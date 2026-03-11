<?php
// modules/customers/add.php
$page_title = "Add New Customer";

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
        $customer_name = sanitizeInput($_POST['customer_name']);
        $phone = sanitizeInput($_POST['phone']);
        $email = sanitizeInput($_POST['email']);
        $address = sanitizeInput($_POST['address']);

        // Validate required fields
        if (empty($customer_name)) {
            throw new Exception("Customer name is required.");
        }

        // Validate email if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        $query = "INSERT INTO customers (customer_name, phone, email, address) 
                 VALUES (?, ?, ?, ?)";

        $stmt = $db->prepare($query);
        $stmt->execute([$customer_name, $phone, $email, $address]);

        $success = "Customer added successfully!";

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
                <h4 class="mb-0"><i class="bi bi-person-plus"></i> Add New Customer</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <div class="mt-2">
                            <a href="index.php" class="btn btn-sm btn-outline-primary me-2">View All Customers</a>
                            <a href="add.php" class="btn btn-sm btn-outline-secondary">Add Another Customer</a>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_name" class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name"
                                value="<?php echo $_POST['customer_name'] ?? ''; ?>" required
                                placeholder="Enter customer full name">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                value="<?php echo $_POST['phone'] ?? ''; ?>"
                                placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?php echo $_POST['email'] ?? ''; ?>"
                            placeholder="Enter email address">
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"
                            placeholder="Enter customer address"><?php echo $_POST['address'] ?? ''; ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
