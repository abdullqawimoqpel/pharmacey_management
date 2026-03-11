<?php
// modules/auth/login.php
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header("Location: ../dashboard/");
    exit;
}

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/constants.php';
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/functions.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();

    try {
        $db = $database->getConnection();

        requireCsrfToken();

        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        // Check login attempts first
        $check_attempts = "SELECT COUNT(*) as attempts 
                          FROM login_attempts 
                          WHERE username = :username 
                          AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";

        $stmt = $db->prepare($check_attempts);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['attempts'] >= 5) {
            throw new Exception("Too many failed login attempts. Please try again in 15 minutes.");
        }

        // Check user credentials
        $query = "SELECT user_id, username, password, full_name, role, is_active 
                  FROM users 
                  WHERE username = :username 
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user['is_active']) {
                throw new Exception("Your account has been deactivated. Please contact administrator.");
            }

            if (password_verify($password, $user['password'])) {
                // ✅ SUCCESSFUL LOGIN - Set ALL session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time'] = time();

                // Update last login
                $update_query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$user['user_id']]);

                // Clear failed attempts
                $clear_attempts = "DELETE FROM login_attempts WHERE username = ?";
                $clear_stmt = $db->prepare($clear_attempts);
                $clear_stmt->execute([$username]);

                // Strengthen session
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }

                // Redirect to dashboard
                header("Location: " . APP_URL . "/modules/dashboard/");
                exit;
            } else {
                // Record failed attempt
                $record_attempt = "INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)";
                $record_stmt = $db->prepare($record_attempt);
                $record_stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);

                throw new Exception("Invalid username or password.");
            }
        } else {
            // Record failed attempt for non-existent user
            $record_attempt = "INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)";
            $record_stmt = $db->prepare($record_attempt);
            $record_stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);

            throw new Exception("Invalid username or password.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// If session exists but missing data, destroy it
if (isset($_SESSION['user_id']) && (!isset($_SESSION['role']) || !isset($_SESSION['full_name']))) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css?v=1">
    <style>
        .login-wrapper {
            background:
                radial-gradient(circle at 18% 20%, rgba(34, 211, 238, 0.1), transparent 32%),
                radial-gradient(circle at 85% 10%, rgba(124, 58, 237, 0.08), transparent 28%),
                linear-gradient(145deg, #0b1221 0%, #0f1c32 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-card {
            background: rgba(17, 26, 44, 0.92);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
            color: var(--text);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(29, 191, 115, 0.25);
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="login-card p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-capsule-pill display-1 text-primary"></i>
                            <h2 class="mt-3 fw-bold text-white"><?php echo SITE_NAME; ?></h2>
                            <p class="text-muted">Welcome back, please sign in</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="_csrf" value="<?php echo getCsrfToken(); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person"></i> Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username"
                                    value="<?php echo htmlspecialchars($username); ?>"
                                    required autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="bi bi-box-arrow-in-right"></i> Sign In
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
