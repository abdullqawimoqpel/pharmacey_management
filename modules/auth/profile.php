<?php
// modules/auth/profile.php
$page_title = "My Profile";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();

$error = '';
$success = '';

// Get current user data
$user_id = $_SESSION['user_id'];
$query = "SELECT username, full_name, email, phone, role, last_login, created_at FROM users WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    try {
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);

        // Validate required fields
        if (empty($full_name)) {
            throw new Exception("Full name is required.");
        }

        // Validate email if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        $query = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$full_name, $email, $phone, $user_id]);

        // Update session
        $_SESSION['full_name'] = $full_name;

        $success = "Profile updated successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate passwords
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception("All password fields are required.");
        }

        if (strlen($new_password) < 6) {
            throw new Exception("New password must be at least 6 characters long.");
        }

        if ($new_password !== $confirm_password) {
            throw new Exception("New passwords do not match.");
        }

        // Change password using Auth class
        if ($auth->changePassword($user_id, $current_password, $new_password)) {
            $success = "Password changed successfully!";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Profile Information -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person"></i> Profile Information</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username"
                                value="<?php echo $user['username']; ?>" readonly>
                            <div class="form-text">Username cannot be changed</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role"
                                value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name"
                            value="<?php echo $user['full_name']; ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo $user['email'] ?? ''; ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                value="<?php echo $user['phone'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card shadow">
            <div class="card-header bg-warning">
                <h4 class="mb-0"><i class="bi bi-shield-lock"></i> Change Password</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">New Password *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required
                                placeholder="Minimum 6 characters">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Information -->
        <div class="card shadow mt-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="bi bi-info-circle"></i> Account Information</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Last Login:</strong><br>
                            <?php echo $user['last_login'] ? formatDate($user['last_login'], 'F j, Y g:i A') : 'Never'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Account Created:</strong><br>
                            <?php echo formatDate($user['created_at'], 'F j, Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        function checkPasswordMatch() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords do not match");
            } else {
                confirmPassword.setCustomValidity("");
            }
        }

        newPassword.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    });
</script>

<?php include '../../includes/footer.php'; ?>