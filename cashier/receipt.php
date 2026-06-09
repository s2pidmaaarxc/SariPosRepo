<?php
//  cashier/receipt.php — View & Print Receipt
//  Sari-Sari Store POS System

require_once __DIR__ . '/../config.php';
requireCashier();

$user     = getCurrentUser();
$db       = getDB();
$order_id = (int)($_GET['order_id'] ?? 0);

if (!$order_id) {
    header('Location: /cashier/transactions.php');
    exit();
}

// Fetch order + receipt
$stmt = $db->prepare("
    SELECT o.order_id, o.order_date, o.total_amount, o.status,
           u.username AS cashier_name,
           r.receipt_number, r.issued_at
    FROM orders o
    JOIN users    u ON o.cashier_id = u.user_id
    JOIN receipts r ON o.order_id   = r.order_id
    WHERE o.order_id = ?
      AND o.cashier_id = ?
    LIMIT 1
");
$stmt->execute([$order_id, $user['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: /cashier/transactions.php');
    exit();
}

// Fetch order items
$stmt = $db->prepare("
    SELECT p.name, oi.quantity, oi.unit_price, oi.subtotal
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
    ORDER BY p.name
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

$db = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?= htmlspecialchars($order['receipt_number']) ?></title>
    <link rel="stylesheet" href="/pos_system/assets/css/style.css">
    <link rel="stylesheet" href="/pos_system/assets/css/receipt.css">
</head>
<body>
<div class="layout">

    <?php include '../includes/sidebar_cashier.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <span class="topbar-title">Receipt</span>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-clock"></span>
            </div>
        </div>

        <div class="page-content">
            <div class="page-header">
                <h1>Receipt Details</h1>
                <p>Order #<?= $order_id ?></p>
            </div>

            <div class="receipt-wrapper">

                <!-- Action Buttons -->
                <div class="receipt-actions">
                    <a href="/pos_system/cashier/transactions.php" class="btn btn-outline">← Back</a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                        Print Receipt
                    </button>
                    <a href="/pos_system/cashier/pos.php" class="btn btn-accent">New Transaction</a>
                </div>

                <!-- Receipt Card -->
                <div class="receipt-card">

                    <!-- Store Header -->
                    <div class="receipt-header">
                        <div class="store-emoji">🏪</div>
                        <h2>Sari-Sari Store</h2>
                        <p>Point of Sale System</p>
                    </div>

                    <!-- Meta Info -->
                    <div class="receipt-meta">
                        <div>
                            <div class="label">Receipt #</div>
                            <div class="value"><?= htmlspecialchars($order['receipt_number']) ?></div>
                        </div>
                        <div style="text-align:right">
                            <div class="label">Date & Time</div>
                            <div class="value"><?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></div>
                        </div>
                    </div>
                    <div class="receipt-meta">
                        <div>
                            <div class="label">Cashier</div>
                            <div class="value"><?= htmlspecialchars($order['cashier_name']) ?></div>
                        </div>
                        <div style="text-align:right">
                            <div class="label">Status</div>
                            <div class="value" style="color:var(--success)">✓ <?= ucfirst($order['status']) ?></div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="receipt-items">
                        <div class="receipt-items-header">
                            <span>Item</span>
                            <span>Qty</span>
                            <span>Price</span>
                            <span>Total</span>
                        </div>
                        <?php
                        foreach ($items as $item):
                        ?>
                        <div class="receipt-item">
                            <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                            <span>x<?= $item['quantity'] ?></span>
                            <span>₱<?= number_format($item['unit_price'], 2) ?></span>
                            <span class="item-subtotal">₱<?= number_format($item['subtotal'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Total -->
                    <div class="receipt-total">
                        <div class="total-row">
                            <span class="label">Items (<?php $item_count = count($items); ?>)</span>
                            <span class="value">₱<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                        <div class="total-row">
                            <span class="label">Discount</span>
                            <span class="value">₱0.00</span>
                        </div>
                        <div class="total-row grand">
                            <span class="label">TOTAL</span>
                            <span class="value">₱<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="receipt-footer">
                        <p class="thank-you">Thank you for buying!! 🙏</p>
                        <p>Please come again</p>
                        <p style="margin-top:8px; font-family:var(--font-mono)"><?= htmlspecialchars($order['receipt_number']) ?></p>
                    </div>

                </div><!-- /receipt-card -->
            </div><!-- /receipt-wrapper -->

        </div>
    </div>
</div>

<script src="/pos_system/assets/js/main.js"></script>
</body>
</html>
