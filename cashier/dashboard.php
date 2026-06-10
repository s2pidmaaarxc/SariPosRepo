<?php
//  cashier/dashboard.php — Cashier Dashboard
//  Sari-Sari Store POS System

require_once __DIR__ . '/../config.php';
requireCashier();

$user   = getCurrentUser();
$db     = getDB();
$today  = date('Y-m-d');
$uid    = (int)$user['user_id'];

// Today's stats
$stmt = $db -> prepare("
    SELECT
        COUNT(*)                                                AS total_orders,
        COALESCE(SUM(total_amount), 0)                         AS total_sales,
        COALESCE(AVG(total_amount), 0)                         AS avg_order,
        SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END)    AS cancelled
    FROM orders
    WHERE DATE(order_date) = ? AND cashier_id = ?
");
$stmt -> execute([$today, $uid]);
$s = $stmt -> fetch();


$my_orders    = (int)   $s['total_orders'];
$my_sales     = (float) $s['total_sales'];
$my_avg       = (float) $s['avg_order'];
$my_cancelled = (int)   $s['cancelled'];

// Hourly sales today
$stmt = $db -> prepare("
    SELECT HOUR(order_date) AS hr, COALESCE(SUM(total_amount), 0) AS sales
    FROM orders
    WHERE DATE(order_date) = ? AND cashier_id = ? AND status = 'completed'
    GROUP BY HOUR(order_date)
    ORDER BY hr
");
$stmt -> execute([$today, $uid]);
$hourly_labels = [];
$hourly_data   = [];
foreach ($stmt -> fetchAll() as $row) {
    $h = (int)$row['hr'];
    $hourly_labels[] = (($h % 12) ?: 12) . ($h >= 12 ? 'PM' : 'AM');
    $hourly_data[]   = (float)$row['sales'];
}

// fetch top 5 products today
$stmt = $db -> prepare("
    SELECT p.name, SUM(oi.quantity) AS qty_sold
    FROM order_items oi
    JOIN orders   o ON oi.order_id   = o.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE DATE(o.order_date) = ? AND o.cashier_id = ? AND o.status = 'completed'
    GROUP BY p.name
    ORDER BY qty_sold DESC
    LIMIT 5
");
$stmt -> execute([$today, $uid]);
$top_names = [];
$top_qty   = [];
foreach ($stmt->fetchAll() as $row) {
    $top_names[] = $row['name'];
    $top_qty[]   = (int)$row['qty_sold'];
}

// fetch last 7 days sales
$stmt = $db -> prepare("
    SELECT DATE(order_date) AS sale_date, COALESCE(SUM(total_amount), 0) AS daily_sales
    FROM orders
    WHERE order_date >= DATE_SUB(?, INTERVAL 6 DAY)
      AND cashier_id = ? AND status = 'completed'
    GROUP BY DATE(order_date)
    ORDER BY sale_date
");
$stmt -> execute([$today, $uid]);
$week_labels = [];
$week_data   = [];
foreach ($stmt -> fetchAll() as $row) {
    $week_labels[] = date('D d', strtotime($row['sale_date']));
    $week_data[]   = (float)$row['daily_sales'];
}

// fetch recent 8 transactions
$stmt = $db -> prepare("
    SELECT o.order_id, r.receipt_number, o.order_date, o.total_amount, o.status
    FROM orders o
    LEFT JOIN receipts r ON o.order_id = r.order_id
    WHERE o.cashier_id = ?
    ORDER BY o.order_date DESC
    LIMIT 8
");
$stmt -> execute([$uid]);
$recent_rows = $stmt -> fetchAll();   // fetch all into an array

$db = null;

$greeting = date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Cashier</title>
    <link rel="stylesheet" href="/pos_system/assets/css/style.css">
    <link rel="stylesheet" href="/pos_system/assets/css/cDashboard.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="layout">

    <?php include '../includes/sidebar_cashier.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <span class="topbar-title">Dashboard</span>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-clock"></span>
            </div>
        </div>

        <div class="page-content">
            <div class="page-header">
                <h1>Good <?= $greeting ?>, <?= htmlspecialchars($user['username']) ?>!</h1>
                <p>Here's your sales summary for today, <?= date('F j, Y') ?></p>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Today's Sales</div>
                        <div class="stat-value">₱<?= number_format($my_sales, 2) ?></div>
                        <div class="stat-sub"><?= $my_orders ?> transactions</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Completed Orders</div>
                        <div class="stat-value"><?= $my_orders - $my_cancelled ?></div>
                        <div class="stat-sub">out of <?= $my_orders ?> total</div>
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
                        <div class="stat-value">₱<?= number_format($my_avg, 2) ?></div>
                        <div class="stat-sub">per transaction</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Cancelled Orders</div>
                        <div class="stat-value"><?= $my_cancelled ?></div>
                        <div class="stat-sub">today</div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-grid">

                <!-- Weekly Line Chart -->
                <div class="card chart-full">
                    <div class="card-header">
                        <span class="card-title">Sales — Last 7 Days</span>
                        <span class="text-muted" style="font-size:0.78rem">Completed transactions only</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>

                <!-- Hourly Bar Chart -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Hourly Sales Today</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>

                <!-- Top Products Horizontal Bar -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Top 5 Products Today</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="topChart"></canvas>
                    </div>
                </div>

            </div>

            <!-- Recent Transactions Table -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Recent Transactions</span>
                    <a href="/pos_system/cashier/transactions.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <?php
                    if (count($recent_rows) === 0): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🧾</div>
                        <p>No Transaction Found.</p>
                    </div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date & Time</th>
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent_rows as $row): ?>
                            <tr>
                                <td class="text-mono"><?= htmlspecialchars($row['receipt_number'] ?? '—') ?></td>
                                <td class="text-muted"><?= date('M j, Y g:i A', strtotime($row['order_date'])) ?></td>
                                <td class="text-right font-bold">₱<?= number_format($row['total_amount'], 2) ?></td>
                                <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                                <td>
                                    <?php if ($row['status'] === 'completed'): ?>
                                    <a href="/pos_system/cashier/receipt.php?order_id=<?= $row['order_id'] ?>" class="btn btn-outline btn-sm">View Receipt</a>
                                    <?php else: ?>
                                    <span class="text-muted" style="font-size:0.78rem">—</span>
                                    <?php endif; ?>
                                </td>
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

<script src="/pos_system/assets/js/main.js"></script>
<script>
    window.CHART_DATA = {
        weeklyLabels: <?= json_encode($week_labels) ?>,
        weeklyData:   <?= json_encode($week_data) ?>,
        hourlyLabels: <?= json_encode($hourly_labels) ?>,
        hourlyData:   <?= json_encode($hourly_data) ?>,
        topLabels:    <?= json_encode($top_names) ?>,
        topData:      <?= json_encode($top_qty) ?>
    };
</script>
<script src="/pos_system/assets/js/cDashboard.js?v=<?= time(); ?>"></script>
</body>
</html>
