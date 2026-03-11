<?php
// modules/users/edit.php
$page_title = "Edit User";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();
$auth->requireRole('admin');
$csrf_token = getCsrfToken();

// Check optional phone column
try {
    $check_phone = "SHOW COLUMNS FROM users LIKE 'phone'";
    $stmt = $db->prepare($check_phone);
    $stmt->execute();
    $has_phone = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $has_phone = false;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    redirect('index.php');
}

$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCsrfToken();
        $username = sanitizeInput($_POST['username']);
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $role = sanitizeInput($_POST['role']);
        $phone = $has_phone ? sanitizeInput($_POST['phone']) : null;
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($full_name) || empty($role)) {
            throw new Exception("Required fields cannot be empty.");
        }

        // Unique username check
        $check_username = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        $stmt = $db->prepare($check_username);
        $stmt->execute([$username, $user_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already exists. Choose another.");
        }

        // Password change only if filled
        $password_clause = '';
        $params = [$username, $full_name, $email, $role];
        if ($has_phone) {
            $params[] = $phone;
        }
        $params[] = $user_id;

        if (!empty($password)) {
            if (strlen($password) < 6) {
                throw new Exception("Password must be at least 6 characters.");
            }
            if ($password !== $confirm_password) {
                throw new Exception("Passwords do not match.");
            }
            $password_clause = ", password = ?";
            $params = [$username, $full_name, $email, $role];
            if ($has_phone) {
                $params[] = $phone;
            }
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $params[] = $user_id;
        }

        $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?";
        if ($has_phone) {
            $sql .= ", phone = ?";
        }
        $sql .= $password_clause;
        $sql .= " WHERE user_id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $success = "User updated successfully.";
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit User</h4>
                <a href="index.php" class="btn btn-sm btn-outline-light">Back to list</a>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? $user['username']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                value="<?php echo htmlspecialchars($_POST['full_name'] ?? $user['full_name']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role *</label>
                            <select class="form-select" id="role" name="role" required>
                                <?php
                                $selected_role = $_POST['role'] ?? $user['role'];
                                $roles = ['admin' => 'Admin', 'pharmacist' => 'Pharmacist', 'assistant' => 'Assistant'];
                                foreach ($roles as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($selected_role === $key) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if ($has_phone): ?>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone']); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">New Password (optional)</label>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Leave blank to keep current">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                placeholder="Re-type if changing">
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
