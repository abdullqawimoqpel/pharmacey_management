<?php
// modules/users/add.php
$page_title = "Add New User";

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

// Check if phone column exists
try {
    $check_phone = "SHOW COLUMNS FROM users LIKE 'phone'";
    $stmt = $db->prepare($check_phone);
    $stmt->execute();
    $has_phone = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $has_phone = false;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        requireCsrfToken();
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $role = sanitizeInput($_POST['role']);

        // Validate required fields
        if (empty($username) || empty($password) || empty($full_name) || empty($role)) {
            throw new Exception("All required fields must be filled.");
        }

        // Check if username already exists
        $check_username = "SELECT user_id FROM users WHERE username = ?";
        $stmt = $db->prepare($check_username);
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already exists. Please choose a different username.");
        }

        // Validate password
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }

        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        // Validate email if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        // Get phone if column exists
        $phone = $has_phone ? sanitizeInput($_POST['phone'] ?? '') : null;

        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Build query based on available columns
        if ($has_phone) {
            $query = "INSERT INTO users (username, password, full_name, email, phone, role) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$username, $password_hash, $full_name, $email, $phone, $role];
        } else {
            $query = "INSERT INTO users (username, password, full_name, email, role) 
                     VALUES (?, ?, ?, ?, ?)";
            $params = [$username, $password_hash, $full_name, $email, $role];
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $success = "User added successfully!";

        // Clear form
        $_POST = array();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person-plus"></i> Add New User</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <div class="mt-2">
                            <a href="index.php" class="btn btn-sm btn-outline-primary me-2">View All Users</a>
                            <a href="add.php" class="btn btn-sm btn-outline-secondary">Add Another User</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$has_phone): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Phone field is not available. <a href="../../update_users_table.php">Update database</a> to enable this feature.
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo $_POST['username'] ?? ''; ?>" required
                                placeholder="Enter username">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                value="<?php echo $_POST['full_name'] ?? ''; ?>" required
                                placeholder="Enter full name">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                placeholder="Enter password (min 6 characters)">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                placeholder="Confirm password">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo $_POST['email'] ?? ''; ?>"
                                placeholder="Enter email address">
                        </div>

                        <?php if ($has_phone): ?>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="<?php echo $_POST['phone'] ?? ''; ?>"
                                    placeholder="Enter phone number">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin" <?php echo ($_POST['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                            <option value="pharmacist" <?php echo ($_POST['role'] ?? '') == 'pharmacist' ? 'selected' : ''; ?>>Pharmacist</option>
                            <option value="assistant" <?php echo ($_POST['role'] ?? '') == 'assistant' ? 'selected' : ''; ?>>Assistant</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password strength indicator
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');

        function checkPasswordMatch() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords do not match");
            } else {
                confirmPassword.setCustomValidity("");
            }
        }

        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    });
</script>

<?php include '../../includes/footer.php'; ?>
