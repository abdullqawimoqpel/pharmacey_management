<?php
// modules/customers/index.php
$page_title = "Customers Management";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();

// Handle delete action
if (isset($_POST['delete_customer'])) {
    $customer_id = sanitizeInput($_POST['customer_id']);

    try {
        // Check if customer has any sales before deleting
        $check_sales = "SELECT COUNT(*) FROM sales WHERE customer_id = ?";
        $stmt = $db->prepare($check_sales);
        $stmt->execute([$customer_id]);
        $sales_count = $stmt->fetchColumn();

        if ($sales_count > 0) {
            $_SESSION['error'] = "Cannot delete customer with existing sales records.";
        } else {
            $query = "DELETE FROM customers WHERE customer_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$customer_id]);
            $_SESSION['success'] = "Customer deleted successfully";
        }

        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = "Error deleting customer: " . $e->getMessage();
    }
}

// Safely get all customers with fallback for missing columns
try {
    // First check if the additional columns exist
    $check_columns = "SHOW COLUMNS FROM customers";
    $stmt = $db->prepare($check_columns);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $has_loyalty_points = in_array('loyalty_points', $columns);
    $has_total_purchases = in_array('total_purchases', $columns);
    $has_last_purchase_date = in_array('last_purchase_date', $columns);

    // Build query based on available columns
    $select_fields = ["customer_id", "customer_name", "phone", "email", "address"];

    if ($has_loyalty_points) $select_fields[] = "loyalty_points";
    if ($has_total_purchases) $select_fields[] = "total_purchases";
    if ($has_last_purchase_date) $select_fields[] = "last_purchase_date";

    $query = "SELECT " . implode(", ", $select_fields) . " FROM customers ORDER BY customer_id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching customers: " . $e->getMessage();
    $customers = [];
}

// Set flags for template
$show_loyalty_points = $has_loyalty_points ?? false;
$show_total_purchases = $has_total_purchases ?? false;
$show_last_purchase_date = $has_last_purchase_date ?? false;
?>

<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Customers Management</h2>
    <div>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Customer
        </a>
        <?php if ($_SESSION['role'] == 'admin' && (!$show_loyalty_points || !$show_total_purchases)): ?>
            <a href="../../update_database.php" class="btn btn-outline-warning">
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

<?php if (!$show_loyalty_points || !$show_total_purchases): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Database Update Required:</strong> Some customer features are not available.
        <a href="../../update_database.php" class="alert-link">Click here to update the database</a>.
    </div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <?php if ($show_loyalty_points): ?>
                            <th>Loyalty Points</th>
                        <?php endif; ?>
                        <?php if ($show_total_purchases): ?>
                            <th>Total Purchases</th>
                        <?php endif; ?>
                        <?php if ($show_last_purchase_date): ?>
                            <th>Last Purchase</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="<?php echo 5 + ($show_loyalty_points ? 1 : 0) + ($show_total_purchases ? 1 : 0) + ($show_last_purchase_date ? 1 : 0); ?>" class="text-center text-muted py-4">
                                <i class="bi bi-people display-4 d-block mb-2"></i>
                                No customers found. <a href="add.php">Add your first customer</a>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['customer_id']; ?></td>
                                <td>
                                    <strong><?php echo $customer['customer_name']; ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($customer['phone'])): ?>
                                        <a href="tel:<?php echo $customer['phone']; ?>" class="text-decoration-none">
                                            <?php echo $customer['phone']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($customer['email'])): ?>
                                        <a href="mailto:<?php echo $customer['email']; ?>" class="text-decoration-none">
                                            <?php echo $customer['email']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($customer['address'])): ?>
                                        <span title="<?php echo htmlspecialchars($customer['address']); ?>">
                                            <?php echo strlen($customer['address']) > 30 ?
                                                substr($customer['address'], 0, 30) . '...' :
                                                $customer['address']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($show_loyalty_points): ?>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $customer['loyalty_points'] ?? 0; ?> pts
                                        </span>
                                    </td>
                                <?php endif; ?>

                                <?php if ($show_total_purchases): ?>
                                    <td>
                                        <strong><?php echo formatCurrency($customer['total_purchases'] ?? 0); ?></strong>
                                    </td>
                                <?php endif; ?>

                                <?php if ($show_last_purchase_date): ?>
                                    <td>
                                        <?php echo !empty($customer['last_purchase_date']) ?
                                            formatDate($customer['last_purchase_date']) :
                                            '<span class="text-muted">Never</span>'; ?>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $customer['customer_id']; ?>"
                                            class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?php echo $customer['customer_id']; ?>"
                                            title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Delete Confirmation Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $customer['customer_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirm Delete</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete customer <strong><?php echo $customer['customer_name']; ?></strong>?</p>
                                            <p class="text-danger">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                This action cannot be undone.
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <form method="POST">
                                                <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="delete_customer" class="btn btn-danger">Delete</button>
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