<?php
//  cashier/pos.php — Point of Sale Interface
//  Sari-Sari Store POS System
require_once '../config.php';
requireCashier();

$user = getCurrentUser();
$db   = getDB();

// Fetch all categories for filter tabs
$cats = $db->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

// Fetch all products with category name
$products = $db->query("
    SELECT p.*, c.category_name
    FROM products p
    JOIN categories c ON p.category_id = c.category_id
    WHERE p.stock > 0
    ORDER BY c.category_name, p.name
")->fetchAll();

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

<script src="/pos_system/assets/js/main.js"></script>
<script src="/pos_system/assets/js/pos.js?v=<?= time(); ?>"></script>
</body>
</html>
