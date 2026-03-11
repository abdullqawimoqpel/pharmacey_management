<?php
// modules/suppliers/index.php
$page_title = "Suppliers Management";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();

// Handle delete action
if (isset($_POST['delete_supplier'])) {
    $supplier_id = sanitizeInput($_POST['supplier_id']);

    try {
        // Check if supplier has any medicines before deleting
        $check_medicines = "SELECT COUNT(*) FROM medicines WHERE supplier_id = ?";
        $stmt = $db->prepare($check_medicines);
        $stmt->execute([$supplier_id]);
        $medicines_count = $stmt->fetchColumn();

        if ($medicines_count > 0) {
            $_SESSION['error'] = "Cannot delete supplier with associated medicines. Please reassign or delete the medicines first.";
        } else {
            $query = "DELETE FROM suppliers WHERE supplier_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$supplier_id]);
            $_SESSION['success'] = "Supplier deleted successfully";
        }

        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = "Error deleting supplier: " . $e->getMessage();
    }
}

// Handle toggle active status
if (isset($_POST['toggle_status'])) {
    $supplier_id = sanitizeInput($_POST['supplier_id']);

    try {
        $query = "UPDATE suppliers SET is_active = NOT is_active WHERE supplier_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$supplier_id]);

        $_SESSION['success'] = "Supplier status updated successfully";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = "Error updating supplier status: " . $e->getMessage();
    }
}

// Get all suppliers
$query = "SELECT * FROM suppliers ORDER BY supplier_id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-truck"></i> Suppliers Management</h2>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New Supplier
    </a>
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

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-truck display-4 d-block mb-2"></i>
                                No suppliers found. <a href="add.php">Add your first supplier</a>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?php echo $supplier['supplier_id']; ?></td>
                                <td>
                                    <strong><?php echo $supplier['supplier_name']; ?></strong>
                                </td>
                                <td>
                                    <?php echo $supplier['contact_person'] ?: '<span class="text-muted">-</span>'; ?>
                                </td>
                                <td>
                                    <?php if ($supplier['phone']): ?>
                                        <a href="tel:<?php echo $supplier['phone']; ?>" class="text-decoration-none">
                                            <?php echo $supplier['phone']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($supplier['email']): ?>
                                        <a href="mailto:<?php echo $supplier['email']; ?>" class="text-decoration-none">
                                            <?php echo $supplier['email']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($supplier['address']): ?>
                                        <span title="<?php echo $supplier['address']; ?>">
                                            <?php echo strlen($supplier['address']) > 30 ?
                                                substr($supplier['address'], 0, 30) . '...' :
                                                $supplier['address']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="supplier_id" value="<?php echo $supplier['supplier_id']; ?>">
                                        <button type="submit" name="toggle_status"
                                            class="btn btn-sm <?php echo $supplier['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $supplier['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $supplier['supplier_id']; ?>"
                                            class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?php echo $supplier['supplier_id']; ?>"
                                            title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Delete Confirmation Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $supplier['supplier_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirm Delete</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete supplier <strong><?php echo $supplier['supplier_name']; ?></strong>?</p>
                                            <?php
                                            // Check if supplier has medicines
                                            $check_medicines = "SELECT COUNT(*) FROM medicines WHERE supplier_id = ?";
                                            $stmt = $db->prepare($check_medicines);
                                            $stmt->execute([$supplier['supplier_id']]);
                                            $medicines_count = $stmt->fetchColumn();

                                            if ($medicines_count > 0): ?>
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    This supplier has <?php echo $medicines_count; ?> medicine(s) associated with it.
                                                    Deleting will affect these medicines.
                                                </div>
                                            <?php endif; ?>
                                            <p class="text-danger">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                This action cannot be undone.
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <form method="POST">
                                                <input type="hidden" name="supplier_id" value="<?php echo $supplier['supplier_id']; ?>">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="delete_supplier" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>