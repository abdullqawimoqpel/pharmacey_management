<?php
// includes/header.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__FILE__) . '/language.php';

if (!isset($page_title)) {
    $page_title = __('site_name');
}

$user_role = $_SESSION['role'] ?? null;
$user_full_name = $_SESSION['full_name'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? null;
$direction = getDirection();
$lang = getCurrentLang();
$other_lang = $lang === 'ar' ? 'en' : 'ar';
$other_lang_name = $lang === 'ar' ? 'English' : 'العربية';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo __('site_name'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if ($direction === 'rtl'): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <?php else: ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-light btn-sm d-lg-none" type="button" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <a class="navbar-brand" href="<?php echo APP_URL; ?>/modules/dashboard/">
                    <i class="bi bi-capsule-pill"></i>
                    <?php echo __('site_name'); ?>
                </a>
            </div>

            <div class="navbar-nav ms-auto d-flex flex-row align-items-center gap-3">
                <!-- Language Switch -->
                <a href="?lang=<?php echo $other_lang; ?>" class="lang-switch">
                    <i class="bi bi-globe"></i>
                    <span><?php echo $other_lang_name; ?></span>
                </a>

                <?php if ($user_id): ?>
                <div class="dropdown user-dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($user_full_name); ?></span>
                        <div class="user-avatar"><?php echo mb_substr($user_full_name, 0, 1); ?></div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?php echo APP_URL; ?>/modules/auth/profile.php">
                                <i class="bi bi-person"></i> <?php echo __('profile'); ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/modules/auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> <?php echo __('logout'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
                <?php else: ?>
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/auth/login.php">
                    <i class="bi bi-box-arrow-in-right"></i> <?php echo __('login'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php if ($user_id): ?>
            <aside class="col-lg-2 sidebar" id="sidebarMenu">
                <nav class="nav flex-column">
                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false ? 'active' : ''; ?>" 
                       href="<?php echo APP_URL; ?>/modules/dashboard/">
                        <i class="bi bi-speedometer2"></i>
                        <?php echo __('nav_dashboard'); ?>
                    </a>
                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/medicines/') !== false ? 'active' : ''; ?>" 
                       href="<?php echo APP_URL; ?>/modules/medicines/">
                        <i class="bi bi-capsule"></i>
                        <?php echo __('nav_medicines'); ?>
                    </a>
                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/sales/pos') !== false ? 'active' : ''; ?>" 
                       href="<?php echo APP_URL; ?>/modules/sales/pos.php">
                        <i class="bi bi-upc-scan"></i>
                        <?php echo __('nav_pos'); ?>
                    </a>
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/sales/') !== false && strpos($_SERVER['PHP_SELF'], 'pos') === false) ? 'active' : ''; ?>" 
                       href="<?php echo APP_URL; ?>/modules/sales/">
                        <i class="bi bi-cart-check"></i>
                        <?php echo __('nav_sales'); ?>
                    </a>
                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/customers/') !== false ? 'active' : ''; ?>" 
                       href="<?php echo APP_URL; ?>/modules/customers/">
                        <i class="bi bi-people"></i>
                        <?php echo __('nav_customers'); ?>
                    </a>
                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/suppliers/') !== false ? 'active' : ''; ?>" 
                       href="<?php echo APP_URL; ?>/modules/suppliers/">
                        <i class="bi bi-truck"></i>
                        <?php echo __('nav_suppliers'); ?>
                    </a>
                    
                    <?php if ($user_role == 'admin'): ?>
                    <div class="sidebar-divider"></div>
                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : ''; ?>" 
                       href="<?php echo APP_URL; ?>/modules/reports/">
                        <i class="bi bi-graph-up"></i>
                        <?php echo __('nav_reports'); ?>
                    </a>
                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : ''; ?>" 
                       href="<?php echo APP_URL; ?>/modules/users/">
                        <i class="bi bi-person-gear"></i>
                        <?php echo __('nav_users'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <div class="sidebar-divider"></div>
                    <a class="nav-link text-red" href="<?php echo APP_URL; ?>/modules/auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <?php echo __('logout'); ?>
                    </a>
                </nav>
            </aside>
            <?php endif; ?>

            <!-- Main content -->
            <main class="<?php echo $user_id ? 'col-lg-10' : 'col-12'; ?> content-wrapper">
                <?php if (!isset($hide_page_header) || !$hide_page_header): ?>
                <div class="page-header">
                    <h1><?php echo $page_title; ?></h1>
                    <div class="btn-toolbar">
                        <?php if (isset($page_actions)) echo $page_actions; ?>
                    </div>
                </div>
                <?php endif; ?>
