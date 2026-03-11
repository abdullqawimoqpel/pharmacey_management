<?php
// modules/medicines/add.php
$page_title = "Add New Medicine";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();
$csrf_token = getCsrfToken();

// Initialize feedback holders
$error = '';
$success = '';
$error_messages = [];

// Get suppliers and categories for dropdowns
// In modules/medicines/add.php - Replace the categories section:

// Get suppliers and categories for dropdowns - Safely
$suppliers_query = "SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name";
$suppliers_stmt = $db->prepare($suppliers_query);
$suppliers_stmt->execute();
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Safely get categories
try {
    $categories_query = "SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name";
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If categories table doesn't exist, use empty array
    $categories = [];
    $error_messages[] = "Categories table not available. Please run database repair.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        requireCsrfToken();
        $medicine_name = sanitizeInput($_POST['medicine_name']);
        $generic_name = sanitizeInput($_POST['generic_name']);
        $category_id = $_POST['category_id'] ?: null;
        $supplier_id = $_POST['supplier_id'] ?: null;
        $batch_number = sanitizeInput($_POST['batch_number']);
        $expiry_date = $_POST['expiry_date'];
        $purchase_price = floatval($_POST['purchase_price']);
        $selling_price = floatval($_POST['selling_price']);
        $quantity_in_stock = intval($_POST['quantity_in_stock']);
        $min_stock_level = intval($_POST['min_stock_level']);
        $description = sanitizeInput($_POST['description']);

        // Validate required fields
        if (empty($medicine_name) || empty($expiry_date) || $purchase_price <= 0 || $selling_price <= 0) {
            throw new Exception("Please fill all required fields with valid values.");
        }

        // Validate expiry date
        if (strtotime($expiry_date) < strtotime('today')) {
            throw new Exception("Expiry date cannot be in the past.");
        }

        // Validate prices
        if ($selling_price < $purchase_price) {
            throw new Exception("Selling price cannot be less than purchase price.");
        }

        $query = "INSERT INTO medicines (medicine_name, generic_name, category_id, supplier_id, 
                 batch_number, expiry_date, purchase_price, selling_price, quantity_in_stock, 
                 min_stock_level, description) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);
        $stmt->execute([
            $medicine_name,
            $generic_name,
            $category_id,
            $supplier_id,
            $batch_number,
            $expiry_date,
            $purchase_price,
            $selling_price,
            $quantity_in_stock,
            $min_stock_level,
            $description
        ]);

        $success = "Medicine added successfully!";

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
                <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Medicine</h4>
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
                            <label for="medicine_name" class="form-label">Medicine Name *</label>
                            <input type="text" class="form-control" id="medicine_name" name="medicine_name"
                                value="<?php echo $_POST['medicine_name'] ?? ''; ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="generic_name" class="form-label">Generic Name</label>
                            <input type="text" class="form-control" id="generic_name" name="generic_name"
                                value="<?php echo $_POST['generic_name'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo ($_POST['category_id'] ?? '') == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo $category['category_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="supplier_id" class="form-label">Supplier</label>
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>"
                                        <?php echo ($_POST['supplier_id'] ?? '') == $supplier['supplier_id'] ? 'selected' : ''; ?>>
                                        <?php echo $supplier['supplier_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="batch_number" class="form-label">Batch Number</label>
                            <input type="text" class="form-control" id="batch_number" name="batch_number"
                                value="<?php echo $_POST['batch_number'] ?? ''; ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="expiry_date" class="form-label">Expiry Date *</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date"
                                value="<?php echo $_POST['expiry_date'] ?? ''; ?>" required
                                min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="purchase_price" class="form-label">Purchase Price *</label>
                            <input type="number" class="form-control" id="purchase_price" name="purchase_price"
                                value="<?php echo $_POST['purchase_price'] ?? ''; ?>" step="0.01" min="0.01" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="selling_price" class="form-label">Selling Price *</label>
                            <input type="number" class="form-control" id="selling_price" name="selling_price"
                                value="<?php echo $_POST['selling_price'] ?? ''; ?>" step="0.01" min="0.01" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="quantity_in_stock" class="form-label">Quantity in Stock *</label>
                            <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock"
                                value="<?php echo $_POST['quantity_in_stock'] ?? 0; ?>" min="0" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="min_stock_level" class="form-label">Minimum Stock Level</label>
                            <input type="number" class="form-control" id="min_stock_level" name="min_stock_level"
                                value="<?php echo $_POST['min_stock_level'] ?? 10; ?>" min="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Medicine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-calculate selling price if empty
        const purchasePrice = document.getElementById('purchase_price');
        const sellingPrice = document.getElementById('selling_price');

        purchasePrice.addEventListener('blur', function() {
            if (!sellingPrice.value && purchasePrice.value) {
                const purchaseVal = parseFloat(purchasePrice.value);
                sellingPrice.value = (purchaseVal * 1.3).toFixed(2); // 30% markup
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>
