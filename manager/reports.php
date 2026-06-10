<?php
//  manager/reports.php — Sales Reports & Analytics
//  Sari-Sari Store POS System

require_once '../config.php';
requireManager();

$db = getDB();

//  HANDLER — AJAX POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $action = $_POST['action'] ?? '';

    switch ($action) {

        // Fetch sales by date range
        case 'get_sales':
            $date_from = $_POST['date_from'] ?? date('Y-m-01');
            $date_to   = $_POST['date_to']   ?? date('Y-m-d');

            $stmt = $db -> prepare("
                SELECT
                    DATE(o.order_date)          AS sale_date,
                    COUNT(o.order_id)           AS total_orders,
                    SUM(CASE WHEN o.status='completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN o.status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
                    COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_amount END), 0) AS revenue
                FROM orders o
                WHERE DATE(o.order_date) BETWEEN ? AND ?
                GROUP BY DATE(o.order_date)
                ORDER BY sale_date ASC
            ");

            $stmt -> execute([$date_from, $date_to]);
            $rows = $stmt -> fetchAll();

            // Summary totals
            $total_revenue  = array_sum(array_column($rows, 'revenue'));
            $total_orders   = array_sum(array_column($rows, 'total_orders'));
            $total_completed= array_sum(array_column($rows, 'completed'));
            $total_cancelled= array_sum(array_column($rows, 'cancelled'));

            jsonResponse(true, '', [
                'rows'            => $rows,
                'total_revenue'   => $total_revenue,
                'total_orders'    => $total_orders,
                'total_completed' => $total_completed,
                'total_cancelled' => $total_cancelled,
            ]);
            break;

        // Fetch best sellers
        case 'get_best_sellers':
            $date_from = $_POST['date_from'] ?? date('Y-m-01');
            $date_to   = $_POST['date_to']   ?? date('Y-m-d');
            $limit     = (int)($_POST['limit'] ?? 10);

            $stmt = $db -> prepare("
                SELECT
                    p.name,
                    c.category_name,
                    SUM(oi.quantity)  AS total_qty,
                    SUM(oi.subtotal)  AS total_revenue,
                    AVG(oi.unit_price) AS avg_price
                FROM order_items oi
                JOIN orders     o  ON oi.order_id   = o.order_id
                JOIN products   p  ON oi.product_id = p.product_id
                JOIN categories c  ON p.category_id = c.category_id
                WHERE DATE(o.order_date) BETWEEN ? AND ?
                  AND o.status = 'completed'
                GROUP BY p.product_id, p.name, c.category_name
                ORDER BY total_qty DESC
                LIMIT ?
            ");

            // Explicitly bind variables with exact data types
            $stmt->bindValue(1, $date_from, PDO::PARAM_STR);
            $stmt->bindValue(2, $date_to,   PDO::PARAM_STR);
            $stmt->bindValue(3, $limit,     PDO::PARAM_INT);

            $stmt->execute();
            jsonResponse(true, '', ['rows' => $stmt -> fetchAll()]);
            break;

        // Fetch cashier performance
        case 'get_cashier_performance':
            $date_from = $_POST['date_from'] ?? date('Y-m-01');
            $date_to   = $_POST['date_to']   ?? date('Y-m-d');

            $stmt = $db -> prepare("
                SELECT
                    u.username,
                    COUNT(o.order_id)           AS total_orders,
                    SUM(CASE WHEN o.status='completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN o.status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
                    COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_amount END), 0) AS revenue,
                    COALESCE(AVG(CASE WHEN o.status='completed' THEN o.total_amount END), 0) AS avg_order
                FROM orders o
                JOIN users u ON o.cashier_id = u.user_id
                WHERE DATE(o.order_date) BETWEEN ? AND ?
                GROUP BY u.user_id, u.username
                ORDER BY revenue DESC
            ");

            $stmt -> execute([$date_from, $date_to]);
            jsonResponse(true, '', ['rows' => $stmt -> fetchAll()]);
            break;

        // Fetch category revenue
        case 'get_category_revenue':
            $date_from = $_POST['date_from'] ?? date('Y-m-01');
            $date_to   = $_POST['date_to']   ?? date('Y-m-d');

            $stmt = $db -> prepare("
                SELECT
                    c.category_name,
                    SUM(oi.quantity) AS total_qty,
                    SUM(oi.subtotal) AS total_revenue
                FROM order_items oi
                JOIN orders     o  ON oi.order_id   = o.order_id
                JOIN products   p  ON oi.product_id = p.product_id
                JOIN categories c  ON p.category_id = c.category_id
                WHERE DATE(o.order_date) BETWEEN ? AND ?
                  AND o.status = 'completed'
                GROUP BY c.category_id, c.category_name
                ORDER BY total_revenue DESC
            ");

            $stmt -> execute([$date_from, $date_to]);
            jsonResponse(true, '', ['rows' => $stmt -> fetchAll()]);
            break;

        default:
            jsonResponse(false, 'Invalid action.');
    }
}

$user = getCurrentUser();

// Default date range: current month
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to']   ?? date('Y-m-d');

// Quick summary for page load
$stmt = $db -> prepare("
    SELECT
        COALESCE(SUM(CASE WHEN status='completed' THEN total_amount END), 0) AS revenue,
        COUNT(*)                                                               AS total_orders,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END)                  AS completed,
        SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END)                  AS cancelled,
        COALESCE(AVG(CASE WHEN status='completed' THEN total_amount END), 0)  AS avg_order
    FROM orders
    WHERE DATE(order_date) BETWEEN ? AND ?
");
$stmt -> execute([$date_from, $date_to]);
$summary = $stmt -> fetch();

$db = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — Manager</title>
    <link rel="stylesheet" href="/pos_system/assets/css/style.css">
    <link rel="stylesheet" href="/pos_system/assets/css/report.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="layout">
    <?php include '../includes/sidebar_manager.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <span class="topbar-title">Reports</span>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-clock"></span>
                <button class="btn btn-outline btn-sm" onclick="window.print()">🖨️ Print</button>
            </div>
        </div>

        <div class="page-content">
            <div class="page-header">
                <h1>Sales Reports & Analytics</h1>
                <p>Detailed breakdown of store performance</p>
            </div>

            <!-- Date Filter Bar -->
            <div class="date-bar">
                <label>From</label>
                <input type="date" id="date-from" value="<?= $date_from ?>">
                <label>To</label>
                <input type="date" id="date-to" value="<?= $date_to ?>">
                <button class="btn btn-primary btn-sm" onclick="loadAll()">Generate</button>
                <div class="quick-ranges">
                    <button class="quick-btn" onclick="setRange('today')">Today</button>
                    <button class="quick-btn" onclick="setRange('week')">This Week</button>
                    <button class="quick-btn active" onclick="setRange('month')">This Month</button>
                    <button class="quick-btn" onclick="setRange('year')">This Year</button>
                </div>
            </div>

            <!-- Summary Stat Cards -->
            <div class="stats-grid" style="margin-bottom:20px">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value" id="stat-revenue">₱<?= number_format($summary['revenue'], 2) ?></div>
                        <div class="stat-sub" id="stat-period">selected period</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="14" rx="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value" id="stat-orders"><?= number_format($summary['total_orders']) ?></div>
                        <div class="stat-sub" id="stat-completed"><?= $summary['completed'] ?> completed</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Avg. Order Value</div>
                        <div class="stat-value" id="stat-avg">₱<?= number_format($summary['avg_order'], 2) ?></div>
                        <div class="stat-sub">per transaction</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Cancelled</div>
                        <div class="stat-value" id="stat-cancelled"><?= number_format($summary['cancelled']) ?></div>
                        <div class="stat-sub">cancelled orders</div>
                    </div>
                </div>
            </div>

            <!-- Report Tabs -->
            <div class="report-tabs">
                <button class="report-tab active" data-pane="pane-sales"      onclick="switchPane('pane-sales')">📈 Daily Sales</button>
                <button class="report-tab"         data-pane="pane-products"  onclick="switchPane('pane-products')">🏆 Best Sellers</button>
                <button class="report-tab"         data-pane="pane-cashiers"  onclick="switchPane('pane-cashiers')">👥 Cashier Performance</button>
                <button class="report-tab"         data-pane="pane-category"  onclick="switchPane('pane-category')">🏷️ Category Revenue</button>
            </div>

            <!-- Pane 1: Daily Sales -->
            <div class="report-pane active" id="pane-sales">
                <div class="card card-relative" style="margin-bottom:16px">
                    <div class="loading-overlay" id="load-sales">
                        <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                        Loading...
                    </div>
                    <div class="card-header">
                        <span class="card-title">Daily Revenue Trend</span>
                    </div>
                    <div class="chart-box"><canvas id="chart-sales"></canvas></div>
                </div>
                <div class="card card-relative">
                    <div class="loading-overlay" id="load-sales-table">
                        <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                        Loading...
                    </div>
                    <div class="card-header">
                        <span class="card-title">Daily Sales Breakdown</span>
                        <span class="text-muted" id="sales-count" style="font-size:0.78rem"></span>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-right">Total Orders</th>
                                    <th class="text-right">Completed</th>
                                    <th class="text-right">Cancelled</th>
                                    <th class="text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-sales">
                                <tr><td colspan="5" class="text-center text-muted" style="padding:24px">Click Generate to load data.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pane 2: Best Sellers -->
            <div class="report-pane" id="pane-products">
                <div class="grid-2">
                    <div class="card card-relative">
                        <div class="loading-overlay" id="load-bs-chart">
                            <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                            Loading...
                        </div>
                        <div class="card-header"><span class="card-title">Top 5 by Quantity Sold</span></div>
                        <div class="chart-box-sm"><canvas id="chart-bs-qty"></canvas></div>
                    </div>
                    <div class="card card-relative">
                        <div class="loading-overlay" id="load-bs-rev">
                            <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                            Loading...
                        </div>
                        <div class="card-header"><span class="card-title">Top 5 by Revenue</span></div>
                        <div class="chart-box-sm"><canvas id="chart-bs-rev"></canvas></div>
                    </div>
                </div>
                <div class="card card-relative">
                    <div class="loading-overlay" id="load-bs-table">
                        <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                        Loading...
                    </div>
                    <div class="card-header">
                        <span class="card-title">Best Sellers Table</span>
                        <span class="text-muted" style="font-size:0.78rem">Top 10</span>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="text-right">Qty Sold</th>
                                    <th class="text-right">Revenue</th>
                                    <th class="text-right">Avg Price</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-bs">
                                <tr><td colspan="6" class="text-center text-muted" style="padding:24px">Click Generate to load data.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pane 3: Cashier Performance -->
            <div class="report-pane" id="pane-cashiers">
                <div class="grid-2">
                    <div class="card card-relative">
                        <div class="loading-overlay" id="load-cash-chart">
                            <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                            Loading...
                        </div>
                        <div class="card-header"><span class="card-title">Revenue per Cashier</span></div>
                        <div class="chart-box-sm"><canvas id="chart-cash-rev"></canvas></div>
                    </div>
                    <div class="card card-relative">
                        <div class="loading-overlay" id="load-cash-orders">
                            <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                            Loading...
                        </div>
                        <div class="card-header"><span class="card-title">Orders per Cashier</span></div>
                        <div class="chart-box-sm"><canvas id="chart-cash-orders"></canvas></div>
                    </div>
                </div>
                <div class="card card-relative">
                    <div class="loading-overlay" id="load-cash-table">
                        <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                        Loading...
                    </div>
                    <div class="card-header"><span class="card-title">Cashier Performance Table</span></div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cashier</th>
                                    <th class="text-right">Total Orders</th>
                                    <th class="text-right">Completed</th>
                                    <th class="text-right">Cancelled</th>
                                    <th class="text-right">Revenue</th>
                                    <th class="text-right">Avg Order</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-cashiers">
                                <tr><td colspan="6" class="text-center text-muted" style="padding:24px">Click Generate to load data.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pane 4: Category Revenue -->
            <div class="report-pane" id="pane-category">
                <div class="grid-2">
                    <div class="card card-relative">
                        <div class="loading-overlay" id="load-cat-doughnut">
                            <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                            Loading...
                        </div>
                        <div class="card-header"><span class="card-title">Revenue Share by Category</span></div>
                        <div class="chart-box-sm"><canvas id="chart-cat-doughnut"></canvas></div>
                    </div>
                    <div class="card card-relative">
                        <div class="loading-overlay" id="load-cat-bar">
                            <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                            Loading...
                        </div>
                        <div class="card-header"><span class="card-title">Qty Sold per Category</span></div>
                        <div class="chart-box-sm"><canvas id="chart-cat-qty"></canvas></div>
                    </div>
                </div>
                <div class="card card-relative">
                    <div class="loading-overlay" id="load-cat-table">
                        <div class="spinner" style="border-color:rgba(26,107,60,0.2);border-top-color:var(--primary)"></div>
                        Loading...
                    </div>
                    <div class="card-header"><span class="card-title">Category Revenue Table</span></div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-right">Qty Sold</th>
                                    <th class="text-right">Revenue</th>
                                    <th class="text-right">% Share</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-category">
                                <tr><td colspan="4" class="text-center text-muted" style="padding:24px">Click Generate to load data.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="/pos_system/assets/js/main.js"></script>
<script src="/pos_system/assets/js/reports.js"></script>
</body>
</html>
