<?php
// modules/medicines/edit.php
$page_title = "Edit Medicine";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();
$csrf_token = getCsrfToken();

$error = '';
$success = '';
$error_messages = [];

$medicine_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($medicine_id <= 0) {
    redirect('index.php');
}

// Fetch existing record
$query = "SELECT * FROM medicines WHERE medicine_id = ? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([$medicine_id]);
$medicine = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$medicine) {
    redirect('index.php');
}

// Suppliers
$suppliers_stmt = $db->prepare("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
$suppliers_stmt->execute();
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Categories (safe)
try {
    $categories_stmt = $db->prepare("SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
    $error_messages[] = "Categories table not available. Please run database repair.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        if (empty($medicine_name) || empty($expiry_date) || $purchase_price <= 0 || $selling_price <= 0) {
            throw new Exception("Please fill all required fields with valid values.");
        }

        if (strtotime($expiry_date) < strtotime('today')) {
            throw new Exception("Expiry date cannot be in the past.");
        }

        if ($selling_price < $purchase_price) {
            throw new Exception("Selling price cannot be less than purchase price.");
        }

        $update = "UPDATE medicines SET 
            medicine_name = ?, generic_name = ?, category_id = ?, supplier_id = ?,
            batch_number = ?, expiry_date = ?, purchase_price = ?, selling_price = ?,
            quantity_in_stock = ?, min_stock_level = ?, description = ?
            WHERE medicine_id = ?";
        $stmt = $db->prepare($update);
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
            $description,
            $medicine_id
        ]);

        $success = "Medicine updated successfully.";
        // Refresh data
        $stmt = $db->prepare($query);
        $stmt->execute([$medicine_id]);
        $medicine = $stmt->fetch(PDO::FETCH_ASSOC);
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
                <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Medicine</h4>
                <a href="index.php" class="btn btn-sm btn-outline-light">Back to list</a>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_messages)): ?>
                    <div class="alert alert-warning">
                        <?php foreach ($error_messages as $msg) echo "<div>$msg</div>"; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="medicine_name" class="form-label">Medicine Name *</label>
                            <input type="text" class="form-control" id="medicine_name" name="medicine_name"
                                value="<?php echo htmlspecialchars($_POST['medicine_name'] ?? $medicine['medicine_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="generic_name" class="form-label">Generic Name</label>
                            <input type="text" class="form-control" id="generic_name" name="generic_name"
                                value="<?php echo htmlspecialchars($_POST['generic_name'] ?? $medicine['generic_name']); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo (($_POST['category_id'] ?? $medicine['category_id']) == $category['category_id']) ? 'selected' : ''; ?>>
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
                                        <?php echo (($_POST['supplier_id'] ?? $medicine['supplier_id']) == $supplier['supplier_id']) ? 'selected' : ''; ?>>
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
                                value="<?php echo htmlspecialchars($_POST['batch_number'] ?? $medicine['batch_number']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expiry_date" class="form-label">Expiry Date *</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date"
                                value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? $medicine['expiry_date']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="purchase_price" class="form-label">Purchase Price *</label>
                            <input type="number" class="form-control" id="purchase_price" name="purchase_price"
                                value="<?php echo htmlspecialchars($_POST['purchase_price'] ?? $medicine['purchase_price']); ?>" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="selling_price" class="form-label">Selling Price *</label>
                            <input type="number" class="form-control" id="selling_price" name="selling_price"
                                value="<?php echo htmlspecialchars($_POST['selling_price'] ?? $medicine['selling_price']); ?>" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="quantity_in_stock" class="form-label">Quantity in Stock *</label>
                            <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock"
                                value="<?php echo htmlspecialchars($_POST['quantity_in_stock'] ?? $medicine['quantity_in_stock']); ?>" min="0" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="min_stock_level" class="form-label">Minimum Stock Level</label>
                            <input type="number" class="form-control" id="min_stock_level" name="min_stock_level"
                                value="<?php echo htmlspecialchars($_POST['min_stock_level'] ?? $medicine['min_stock_level']); ?>" min="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $medicine['description']); ?></textarea>
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
