<?php
// modules/medicines/index.php
$page_title = "Medicines";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();
$csrf_token = getCsrfToken();

// Handle delete action
if (isset($_POST['delete_medicine'])) {
    requireCsrfToken();
    $medicine_id = sanitizeInput($_POST['medicine_id']);

    try {
        $query = "UPDATE medicines SET is_active = 0 WHERE medicine_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$medicine_id]);

        $_SESSION['success'] = getCurrentLang() === 'ar' ? "تم حذف الدواء بنجاح" : "Medicine deleted successfully";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all medicines
try {
    $check_tables = "SHOW TABLES LIKE 'categories'";
    $stmt = $db->prepare($check_tables);
    $stmt->execute();
    $categories_table_exists = $stmt->rowCount() > 0;

    $check_column = "SHOW COLUMNS FROM medicines LIKE 'category_id'";
    $stmt = $db->prepare($check_column);
    $stmt->execute();
    $category_column_exists = $stmt->rowCount() > 0;

    if ($categories_table_exists && $category_column_exists) {
        $query = "SELECT m.*, s.supplier_name, c.category_name 
                  FROM medicines m 
                  LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id 
                  LEFT JOIN categories c ON m.category_id = c.category_id 
                  WHERE m.is_active = 1 
                  ORDER BY m.medicine_id DESC";
    } else {
        $query = "SELECT m.*, s.supplier_name, NULL as category_name 
                  FROM medicines m 
                  LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id 
                  WHERE m.is_active = 1 
                  ORDER BY m.medicine_id DESC";
    }

    $stmt = $db->prepare($query);
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $query = "SELECT * FROM medicines WHERE is_active = 1 ORDER BY medicine_id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($medicines as &$medicine) {
        $medicine['supplier_name'] = null;
        $medicine['category_name'] = null;
    }
}

include $root_path . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-capsule text-green"></i> <?php echo __('nav_medicines'); ?></h2>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> <?php echo __('add_medicine'); ?>
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-list-ul green"></i>
            <?php echo getCurrentLang() === 'ar' ? 'قائمة الأدوية' : 'Medicines List'; ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchMedicines" class="form-control" 
                   placeholder="<?php echo __('search'); ?>...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover" id="medicinesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo __('medicine_name'); ?></th>
                        <th><?php echo getCurrentLang() === 'ar' ? 'الاسم العام' : 'Generic'; ?></th>
                        <th><?php echo getCurrentLang() === 'ar' ? 'المورد' : 'Supplier'; ?></th>
                        <th><?php echo __('price'); ?></th>
                        <th><?php echo __('quantity'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('expiry_date'); ?></th>
                        <th><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-capsule display-4 d-block mb-2"></i>
                                <?php echo getCurrentLang() === 'ar' ? 'لا توجد أدوية' : 'No medicines found'; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($medicines as $medicine): ?>
                            <tr>
                                <td><?php echo $medicine['medicine_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($medicine['medicine_name']); ?></strong>
                                    <?php if (!empty($medicine['batch_number'])): ?>
                                        <br><small class="text-muted">Batch: <?php echo $medicine['batch_number']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $medicine['generic_name'] ?: '-'; ?></td>
                                <td><?php echo $medicine['supplier_name'] ?: '-'; ?></td>
                                <td class="num"><?php echo formatMoney($medicine['selling_price']); ?></td>
                                <td>
                                    <?php echo $medicine['quantity_in_stock']; ?>
                                    <?php echo getStockStatus($medicine['quantity_in_stock'], $medicine['min_stock_level']); ?>
                                </td>
                                <td><?php echo getExpiryStatus($medicine['expiry_date']); ?></td>
                                <td><?php echo formatDate($medicine['expiry_date']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $medicine['medicine_id']; ?>" 
                                           class="btn btn-outline-primary" title="<?php echo __('edit'); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?php echo $medicine['medicine_id']; ?>"
                                            title="<?php echo __('delete'); ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $medicine['medicine_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo getCurrentLang() === 'ar' ? 'تأكيد الحذف' : 'Confirm Delete'; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><?php echo getCurrentLang() === 'ar' ? 'هل أنت متأكد من حذف' : 'Delete'; ?> <strong><?php echo $medicine['medicine_name']; ?></strong>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST">
                                                        <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="medicine_id" value="<?php echo $medicine['medicine_id']; ?>">
                                                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                                                        <button type="submit" name="delete_medicine" class="btn btn-danger"><?php echo __('delete'); ?></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('searchMedicines').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#medicinesTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});
</script>

<?php include $root_path . '/includes/footer.php'; ?>
