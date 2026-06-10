<?php
//  cashier/pos.php — Point of Sale Interface
//  Sari-Sari Store POS System

require_once '../config.php';
requireCashier();

$user   = getCurrentUser();
$db     = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $action = $_POST['action'] ?? '';
    

    switch ($action) {

        // CHECKOUT - creates order as PENDING first
        case 'checkout':
            $cartRaw = $_POST['cart'] ?? '';
            $cart    = json_decode($cartRaw, true);

            if (!$cart || count($cart) === 0) {
                jsonResponse(false, 'Cart is empty.');
            }

            try {
                $db -> beginTransaction();

                $total = 0;
                $items = [];

                // Validate stock + compute total using DB prices
                foreach ($cart as $product_id => $item) {
                    $pid = (int)$product_id;
                    $qty = (int)$item['qty'];

                    $stmt = $db -> prepare("SELECT product_id, name, price, stock FROM products WHERE product_id = ?");
                    $stmt -> execute([$pid]);
                    $product = $stmt -> fetch();

                    if (!$product) {
                        $db -> rollBack();
                        jsonResponse(false, "Product not found: {$item['name']}");
                    }
                    if ($product['stock'] < $qty) {
                        $db -> rollBack();
                        jsonResponse(false, "Insufficient stock for: {$product['name']} (available: {$product['stock']})");
                    }

                    $subtotal = $product['price'] * $qty;
                    $total   += $subtotal;
                    $items[]  = [
                        'product_id' => $pid,
                        'name'       => $product['name'],
                        'qty'        => $qty,
                        'unit_price' => $product['price'],
                    ];
                }

                // Insert order as PENDING
                $stmt = $db -> prepare("
                    INSERT INTO orders (cashier_id, order_date, total_amount, status)
                    VALUES (?, NOW(), ?, 'pending')
                ");
                $stmt -> execute([$user['user_id'], $total]);
                $order_id = $db -> lastInsertId();

                // Insert order items
                $stmt = $db -> prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($items as $item) {
                    $stmt -> execute([$order_id, $item['product_id'], $item['qty'], $item['unit_price']]);
                }

                // GENERATE RECEIPT IMMEDIATELY FOR PENDING ORDER
                $receipt_number = 'RCP-' . date('Y') . '-' . str_pad($order_id, 4, '0', STR_PAD_LEFT);
                $stmt = $db -> prepare("
                    INSERT INTO receipts (order_id, receipt_number, issued_at)
                    VALUES (?, ?, NOW())
                ");
                $stmt -> execute([$order_id, $receipt_number]);

                $db -> commit();

                jsonResponse(true, 'Order created.', [
                    'order_id' => $order_id,
                    'total'    => $total,
                ]);

            } catch (Exception $e) {
                $db -> rollBack();
                jsonResponse(false, 'Transaction failed. Please try again.');
            }
            break;

        // CONFIRM - pending to completed, deduct stock 
        case 'confirm_order':
            $order_id = (int)($_POST['order_id'] ?? 0);
            if (!$order_id) jsonResponse(false, 'Invalid order ID.');

            try {
                $db -> beginTransaction();

                // Check if pending
                $stmt = $db -> prepare("SELECT status FROM orders WHERE order_id = ? AND cashier_id = ?");
                $stmt -> execute([$order_id, $user['user_id']]);
                $order = $stmt -> fetch();

                if (!$order) jsonResponse(false, 'Order not found.');
                if ($order['status'] !== 'pending') jsonResponse(false, 'Order is no longer pending.');

                // Re-validate stock before confirming
                $stmt = $db -> prepare("
                    SELECT oi.product_id, oi.quantity, p.stock, p.name
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.product_id
                    WHERE oi.order_id = ?
                ");
                $stmt -> execute([$order_id]);
                $items = $stmt -> fetchAll();

                foreach ($items as $item) {
                    if ($item['stock'] < $item['quantity']) {
                        $db->rollBack();
                        jsonResponse(false, "Insufficient stock for: {$item['name']}");
                    }
                }

                // Deduct stock
                $stmt = $db -> prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
                foreach ($items as $item) {
                    $stmt -> execute([$item['quantity'], $item['product_id']]);
                }

                // Update order to completed
                $stmt = $db -> prepare("UPDATE orders SET status = 'completed' WHERE order_id = ?");
                $stmt->execute([$order_id]);

                // Fetch existing receipt number to pass back to front-end
                $stmt = $db -> prepare("SELECT receipt_number FROM receipts WHERE order_id = ?");
                $stmt -> execute([$order_id]);
                $receipt = $stmt -> fetch();
                $receipt_number = $receipt ? $receipt['receipt_number'] : '';
                $db -> commit();

                jsonResponse(true, 'Order confirmed!', [
                    'order_id'       => $order_id,
                    'receipt_number' => $receipt_number,
                ]);

            } catch (Exception $e) {
                $db -> rollBack();
                jsonResponse(false, 'Failed to confirm order.');
            }
            break;

        // CANCEL - pending to cancelled, no stock change 
        case 'cancel_order':
            $order_id = (int)($_POST['order_id'] ?? 0);
            if (!$order_id) jsonResponse(false, 'Invalid order ID.');

            // Only pending orders can be cancelled from POS
            $stmt = $db -> prepare("
                SELECT status FROM orders
                WHERE order_id = ? AND cashier_id = ?
            ");
            $stmt -> execute([$order_id, $user['user_id']]);
            $order = $stmt -> fetch();

            if (!$order) jsonResponse(false, 'Order not found.');
            if ($order['status'] !== 'pending') jsonResponse(false, 'Only pending orders can be cancelled.');

            $stmt = $db -> prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
            $stmt -> execute([$order_id]);

            jsonResponse(true, 'Order cancelled.');
            break;

        default:
            jsonResponse(false, 'Invalid action.');
    }
}

// Fetch all categories for filter tabs
$cats = $db -> query("SELECT * FROM categories ORDER BY category_name") -> fetchAll();

// Fetch all products with category name
$products = $db -> query("
    SELECT p.*, c.category_name
    FROM products p
    JOIN categories c ON p.category_id = c.category_id
    WHERE p.stock > 0
    ORDER BY c.category_name, p.name
") -> fetchAll();

$resume_cart_json = 'null';
$resume_order_id  = 0;
$resume_total     = 0;

if (isset($_GET['resume']) && (int)$_GET['resume'] > 0) {
    $res_id = (int)$_GET['resume'];
    
    // Check if order belongs to this cashier and is actually pending
    $stmt = $db -> prepare("SELECT order_id, total_amount FROM orders WHERE order_id = ? AND cashier_id = ? AND status = 'pending'");
    $stmt -> execute([$res_id, $user['user_id']]);
    $res_order = $stmt->fetch();
    
    if ($res_order) {
        $resume_order_id = (int)$res_order['order_id'];
        $resume_total    = (float)$res_order['total_amount'];
        
        // Fetch items linked to this pending order
        $stmt = $db -> prepare("
            SELECT oi.product_id, oi.quantity, p.name, p.price 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $stmt -> execute([$resume_order_id]);
        $res_items = $stmt -> fetchAll();
        
        // Rebuild into the array format your JavaScript cart uses: { product_id: { qty, name, price } }
        $formatted_cart = [];
        foreach ($res_items as $item) {
            $formatted_cart[$item['product_id']] = [
                'qty'  => (int)$item['quantity'],
                'name' => $item['name'],
                'price'=> (float)$item['price']
            ];
        }
        $resume_cart_json = json_encode($formatted_cart);
    }
}

$db = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS — SariPOS</title>
    <link rel="stylesheet" href="/pos_system/assets/css/style.css">
    <link rel="stylesheet" href="/pos_system/assets/css/pos.css">
</head>
<body>
<div class="layout">
    <?php include '../includes/sidebar_cashier.php'; ?>

    <div class="main-content" style="overflow:hidden">

        <!-- Topbar -->
        <div class="topbar">
            <span class="topbar-title">Point of Sale</span>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-clock"></span>
            </div>
        </div>

        <!-- POS Layout -->
        <div class="pos-layout">

            <!-- LEFT: Products -->
            <div class="products-panel">
                <!-- Toolbar -->
                <div class="products-toolbar">
                    <div class="search-wrap">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="text" id="search-input" placeholder="Search products...">
                    </div>
                </div>

                <!-- Category Tabs -->
                <div class="cat-tabs">
                    <div class="cat-tab active" data-cat="all">All</div>
                    <?php foreach ($cats as $cat): ?>
                    <div class="cat-tab" data-cat="<?= $cat['category_id'] ?>">
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Products Grid -->
                <div class="products-grid" id="products-grid">
                    <?php
                    $emojis = [
                        'Noodles'       => '🍜',
                        'Drinks'        => '🥤',
                        'Snacks'        => '🍿',
                        'Canned Goods'  => '🥫',
                        'Condiments'    => '🧂',
                        'Personal Care' => '🧴',
                        'Beverages'     => '☕',
                    ];
                    foreach ($products as $p):
                        $emoji = $emojis[$p['category_name']] ?? '📦';
                        $lowStock = $p['stock'] <= 5;
                    ?>
                    <div class="product-card <?= $p['stock'] == 0 ? 'out-of-stock' : '' ?>"
                         data-id="<?= $p['product_id'] ?>"
                         data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
                         data-price="<?= $p['price'] ?>"
                         data-stock="<?= $p['stock'] ?>"
                         data-cat="<?= $p['category_id'] ?>"
                         onclick="addToCart(this)">
                        <?php if ($lowStock && $p['stock'] > 0): ?>
                            <span class="stock-badge">Low</span>
                        <?php endif; ?>
                        <div class="product-emoji"><?= $emoji ?></div>
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="product-cat"><?= htmlspecialchars($p['category_name']) ?></div>
                        <div class="product-price">₱<?= number_format($p['price'], 2) ?></div>
                        <div class="product-stock">Stock: <?= $p['stock'] ?> pcs</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RIGHT: Cart -->
            <div class="cart-panel">
                <div class="cart-header">
                    <div class="cart-title">
                        🛒 Cart
                        <span class="cart-count" id="cart-count">0</span>
                    </div>
                </div>

                <div class="cart-items" id="cart-items">
                    <div class="cart-empty" id="cart-empty">
                        <div class="cart-empty-icon">🛒</div>
                        <p>Cart is empty.<br>Tap a product to add.</p>
                    </div>
                </div>

                <div class="cart-footer">
                    <div class="total-row">
                        <span class="label">Items</span>
                        <span class="value" id="total-items">0</span>
                    </div>
                    <div class="total-row grand">
                        <span class="label">TOTAL</span>
                        <span class="value" id="total-amount">₱0.00</span>
                    </div>
                    <button class="btn-checkout" id="btn-checkout" onclick="openCheckout()" disabled>
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        Checkout
                    </button>
                    <button class="btn-clear" id="btn-clear-bottom" onclick="clearCart()" disabled>
                        Clear Cart
                    </button>
                </div>
            </div>

        </div><!-- /pos-layout -->
    </div><!-- /main-content -->
</div><!-- /layout -->

<!-- Checkout Confirm Modal -->
<div class="modal-overlay" id="modal-checkout">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">🧾 Confirm Order</span>
            <button class="modal-close" onclick="closeModal('modal-checkout')">✕</button>
        </div>
        <div class="modal-body">
            <div id="order-summary-list"></div>
            <div class="modal-total">
                <span class="label">Total</span>
                <span class="value" id="modal-total-amount">₱0.00</span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('modal-checkout')">Cancel</button>
            <button class="btn btn-primary" id="btn-confirm" onclick="processCheckout()">
                <span id="confirm-text">Confirm & Process</span>
                <div id="confirm-spinner" class="spinner" style="display:none; width:16px; height:16px; border-width:2px;"></div>
            </button>
        </div>
    </div>
</div>

<!-- Pending Modal — Confirm or Cancel payment -->
<div class="modal-overlay" id="modal-pending">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">⏳ Awaiting Payment</span>
        </div>
        <div class="modal-body" style="text-align:center; padding:28px 24px">
            <div style="font-size:2.5rem; margin-bottom:12px">💳</div>
            <h3 style="font-size:1rem; font-weight:800; color:var(--text); margin-bottom:6px">
                Order <span id="pending-order-id"></span> — Pending
            </h3>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px">
                Collect payment from the customer.
            </p>
            <div style="
                background:var(--primary-light);
                border-radius:var(--radius);
                padding:14px 20px;
                margin-bottom:4px
            ">
                <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:4px">Amount to Collect</div>
                <div id="pending-total" style="font-size:1.8rem; font-weight:800; color:var(--primary); font-family:var(--font-mono)">
                    ₱0.00
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="cancelOrder()">
                ✕ Cancel Order
            </button>
            <button class="btn btn-primary" id="btn-confirm-order" onclick="confirmOrder()">
                <span id="confirm-order-text">✓ Confirm Payment</span>
                <div id="confirm-order-spinner" class="spinner" style="display:none; width:16px; height:16px; border-width:2px;"></div>
            </button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal-overlay" id="modal-success">
    <div class="modal success-modal">
        <div class="modal-body">
            <div class="success-icon">✅</div>
            <h3>Order Complete!</h3>
            <p>Receipt Number:</p>
            <div class="receipt-num" id="success-receipt-num">—</div>
            <p style="margin-top:6px; font-size:0.8rem; color:var(--text-muted)">Transaction processed successfully.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="btn-new-order" onclick="resetPOS()">New Order</button>
            <button class="btn btn-primary" id="btn-view-receipt" onclick="viewReceipt()">
                🧾 Print Receipt
            </button>
        </div>
    </div>
</div>

<!-- Resume order -->
<script>
    window.RESUME_ORDER_ID = <?= $resume_order_id ?>;
    window.RESUME_TOTAL    = <?= $resume_total ?>;
    window.RESUME_CART     = <?= $resume_cart_json ?>;
</script>

<script src="/pos_system/assets/js/main.js"></script>
<script src="/pos_system/assets/js/pos.js?v=<?= time(); ?>"></script>
</body>
</html>
