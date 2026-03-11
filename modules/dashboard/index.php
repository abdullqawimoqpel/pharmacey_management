<?php
// modules/dashboard/index.php
$hide_page_header = true;
$page_title = "Dashboard";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();

// Get dashboard statistics
$stats = [];
$queries = [
    'total_medicines' => "SELECT COUNT(*) as count FROM medicines WHERE is_active = 1",
    'low_stock' => "SELECT COUNT(*) as count FROM medicines WHERE quantity_in_stock <= min_stock_level AND is_active = 1",
    'expired_medicines' => "SELECT COUNT(*) as count FROM medicines WHERE expiry_date < CURDATE() AND is_active = 1",
    'expiring_soon' => "SELECT COUNT(*) as count FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND is_active = 1",
    'today_sales' => "SELECT COUNT(*) as count FROM sales WHERE DATE(sale_date) = CURDATE()",
    'today_revenue' => "SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()",
    'month_revenue' => "SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())",
    'total_customers' => "SELECT COUNT(*) as count FROM customers"
];

foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get recent sales
$recent_sales_query = "SELECT s.sale_id, s.final_amount, s.sale_date, c.customer_name 
                      FROM sales s 
                      LEFT JOIN customers c ON s.customer_id = c.customer_id 
                      ORDER BY s.sale_date DESC 
                      LIMIT 5";
$recent_sales_stmt = $db->prepare($recent_sales_query);
$recent_sales_stmt->execute();
$recent_sales = $recent_sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock medicines
$low_stock_query = "SELECT medicine_name, quantity_in_stock, min_stock_level 
                   FROM medicines 
                   WHERE quantity_in_stock <= min_stock_level AND is_active = 1 
                   ORDER BY quantity_in_stock ASC 
                   LIMIT 5";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock_medicines = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header (this loads the language system)
include $root_path . '/includes/header.php';

// Set timezone
date_default_timezone_set('Asia/Riyadh');

$user_name = $_SESSION['full_name'] ?? __('welcome');
?>

<!-- Welcome Section -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="welcome-card">
            <div class="welcome-badge">
                <i class="bi bi-<?php echo (date('H') < 12) ? 'sun' : ((date('H') < 17) ? 'sun-fill' : 'moon-stars'); ?>"></i>
                <?php echo getGreeting(); ?>
            </div>
            <h1 class="welcome-title"><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($user_name); ?></h1>
            <p class="welcome-subtitle">
                <i class="bi bi-calendar3"></i>
                <?php echo getArabicDate(); ?> - <?php echo __('riyadh_time'); ?>
            </p>
            <div class="welcome-stats">
                <div class="welcome-stat">
                    <div class="welcome-stat-value num"><?php echo formatMoney($stats['today_revenue']['total']); ?></div>
                    <div class="welcome-stat-label"><?php echo __('today_revenue'); ?></div>
                </div>
                <div class="welcome-stat">
                    <div class="welcome-stat-value num"><?php echo $stats['today_sales']['count']; ?></div>
                    <div class="welcome-stat-label"><?php echo __('sales_count'); ?></div>
                </div>
                <div class="welcome-stat">
                    <div class="welcome-stat-value num"><?php echo formatMoney($stats['month_revenue']['total']); ?></div>
                    <div class="welcome-stat-label"><?php echo __('month_revenue'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="quick-card">
            <div class="quick-title">
                <i class="bi bi-lightning-charge-fill"></i>
                <?php echo __('quick_actions'); ?>
            </div>
            <a href="../sales/pos.php" class="quick-btn primary">
                <i class="bi bi-cart-plus"></i>
                <?php echo __('nav_pos'); ?>
            </a>
            <a href="../medicines/add.php" class="quick-btn secondary">
                <i class="bi bi-plus-circle"></i>
                <?php echo __('add_medicine'); ?>
            </a>
            <a href="../reports/" class="quick-btn secondary">
                <i class="bi bi-bar-chart"></i>
                <?php echo __('nav_reports'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($stats['expired_medicines']['count'] > 0): ?>
<div class="alert alert-danger mb-4">
    <div class="alert-icon">
        <i class="bi bi-exclamation-triangle-fill"></i>
    </div>
    <div class="alert-content">
        <h6><?php echo __('alert_expired'); ?></h6>
        <p><?php echo $stats['expired_medicines']['count']; ?> <?php echo __('expired_need_review'); ?></p>
    </div>
    <a href="../medicines/?filter=expired" class="btn btn-outline-danger btn-sm ms-auto"><?php echo __('review_now'); ?></a>
</div>
<?php endif; ?>

<?php if ($stats['low_stock']['count'] >= 5): ?>
<div class="alert alert-warning mb-4">
    <div class="alert-icon">
        <i class="bi bi-box-seam"></i>
    </div>
    <div class="alert-content">
        <h6><?php echo __('alert_low_stock'); ?></h6>
        <p><?php echo $stats['low_stock']['count']; ?> <?php echo __('items_need_reorder'); ?></p>
    </div>
    <a href="../medicines/?filter=low_stock" class="btn btn-outline-warning btn-sm ms-auto"><?php echo __('manage_stock'); ?></a>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label"><?php echo __('total_medicines'); ?></div>
                <div class="stat-value num"><?php echo number_format($stats['total_medicines']['count']); ?></div>
            </div>
            <div class="stat-icon green"><i class="bi bi-capsule"></i></div>
        </div>
        <div class="stat-footer"><?php echo __('active_products'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label"><?php echo __('total_customers'); ?></div>
                <div class="stat-value num"><?php echo number_format($stats['total_customers']['count']); ?></div>
            </div>
            <div class="stat-icon blue"><i class="bi bi-people"></i></div>
        </div>
        <div class="stat-footer"><?php echo __('registered_customers'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label"><?php echo __('stock_alerts'); ?></div>
                <div class="stat-value num"><?php echo $stats['low_stock']['count']; ?></div>
            </div>
            <div class="stat-icon yellow"><i class="bi bi-exclamation-triangle"></i></div>
        </div>
        <div class="stat-footer"><?php echo $stats['low_stock']['count'] > 0 ? __('needs_review') : __('stock_ok'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label"><?php echo __('expired'); ?></div>
                <div class="stat-value num"><?php echo $stats['expired_medicines']['count']; ?></div>
            </div>
            <div class="stat-icon red"><i class="bi bi-calendar-x"></i></div>
        </div>
        <div class="stat-footer"><?php echo __('needs_removal'); ?></div>
    </div>
</div>

<!-- Data Cards -->
<div class="row g-4">
    <!-- Today Summary -->
    <div class="col-lg-4">
        <div class="summary-card">
            <div class="summary-title">
                <i class="bi bi-calendar-check"></i>
                <?php echo __('today_summary'); ?>
            </div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value num"><?php echo $stats['today_sales']['count']; ?></div>
                    <div class="summary-label"><?php echo __('sales_count'); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-value num" style="font-size:14px;"><?php echo formatMoney($stats['today_revenue']['total']); ?></div>
                    <div class="summary-label"><?php echo __('today_revenue'); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-value num" style="font-size:14px;">
                        <?php echo $stats['today_sales']['count'] > 0 ? formatMoney($stats['today_revenue']['total'] / $stats['today_sales']['count']) : formatMoney(0); ?>
                    </div>
                    <div class="summary-label"><?php echo __('avg_invoice'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-receipt green"></i> <?php echo __('recent_sales'); ?></h5>
                <a href="../sales/" class="view-link"><?php echo __('view_all'); ?> <i class="bi bi-arrow-left"></i></a>
            </div>
            <div class="data-list">
                <?php if ($recent_sales): ?>
                    <?php foreach ($recent_sales as $sale): ?>
                    <div class="data-item">
                        <div class="data-item-info">
                            <h6>#<?php echo $sale['sale_id']; ?> - <?php echo $sale['customer_name'] ?: __('walk_in'); ?></h6>
                            <small><?php echo formatDate($sale['sale_date'], 'd/m/Y H:i'); ?></small>
                        </div>
                        <div class="data-item-value success num"><?php echo formatMoney($sale['final_amount']); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p><?php echo __('no_sales'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-box-seam yellow"></i> <?php echo __('stock_alerts'); ?></h5>
                <a href="../medicines/" class="view-link"><?php echo __('view_all'); ?> <i class="bi bi-arrow-left"></i></a>
            </div>
            <div class="data-list">
                <?php if ($low_stock_medicines): ?>
                    <?php foreach ($low_stock_medicines as $med): ?>
                    <div class="data-item">
                        <div class="data-item-info">
                            <h6><?php echo htmlspecialchars($med['medicine_name']); ?></h6>
                            <small><?php echo __('min_level'); ?>: <?php echo $med['min_stock_level']; ?></small>
                        </div>
                        <span class="tag <?php echo $med['quantity_in_stock'] == 0 ? 'danger' : 'warning'; ?>">
                            <?php echo $med['quantity_in_stock']; ?> <?php echo __('remaining'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <p><?php echo __('stock_excellent'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include $root_path . '/includes/footer.php'; ?>
