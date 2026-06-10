<?php

require_once '../config.php';
requireManager();

$user  = getCurrentUser();
$db  = getDB();
$today = date('Y-m-d');

// Store-wide stats today
$q_store = $db -> prepare("
    SELECT COUNT(*) AS total_orders,
           COALESCE(SUM(total_amount),0) AS total_sales,
           COALESCE(AVG(total_amount),0) AS avg_order,
           SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM orders WHERE DATE(order_date) = ?
");
$q_store -> execute([$today]);
$store = $q_store -> fetch();

// Inventory stats
$q_inv = $db -> query("
    SELECT COUNT(*) AS total_products,
           SUM(CASE WHEN stock < 10 THEN 1 ELSE 0 END) AS low_stock,
           SUM(CASE WHEN stock = 0  THEN 1 ELSE 0 END) AS out_of_stock
    FROM products
");
$inv = $q_inv -> fetch();

// Last 7 days store sales
$q_weekly = $db -> prepare("
    SELECT DATE(order_date) AS d, COALESCE(SUM(total_amount),0) AS sales
    FROM orders
    WHERE order_date >= DATE_SUB(?, INTERVAL 6 DAY) AND status='completed'
    GROUP BY DATE(order_date) ORDER BY d
");
$q_weekly -> execute([$today]);
$week_labels = []; $week_data = [];
while($r=$q_weekly -> fetch()){
    $week_labels[] = date('D d',strtotime($r['d']));
    $week_data[] = (float)$r['sales'];
}

// Per cashier today
$q_cashier = $db -> prepare("
    SELECT u.username, COUNT(o.order_id) AS orders,
           COALESCE(SUM(o.total_amount),0) AS sales
    FROM orders o JOIN users u ON o.cashier_id=u.user_id
    WHERE DATE(o.order_date)=? AND o.status='completed'
    GROUP BY u.username ORDER BY sales DESC
");
$q_cashier -> execute([$today]);
$cashier_names = []; $cashier_sales = []; $cashier_orders = [];
while($r=$q_cashier -> fetch()){
    $cashier_names[] = $r['username'];
    $cashier_sales[] = (float)$r['sales'];
    $cashier_orders[] = (int)$r['orders'];
}

// Category revenue today
$q_cat = $db -> prepare("
    SELECT c.category_name, COALESCE(SUM(oi.subtotal),0) AS revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id=o.order_id
    JOIN products p ON oi.product_id=p.product_id
    JOIN categories c ON p.category_id=c.category_id
    WHERE DATE(o.order_date)=? AND o.status='completed'
    GROUP BY c.category_name ORDER BY revenue DESC
");
$q_cat -> execute([$today]);
$cat_labels = []; $cat_data = [];
while($r=$q_cat -> fetch()){
    $cat_labels[] = $r['category_name'];
    $cat_data[] = (float)$r['revenue'];
}

// Weekly per cashier trend
$seven_days = [];
for($i=6; $i >= 0; $i--) $seven_days[] = date('Y-m-d',strtotime("-{$i} days", strtotime($today)));
$seven_labels = array_map(fn($d) => date('D d',strtotime($d)), $seven_days);

$q_cw = $db -> prepare("
    SELECT u.username, DATE(o.order_date) AS d, COALESCE(SUM(o.total_amount),0) AS sales
    FROM orders o JOIN users u ON o.cashier_id=u.user_id
    WHERE o.order_date >= DATE_SUB(?, INTERVAL 6 DAY) AND o.status='completed'
    GROUP BY u.username, DATE(o.order_date) ORDER BY u.username, d
");
$q_cw -> execute([$today]);
$cw=[];
while($r = $q_cw -> fetch()) $cw[$r['username']][$r['d']] = (float)$r['sales'];
$palette_php = ['#1a6b3c','#f5a623','#3182ce','#e53e3e','#805ad5','#38b2ac'];
$cashier_datasets = []; $pi = 0;
foreach($cw as $cn => $days){
    $ds = [];
    foreach($seven_days as $d) $ds[] = $days[$d] ?? 0;
    $color = $palette_php[$pi%count($palette_php)];
    $cashier_datasets[] = ['label' => $cn,'data' => $ds,'borderColor' => $color,
        'backgroundColor' => $color.'18','borderWidth' => 2,
        'fill' => false,'tension' => 0.4,'pointRadius' => 3];
    $pi++;
}

// Top 5 products all time
$q_top = $db -> query("
    SELECT p.name, SUM(oi.quantity) AS qty
    FROM order_items oi JOIN products p ON oi.product_id=p.product_id
    GROUP BY p.name ORDER BY qty DESC LIMIT 5
");
$top_names = []; $top_qty = [];
while($r = $q_top -> fetch()){ $top_names[] = $r['name']; $top_qty[] = (int)$r['qty']; }

// Low stock
$q_low = $db -> query("
    SELECT p.name, c.category_name, p.stock, p.price
    FROM products p JOIN categories c ON p.category_id=c.category_id
    WHERE p.stock < 10 ORDER BY p.stock ASC LIMIT 8
");

$db = null;
$greeting = date('H')<12?'Morning':(date('H')<18?'Afternoon':'Evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Dashboard — Manager</title>
    <link rel="stylesheet" href="/pos_system/assets/css/style.css">
    <link rel="stylesheet" href="/pos_system/assets/css/mDashboard.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="layout">

<?php include '../includes/sidebar_manager.php'; ?>

<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">Manager Dashboard</span>
        <div class="topbar-right">
            <span class="topbar-date" id="topbar-clock"></span>
            <a href="/pos_system/manager/reports.php" class="btn btn-outline btn-sm">Full Reports</a>
        </div>
    </div>
    <div class="page-content" id="dashboard-data" data-seven-labels='<?= htmlspecialchars(json_encode($seven_labels), ENT_QUOTES, 'UTF-8') ?>'
     data-cashier-datasets='<?= json_encode($cashier_datasets) ?>' data-cat-labels='<?= json_encode($cat_labels) ?>'
     data-cat-data='<?= json_encode($cat_data) ?>' data-week-labels='<?= json_encode($week_labels) ?>'
     data-week-data='<?= json_encode($week_data) ?>' data-cashier-names='<?= json_encode($cashier_names) ?>'
     data-cashier-sales='<?= json_encode($cashier_sales) ?>' data-top-names='<?= json_encode($top_names) ?>'
     data-top-qty='<?= json_encode($top_qty) ?>'>

        <div class="page-header">
            <h1>Good <?=$greeting?>, <?=htmlspecialchars($user['username'])?>!</h1>
            <p>Store overview for today, <?=date('F j, Y')?></p>
        </div>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="stat-label">Store Sales Today</div>
                    <div class="stat-value">₱<?=number_format($store['total_sales'],2)?></div>
                    <div class="stat-sub"><?=$store['total_orders']?> transactions</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div>
                    <div class="stat-label">Avg. Order Value</div>
                    <div class="stat-value">₱<?=number_format($store['avg_order'],2)?></div>
                    <div class="stat-sub">per transaction today</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon <?=(int)$inv['low_stock']>0?'red':'blue'?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <div>
                    <div class="stat-label">Low Stock Items</div>
                    <div class="stat-value"><?=$inv['low_stock']?></div>
                    <div class="stat-sub"><?=$inv['out_of_stock']?> out of stock</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div>
                    <div class="stat-label">Cancelled Today</div>
                    <div class="stat-value"><?=$store['cancelled']?></div>
                    <div class="stat-sub">out of <?=$store['total_orders']?> orders</div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1: Weekly cashier trend + Category doughnut -->
        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Weekly Sales — Per Cashier</span>
                    <span class="text-muted" style="font-size:.75rem">Last 7 days</span>
                </div>
                <div class="chart-tall"><canvas id="cashierWeekChart"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Revenue by Category</span>
                    <span class="text-muted" style="font-size:.75rem">Today</span>
                </div>
                <div class="chart-tall"><canvas id="categoryChart"></canvas></div>
            </div>
        </div>

        <!-- Charts Row 2: Store weekly + Cashier today + Top products -->
        <div class="grid-3">
            <div class="card">
                <div class="card-header"><span class="card-title">Store Sales — 7 Days</span></div>
                <div class="chart-box"><canvas id="weeklyChart"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><span class="card-title">Cashier Sales Today</span></div>
                <div class="chart-box"><canvas id="cashierTodayChart"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><span class="card-title">Top Products (All Time)</span></div>
                <div class="chart-box"><canvas id="topChart"></canvas></div>
            </div>
        </div>

        <!-- Tables Row: Low stock + Cashier breakdown -->
        <div class="grid-2b">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">⚠️ Low Stock Alert</span>
                    <a href="/pos_system/manager/products.php" class="btn btn-outline btn-sm">Manage</a>
                </div>
                <?php 
                    $low_rows = $q_low -> fetchAll();
                    if(count($low_rows) === 0): ?>
                <div class="empty-state"><div class="empty-state-icon">✅</div><p>All products have sufficient stock.</p></div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Product</th><th>Category</th><th class="text-right">Stock</th><th class="text-right">Price</th></tr></thead>
                        <tbody>
                        <?php foreach($low_rows as $r): ?>
                        <tr>
                            <td class="font-bold"><?=htmlspecialchars($r['name'])?></td>
                            <td class="text-muted"><?=htmlspecialchars($r['category_name'])?></td>
                            <td class="text-right <?=$r['stock']==0?'stock-critical':'stock-low'?>"><?=$r['stock']==0?'OUT':$r['stock']?></td>
                            <td class="text-right text-mono">₱<?=number_format($r['price'],2)?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Cashier Breakdown Today</span>
                    <a href="/pos_system/manager/reports.php" class="btn btn-outline btn-sm">Details</a>
                </div>
                <?php if(empty($cashier_names)): ?>
                <div class="empty-state"><div class="empty-state-icon">👥</div><p>No transactions yet today.</p></div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Cashier</th><th class="text-right">Orders</th><th class="text-right">Sales</th></tr></thead>
                        <tbody>
                        <?php foreach($cashier_names as $i => $cn): ?>
                        <tr>
                            <td class="font-bold"><?=htmlspecialchars($cn)?></td>
                            <td class="text-right text-mono"><?=$cashier_orders[$i]?></td>
                            <td class="text-right font-bold text-primary">₱<?=number_format($cashier_sales[$i],2)?></td>
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
</div>
<script src="/pos_system/assets/js/main.js"></script>
<script src="/pos_system/assets/js/mDashboard.js"></script>
</body>
</html>
