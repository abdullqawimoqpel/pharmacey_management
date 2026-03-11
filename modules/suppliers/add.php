<?php
// modules/suppliers/add.php
$page_title = "Add New Supplier";

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        requireCsrfToken();
        $supplier_name = sanitizeInput($_POST['supplier_name']);
        $contact_person = sanitizeInput($_POST['contact_person']);
        $phone = sanitizeInput($_POST['phone']);
        $email = sanitizeInput($_POST['email']);
        $address = sanitizeInput($_POST['address']);

        // Validate required fields
        if (empty($supplier_name)) {
            throw new Exception("Supplier name is required.");
        }

        // Validate email if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        $query = "INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) 
                 VALUES (?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);
        $stmt->execute([$supplier_name, $contact_person, $phone, $email, $address]);

        $success = "Supplier added successfully!";

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
                <h4 class="mb-0"><i class="bi bi-truck"></i> Add New Supplier</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <div class="mt-2">
                            <a href="index.php" class="btn btn-sm btn-outline-primary me-2">View All Suppliers</a>
                            <a href="add.php" class="btn btn-sm btn-outline-secondary">Add Another Supplier</a>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier_name" class="form-label">Supplier Name *</label>
                            <input type="text" class="form-control" id="supplier_name" name="supplier_name"
                                value="<?php echo $_POST['supplier_name'] ?? ''; ?>" required
                                placeholder="Enter supplier company name">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person"
                                value="<?php echo $_POST['contact_person'] ?? ''; ?>"
                                placeholder="Enter contact person name">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                value="<?php echo $_POST['phone'] ?? ''; ?>"
                                placeholder="Enter phone number">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo $_POST['email'] ?? ''; ?>"
                                placeholder="Enter email address">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"
                            placeholder="Enter supplier address"><?php echo $_POST['address'] ?? ''; ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
