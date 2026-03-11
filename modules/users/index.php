<?php
// modules/users/index.php
$page_title = "Users Management";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();
$auth->requireRole('admin'); // Only admin can access users management

// Safely check which columns exist in users table
try {
    $check_columns = "SHOW COLUMNS FROM users";
    $stmt = $db->prepare($check_columns);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $has_phone = in_array('phone', $columns);
    $has_last_login = in_array('last_login', $columns);
} catch (Exception $e) {
    $has_phone = false;
    $has_last_login = false;
}

// Handle actions
if (isset($_POST['toggle_status'])) {
    $user_id = sanitizeInput($_POST['user_id']);

    try {
        $query = "UPDATE users SET is_active = NOT is_active WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);

        $_SESSION['success'] = "User status updated successfully";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = "Error updating user status: " . $e->getMessage();
    }
}

if (isset($_POST['delete_user'])) {
    $user_id = sanitizeInput($_POST['user_id']);

    try {
        // Prevent deleting own account
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "You cannot delete your own account.";
        } else {
            $query = "DELETE FROM users WHERE user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "User deleted successfully";
        }

        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Build query based on available columns
$select_fields = ["user_id", "username", "full_name", "email", "role", "is_active", "created_at"];
if ($has_phone) $select_fields[] = "phone";
if ($has_last_login) $select_fields[] = "last_login";

$query = "SELECT " . implode(", ", $select_fields) . " FROM users ORDER BY user_id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-gear"></i> Users Management</h2>
    <div>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New User
        </a>
        <?php if (!$has_phone || !$has_last_login): ?>
            <a href="../../update_users_table.php" class="btn btn-outline-warning">
                <i class="bi bi-tools"></i> Update Database
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success'];
        unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error'];
        unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$has_phone || !$has_last_login): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Database Update Recommended:</strong> Some user features are not available.
        <a href="../../update_users_table.php" class="alert-link">Click here to update the database</a>.
    </div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <?php if ($has_phone): ?>
                            <th>Phone</th>
                        <?php endif; ?>
                        <th>Role</th>
                        <th>Status</th>
                        <?php if ($has_last_login): ?>
                            <th>Last Login</th>
                        <?php endif; ?>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="<?php echo 5 + ($has_phone ? 1 : 0) + ($has_last_login ? 1 : 0) + 2; ?>" class="text-center text-muted py-4">
                                <i class="bi bi-person-gear display-4 d-block mb-2"></i>
                                No users found. <a href="add.php">Add your first user</a>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td>
                                    <strong><?php echo $user['username']; ?></strong>
                                    <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-primary">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['full_name']; ?></td>
                                <td><?php echo $user['email'] ?: '-'; ?></td>

                                <?php if ($has_phone): ?>
                                    <td><?php echo $user['phone'] ?: '-'; ?></td>
                                <?php endif; ?>

                                <td>
                                    <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'pharmacist' ? 'warning' : 'info'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" name="toggle_status"
                                            class="btn btn-sm <?php echo $user['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>

                                <?php if ($has_last_login): ?>
                                    <td>
                                        <?php echo $user['last_login'] ? formatDate($user['last_login'], 'M j, Y g:i A') : 'Never'; ?>
                                    </td>
                                <?php endif; ?>

                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $user['user_id']; ?>"
                                            class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?php echo $user['user_id']; ?>"
                                                title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- Delete Confirmation Modal -->
                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <div class="modal fade" id="deleteModal<?php echo $user['user_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete user <strong><?php echo $user['full_name']; ?></strong>?</p>
                                                <p class="text-danger">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    This action cannot be undone.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>