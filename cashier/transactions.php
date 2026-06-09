<?php
//  cashier/transactions.php — Transaction History
//  Sari-Sari Store POS System

require_once __DIR__ . '/../config.php';
requireCashier();

$user = getCurrentUser();
$db = getDB();
$uid  = (int)$user['user_id'];

// Filters
$filter_status = $_GET['status'] ?? 'all';
$filter_date   = $_GET['date']   ?? '';
$search        = trim($_GET['search'] ?? '');

$params = [$uid];
$where = ["o.cashier_id = ?"];

if ($filter_status !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $filter_status;
}
if ($filter_date) {
    $where[] = "DATE(o.order_date) = ?";
    $params[] = $filter_date;
}
if ($search) {
    $where[] = "r.receipt_number LIKE ?";
    $params[] = "%$search%";
}

$where_sql = implode(' AND ', $where);

// Fetch transactions
$q = $db -> prepare("
    SELECT o.order_id, r.receipt_number, o.order_date,
           o.total_amount, o.status,
           COUNT(oi.item_id) AS item_count
    FROM orders o
    LEFT JOIN receipts    r  ON o.order_id   = r.order_id
    LEFT JOIN order_items oi ON o.order_id   = oi.order_id
    WHERE $where_sql
    GROUP BY o.order_id, r.receipt_number, o.order_date, o.total_amount, o.status
    ORDER BY o.order_date DESC
");
$q -> execute($params);
$transactions = $q->fetchAll();

// Summary counts
$q_summary = $db->prepare("
    SELECT
        COUNT(DISTINCT o.order_id)                                           AS total,
        SUM(CASE WHEN o.status='completed' THEN 1 ELSE 0 END)                AS completed,
        SUM(CASE WHEN o.status='cancelled' THEN 1 ELSE 0 END)                AS cancelled,
        COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_amount END), 0) AS total_sales
    FROM orders o
    LEFT JOIN receipts r ON o.order_id = r.order_id
    WHERE $where_sql
");
$q_summary -> execute($params);
$summary = $q_summary -> fetch();

$db = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions — Cashier</title>
    <link rel="stylesheet" href="/pos_system/assets/css/style.css">
    <link rel="stylesheet" href="/pos_system/assets/css/transactions.css">
</head>
<body>
<div class="layout">

    <?php include '../includes/sidebar_cashier.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <span class="topbar-title">My Transactions</span>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-clock"></span>
            </div>
        </div>

        <div class="page-content">
            <div class="page-header">
                <h1>Transaction History</h1>
                <p>All your processed orders</p>
            </div>

            <!-- Summary Pills -->
            <div class="stats-grid" style="margin-bottom:20px">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Total Sales</div>
                        <div class="stat-value">₱<?= number_format($summary['total_sales'], 2) ?></div>
                        <div class="stat-sub">all time</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Total Transactions</div>
                        <div class="stat-value"><?= number_format($summary['total']) ?></div>
                        <div class="stat-sub">all orders</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Completed</div>
                        <div class="stat-value"><?= number_format($summary['completed']) ?></div>
                        <div class="stat-sub">successful orders</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Cancelled</div>
                        <div class="stat-value"><?= number_format($summary['cancelled']) ?></div>
                        <div class="stat-sub">cancelled orders</div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="card" style="margin-bottom:16px; padding:16px 20px">
                <form method="GET" action="/pos_system/cashier/transactions.php" class="filter-bar">
                    <input type="search" name="search" class="form-control"
                           placeholder="Search receipt #..."
                           value="<?= htmlspecialchars($search) ?>">

                    <select name="status" class="form-control">
                        <option value="all"      <?= $filter_status==='all'       ? 'selected':'' ?>>All Status</option>
                        <option value="completed"<?= $filter_status==='completed' ? 'selected':'' ?>>Completed</option>
                        <option value="cancelled"<?= $filter_status==='cancelled' ? 'selected':'' ?>>Cancelled</option>
                        <option value="pending"  <?= $filter_status==='pending'   ? 'selected':'' ?>>Pending</option>
                    </select>

                    <input type="date" name="date" class="form-control"
                           value="<?= htmlspecialchars($filter_date) ?>">

                    <a href="/pos_system/cashier/transactions.php" class="btn btn-outline btn-sm">Reset</a>
                </form>
            </div>

            <!-- Transactions Table -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Transactions</span>
                    <span class="text-muted" style="font-size:0.8rem"><?= count($transactions) ?> records found</span>
                </div>

                <?php if (count($transactions) === 0): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <p>No Transaction Found.</p>
                    </div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Receipt #</th>
                                <th>Date & Time</th>
                                <th class="text-right">Items</th>
                                <th class="text-right">Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $ctr = 1; foreach ($transactions as $row): ?>
                            <tr>
                                <td class="text-muted"><?= $ctr++ ?></td>
                                <td class="text-mono font-bold"><?= htmlspecialchars($row['receipt_number'] ?? '—') ?></td>
                                <td class="text-muted"><?= date('M j, Y g:i A', strtotime($row['order_date'])) ?></td>
                                <td class="text-right"><?= $row['item_count'] ?> items</td>
                                <td class="text-right font-bold">₱<?= number_format($row['total_amount'], 2) ?></td>
                                <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                                <td>
                                    <?php if ($row['status'] === 'completed'): ?>
                                    <a href="/pos_system/cashier/receipt.php?order_id=<?= $row['order_id'] ?>"
                                       class="btn btn-outline btn-sm">
                                        🧾 Receipt
                                    </a>
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
<script src="/pos_system/assets/js/transactions.js"></script>
</body>
</html>
