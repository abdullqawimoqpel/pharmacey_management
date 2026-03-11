<?php
// modules/sales/invoice.php
$page_title = "Sale Invoice";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$sale_id = intval($_GET['id']);

// Get sale details
$sale_query = "SELECT s.*, c.customer_name, c.phone as customer_phone, 
               u.full_name as cashier_name
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.customer_id
               JOIN users u ON s.user_id = u.user_id
               WHERE s.sale_id = ?";
$sale_stmt = $db->prepare($sale_query);
$sale_stmt->execute([$sale_id]);
$sale = $sale_stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die("Sale not found.");
}

// Get sale items
$items_query = "SELECT si.*, m.medicine_name, m.generic_name
                FROM sale_items si 
                JOIN medicines m ON si.medicine_id = m.medicine_id 
                WHERE si.sale_id = ?";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute([$sale_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $sale_id; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                font-size: 12pt;
            }

            .container {
                max-width: none;
            }
        }

        .invoice-header {
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .invoice-table th {
            background-color: #f8f9fa;
        }

        .total-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <!-- Print Controls -->
        <div class="no-print mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <a href="pos.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to POS
                </a>
                <div>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bi bi-printer"></i> Print Invoice
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">Sales History</a>
                </div>
            </div>
        </div>

        <!-- Invoice Content -->
        <div class="card shadow">
            <div class="card-body">
                <!-- Header -->
                <div class="row invoice-header">
                    <div class="col-md-6">
                        <h1 class="h3"><?php echo SITE_NAME; ?></h1>
                        <p class="mb-1">123 Pharmacy Street</p>
                        <p class="mb-1">Medical City, State 12345</p>
                        <p class="mb-1">Phone: (123) 456-7890</p>
                        <p class="mb-0">Email: info@pharmacy.com</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h2 class="h4 text-primary">INVOICE</h2>
                        <p class="mb-1"><strong>Invoice #:</strong> <?php echo $sale_id; ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($sale['sale_date'], 'F j, Y g:i A'); ?></p>
                        <p class="mb-0"><strong>Cashier:</strong> <?php echo $sale['cashier_name']; ?></p>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Bill To:</h5>
                        <p class="mb-1">
                            <strong>
                                <?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?>
                            </strong>
                        </p>
                        <?php if ($sale['customer_phone']): ?>
                            <p class="mb-0">Phone: <?php echo $sale['customer_phone']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-end">
                        <h5>Payment Method:</h5>
                        <p class="mb-0">
                            <span class="badge bg-info text-uppercase">
                                <?php echo $sale['payment_method']; ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="invoice-table table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Medicine Name</th>
                                <th>Generic Name</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $index => $item): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $item['medicine_name']; ?></td>
                                    <td><?php echo $item['generic_name'] ?: '-'; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                    <td><?php echo formatCurrency($item['total_price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="row justify-content-end mt-4">
                    <div class="col-md-6">
                        <div class="total-section">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span><?php echo formatCurrency($sale['total_amount']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (5%):</span>
                                <span><?php echo formatCurrency($sale['tax_amount']); ?></span>
                            </div>
                            <?php if ($sale['discount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount:</span>
                                    <span class="text-danger">-<?php echo formatCurrency($sale['discount']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-2 fw-bold fs-5">
                                <span>Total:</span>
                                <span class="text-success"><?php echo formatCurrency($sale['final_amount']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="row mt-5">
                    <div class="col-12 text-center">
                        <p class="mb-1">Thank you for your business!</p>
                        <p class="mb-0 text-muted">
                            This is a computer-generated invoice. No signature required.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['sale_success'])): ?>
            <div class="alert alert-success mt-4 no-print">
                <h5><i class="bi bi-check-circle"></i> <?php echo $_SESSION['sale_success']; ?></h5>
                <?php unset($_SESSION['sale_success']); ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-print option
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            window.print();
        }
    </script>
</body>

</html>