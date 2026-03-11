<?php
// modules/sales/index.php
$page_title = "Sales History";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();

// Get sales history
$query = "SELECT s.*, c.customer_name, u.full_name as cashier_name
          FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.customer_id
          JOIN users u ON s.user_id = u.user_id
          ORDER BY s.sale_date DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-receipt"></i> Sales History</h2>
    <a href="pos.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> New Sale
    </a>
</div>

<div class="card shadow">
    <div class="card-body">
        <div class="table-toolbar">
            <input type="text" id="searchSales" class="form-control" placeholder="Search customer or cashier...">
            <input type="date" id="dateFrom" class="form-control" aria-label="From date">
            <input type="date" id="dateTo" class="form-control" aria-label="To date">
            <input type="number" id="minTotal" class="form-control" placeholder="Min total" min="0" step="0.01">
            <input type="number" id="maxTotal" class="form-control" placeholder="Max total" min="0" step="0.01">
            <select id="filterPayment" class="form-select">
                <option value="">All payment methods</option>
                <?php
                $payments = array_unique(array_column($sales, 'payment_method'));
                foreach ($payments as $method): ?>
                    <option value="<?php echo strtolower($method); ?>"><?php echo ucfirst($method); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="flex-grow-1"></div>
            <div class="table-actions">
                <button type="button" class="btn btn-outline-primary btn-sm" id="exportSalesCsv">
                    <i class="bi bi-download"></i> Export CSV
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="exportSalesXls">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sale ID</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-receipt display-4 d-block mb-2"></i>
                                No sales records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td>#<?php echo $sale['sale_id']; ?></td>
                                <td><?php echo formatDate($sale['sale_date'], 'M j, Y g:i A'); ?></td>
                                <td><?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></td>
                                <td><?php echo $sale['cashier_name']; ?></td>
                                <td><strong class="num"><?php echo formatCurrency($sale['final_amount']); ?></strong></td>
                                <td>
                                    <span class="badge bg-info text-uppercase">
                                        <?php echo $sale['payment_method']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="invoice.php?id=<?php echo $sale['sale_id']; ?>"
                                            class="btn btn-outline-primary" title="View Invoice">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="invoice.php?id=<?php echo $sale['sale_id']; ?>&print=1"
                                            class="btn btn-outline-success" title="Print Invoice" target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
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
    const saleSearch = document.getElementById('searchSales');
    const paymentFilter = document.getElementById('filterPayment');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const minTotal = document.getElementById('minTotal');
    const maxTotal = document.getElementById('maxTotal');
    const saleRows = Array.from(document.querySelectorAll('table tbody tr'));

    function filterSales() {
        const term = (saleSearch.value || '').toLowerCase();
        const method = (paymentFilter.value || '').toLowerCase();
        const from = dateFrom.value ? new Date(dateFrom.value) : null;
        const to = dateTo.value ? new Date(dateTo.value) : null;
        const min = minTotal.value ? parseFloat(minTotal.value) : null;
        const max = maxTotal.value ? parseFloat(maxTotal.value) : null;

        saleRows.forEach(row => {
            const customer = row.children[2]?.innerText.toLowerCase() || '';
            const cashier = row.children[3]?.innerText.toLowerCase() || '';
            const payment = row.children[5]?.innerText.toLowerCase() || '';
            const dateText = row.children[1]?.innerText || '';
            const totalText = row.children[4]?.innerText.replace(/[^0-9.]/g, '') || '0';

            const rowDate = dateText ? new Date(dateText) : null;
            const totalVal = parseFloat(totalText) || 0;

            const matchTerm = customer.includes(term) || cashier.includes(term);
            const matchMethod = !method || payment.includes(method);
            const matchDate = (!from || (rowDate && rowDate >= from)) && (!to || (rowDate && rowDate <= to));
            const matchTotal = (!min || totalVal >= min) && (!max || totalVal <= max);

            row.style.display = (matchTerm && matchMethod && matchDate && matchTotal) ? '' : 'none';
        });
    }

    saleSearch.addEventListener('input', filterSales);
    paymentFilter.addEventListener('change', filterSales);
    dateFrom.addEventListener('change', filterSales);
    dateTo.addEventListener('change', filterSales);
    minTotal.addEventListener('input', filterSales);
    maxTotal.addEventListener('input', filterSales);

    const visibleSales = () => Array.from(document.querySelectorAll('table tbody tr')).filter(r => r.style.display !== 'none');

    document.getElementById('exportSalesCsv').addEventListener('click', function() {
        const headers = ['Sale ID', 'Date', 'Customer', 'Cashier', 'Total', 'Payment Method'];
        const data = visibleSales().map(row => {
            const cells = Array.from(row.querySelectorAll('td')).slice(0, 6);
            return cells.map(c => `"${c.innerText.replace(/"/g, '""')}"`).join(',');
        });
        const csv = [headers.join(','), ...data].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'sales.csv';
        link.click();
    });

    document.getElementById('exportSalesXls').addEventListener('click', function() {
        const headers = ['Sale ID', 'Date', 'Customer', 'Cashier', 'Total', 'Payment Method'];
        const rowsHtml = visibleSales().map(row => {
            const cells = Array.from(row.querySelectorAll('td')).slice(0, 6).map(c => `<td>${c.innerText}</td>`).join('');
            return `<tr>${cells}</tr>`;
        }).join('');
        const thead = `<tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>`;
        const tableHtml = `<table>${thead}${rowsHtml}</table>`;
        const blob = new Blob([tableHtml], { type: 'application/vnd.ms-excel' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'sales.xls';
        link.click();
    });
</script>

<?php include '../../includes/footer.php'; ?>
