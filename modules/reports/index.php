<?php
// modules/reports/index.php
$page_title = "Reports & Analytics";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();
$auth->requireRole('admin');

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

// Sales reports
$sales_report_query = "SELECT 
    DATE(sale_date) as sale_day,
    COUNT(*) as sales_count,
    SUM(final_amount) as total_revenue,
    AVG(final_amount) as avg_sale
FROM sales 
WHERE DATE(sale_date) BETWEEN ? AND ?
GROUP BY DATE(sale_date)
ORDER BY sale_day DESC";

$stmt = $db->prepare($sales_report_query);
$stmt->execute([$start_date, $end_date]);
$sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top medicines
$top_medicines_query = "SELECT 
    m.medicine_name,
    SUM(si.quantity) as total_sold,
    SUM(si.total_price) as revenue
FROM sale_items si
JOIN medicines m ON si.medicine_id = m.medicine_id
JOIN sales s ON si.sale_id = s.sale_id
WHERE DATE(s.sale_date) BETWEEN ? AND ?
GROUP BY m.medicine_id
ORDER BY total_sold DESC
LIMIT 10";

$stmt = $db->prepare($top_medicines_query);
$stmt->execute([$start_date, $end_date]);
$top_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary statistics
$summary_query = "SELECT 
    COUNT(*) as total_sales,
    SUM(final_amount) as total_revenue,
    AVG(final_amount) as avg_sale_value,
    COUNT(DISTINCT customer_id) as unique_customers
FROM sales 
WHERE DATE(sale_date) BETWEEN ? AND ?";

$stmt = $db->prepare($summary_query);
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Prepare data for charts
$sales_labels = array_column($sales_data, 'sale_day');
$sales_totals = array_map(fn($d) => floatval($d['total_revenue']), $sales_data);
$sales_counts = array_map(fn($d) => intval($d['sales_count']), $sales_data);

$top_labels = array_column($top_medicines, 'medicine_name');
$top_sold = array_map(fn($d) => intval($d['total_sold']), $top_medicines);
$top_revenue = array_map(fn($d) => floatval($d['revenue']), $top_medicines);
?>

<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
</div>

<!-- Date Filter -->
<div class="card shadow mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-calendar-range"></i> Date Range Filter</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date"
                    value="<?php echo $start_date; ?>" required>
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date"
                    value="<?php echo $end_date; ?>" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                            Total Sales</div>
                        <div class="h5 mb-0 fw-bold text-gray-800 num">
                            <?php echo $summary['total_sales'] ?? 0; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-receipt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">
                            Total Revenue</div>
                        <div class="h5 mb-0 fw-bold text-gray-800 num">
                            <?php echo formatCurrency($summary['total_revenue'] ?? 0); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">
                            Average Sale</div>
                        <div class="h5 mb-0 fw-bold text-gray-800 num">
                            <?php echo formatCurrency($summary['avg_sale_value'] ?? 0); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-graph-up fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                            Unique Customers</div>
                        <div class="h5 mb-0 fw-bold text-gray-800 num">
                            <?php echo $summary['unique_customers'] ?? 0; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sales Trend -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Sales Trend (<?php echo formatDate($start_date); ?> to <?php echo formatDate($end_date); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($sales_data)): ?>
                    <p class="text-muted">No sales data available for the selected period.</p>
                <?php else: ?>
                    <canvas id="salesTrendChart" height="120" class="mb-3"></canvas>
                    <div class="table-toolbar">
                        <div class="text-muted small">Filtered by date range above</div>
                        <div class="flex-grow-1"></div>
                        <div class="table-actions">
                            <button class="btn btn-outline-primary btn-sm" id="exportSalesTrendCsv">
                                <i class="bi bi-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped" id="salesTrendTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Sales Count</th>
                                    <th>Total Revenue</th>
                                    <th>Average Sale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_data as $data): ?>
                                    <tr>
                                        <td><?php echo formatDate($data['sale_day']); ?></td>
                                        <td class="num"><?php echo $data['sales_count']; ?></td>
                                        <td class="num"><?php echo formatCurrency($data['total_revenue']); ?></td>
                                        <td class="num"><?php echo formatCurrency($data['avg_sale']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Medicines -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Top Selling Medicines</h5>
            </div>
            <div class="card-body">
                <?php if (empty($top_medicines)): ?>
                    <p class="text-muted">No sales data available for the selected period.</p>
                <?php else: ?>
                    <canvas id="topMedicinesChart" height="120" class="mb-3"></canvas>
                    <div class="table-toolbar">
                        <div class="text-muted small">Top 10 by quantity sold</div>
                        <div class="flex-grow-1"></div>
                        <div class="table-actions">
                            <button class="btn btn-outline-primary btn-sm" id="exportTopMedicinesCsv">
                                <i class="bi bi-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped" id="topMedicinesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Medicine</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_medicines as $index => $medicine): ?>
                                    <tr>
                                        <td class="num"><?php echo $index + 1; ?></td>
                                        <td><?php echo $medicine['medicine_name']; ?></td>
                                        <td class="num"><?php echo $medicine['total_sold']; ?></td>
                                        <td class="num"><?php echo formatCurrency($medicine['revenue']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="card shadow">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-download"></i> Export Reports</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <button type="button" class="btn btn-outline-primary w-100" id="exportSummaryCsv">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Summary
                </button>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-outline-success w-100" id="exportSalesTrendCsvCard">
                    <i class="bi bi-graph-up"></i> Export Sales Trend
                </button>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-outline-info w-100" id="exportTopMedicinesCsvCard">
                    <i class="bi bi-capsule"></i> Export Top Medicines
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Charts using Chart.js from CDN
    const salesLabels = <?php echo json_encode($sales_labels); ?>;
    const salesTotals = <?php echo json_encode($sales_totals); ?>;
    const salesCounts = <?php echo json_encode($sales_counts); ?>;
    const topLabels = <?php echo json_encode($top_labels); ?>;
    const topSold = <?php echo json_encode($top_sold); ?>;

    function renderCharts() {
        if (typeof Chart === 'undefined') return;

        const ctxTrend = document.getElementById('salesTrendChart');
        if (ctxTrend) {
            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: salesLabels,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: salesTotals,
                            borderColor: '#1fb3d5',
                            backgroundColor: 'rgba(31,179,213,0.15)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Sales Count',
                            data: salesCounts,
                            borderColor: '#7c3aed',
                            backgroundColor: 'rgba(124,58,237,0.15)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: {
                            position: 'left',
                            ticks: { color: '#e8edfc' }
                        },
                        y1: {
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { color: '#e8edfc' }
                        },
                        x: { ticks: { color: '#e8edfc' } }
                    },
                    plugins: {
                        legend: { labels: { color: '#e8edfc' } },
                        tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.formattedValue}` } }
                    }
                }
            });
        }

        const ctxTop = document.getElementById('topMedicinesChart');
        if (ctxTop) {
            new Chart(ctxTop, {
                type: 'bar',
                data: {
                    labels: topLabels,
                    datasets: [
                        {
                            label: 'Units Sold',
                            data: topSold,
                            backgroundColor: 'rgba(124,58,237,0.4)',
                            borderColor: '#7c3aed',
                            borderWidth: 1.5
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    scales: {
                        x: { ticks: { color: '#e8edfc' } },
                        y: { ticks: { color: '#e8edfc' } }
                    },
                    plugins: {
                        legend: { labels: { color: '#e8edfc' } },
                        tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.parsed.x}` } }
                    }
                }
            });
        }
    }

    // Load Chart.js once
    (function loadChartJs() {
        if (window.Chart) {
            renderCharts();
            return;
        }
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
        s.onload = renderCharts;
        document.head.appendChild(s);
    })();

    // Export helpers for reports tables
    function exportTableCsv(tableId, filename, headers) {
        const rows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`));
        const data = rows.map(r => Array.from(r.querySelectorAll('td')).map(c => `"${c.innerText.replace(/"/g, '""')}"`).join(','));
        const csv = [headers.join(','), ...data].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }

    const salesBtn = document.getElementById('exportSalesTrendCsv');
    const salesBtnCard = document.getElementById('exportSalesTrendCsvCard');
    [salesBtn, salesBtnCard].forEach(btn => {
        if (btn) {
            btn.addEventListener('click', () => {
                exportTableCsv('salesTrendTable', 'sales_trend.csv', ['Date', 'Sales Count', 'Total Revenue', 'Average Sale']);
            });
        }
    });

    const topBtn = document.getElementById('exportTopMedicinesCsv');
    const topBtnCard = document.getElementById('exportTopMedicinesCsvCard');
    [topBtn, topBtnCard].forEach(btn => {
        if (btn) {
            btn.addEventListener('click', () => {
                exportTableCsv('topMedicinesTable', 'top_medicines.csv', ['#', 'Medicine', 'Sold', 'Revenue']);
            });
        }
    });

    const summaryBtn = document.getElementById('exportSummaryCsv');
    if (summaryBtn) {
        summaryBtn.addEventListener('click', () => {
            const headers = ['Metric', 'Value'];
            const rows = [
                ['Total Sales', "<?php echo $summary['total_sales'] ?? 0; ?>"],
                ['Total Revenue', "<?php echo formatCurrency($summary['total_revenue'] ?? 0); ?>"],
                ['Average Sale', "<?php echo formatCurrency($summary['avg_sale_value'] ?? 0); ?>"],
                ['Unique Customers', "<?php echo $summary['unique_customers'] ?? 0; ?>"],
                ['Range From', "<?php echo $start_date; ?>"],
                ['Range To', "<?php echo $end_date; ?>"]
            ];
            const csv = [headers.join(','), ...rows.map(r => r.map(c => `"${c}"`).join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'summary.csv';
            link.click();
        });
    }
</script>

<?php include '../../includes/footer.php'; ?>
