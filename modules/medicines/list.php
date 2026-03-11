<?php
// modules/medicines/list.php - simple readonly list
$page_title = "Medicines List";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();

$stmt = $db->prepare("SELECT medicine_id, medicine_name, quantity_in_stock, selling_price FROM medicines WHERE is_active = 1 ORDER BY medicine_name");
$stmt->execute();
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once $root_path . '/includes/header.php';
?>

<div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Medicines</h4>
        <a class="btn btn-sm btn-primary" href="add.php"><i class="bi bi-plus-circle"></i> Add Medicine</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No medicines found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($medicines as $m): ?>
                            <tr>
                                <td><?php echo $m['medicine_id']; ?></td>
                                <td><?php echo htmlspecialchars($m['medicine_name']); ?></td>
                                <td class="num"><?php echo $m['quantity_in_stock']; ?></td>
                                <td class="num"><?php echo formatCurrency($m['selling_price']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once $root_path . '/includes/footer.php'; ?>
