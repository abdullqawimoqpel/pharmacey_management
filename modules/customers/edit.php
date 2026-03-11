<?php
// modules/customers/edit.php
$page_title = "Edit Customer";

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

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($customer_id <= 0) {
    redirect('index.php');
}

$stmt = $db->prepare("SELECT * FROM customers WHERE customer_id = ? LIMIT 1");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCsrfToken();
        $customer_name = sanitizeInput($_POST['customer_name']);
        $phone = sanitizeInput($_POST['phone']);
        $email = sanitizeInput($_POST['email']);
        $address = sanitizeInput($_POST['address']);

        if (empty($customer_name)) {
            throw new Exception("Customer name is required.");
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        $update = "UPDATE customers SET customer_name = ?, phone = ?, email = ?, address = ? WHERE customer_id = ?";
        $stmt = $db->prepare($update);
        $stmt->execute([$customer_name, $phone, $email, $address, $customer_id]);

        $success = "Customer updated successfully.";
        $stmt = $db->prepare("SELECT * FROM customers WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
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
                <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Customer</h4>
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
                            <label for="customer_name" class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name"
                                value="<?php echo htmlspecialchars($_POST['customer_name'] ?? $customer['customer_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($_POST['phone'] ?? $customer['phone']); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? $customer['email']); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? $customer['address']); ?></textarea>
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
