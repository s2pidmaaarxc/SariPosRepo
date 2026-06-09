<?php
//  cashier/pos_handler.php — POS Checkout AJAX Handler (too lazy to do it again when something is needed)
//  Sari-Sari Store POS System
require_once '../config.php';
requireCashier();

$user   = getCurrentUser();
$action = $_POST['action'] ?? '';

if ($action !== 'checkout') {
    jsonResponse(false, 'Invalid action.');
}

// Parse cart
$cartRaw = $_POST['cart'] ?? '';
$cart    = json_decode($cartRaw, true);

if (!$cart || count($cart) === 0) {
    jsonResponse(false, 'Cart is empty.');
}

$db = getDB();

try {
    $db -> beginTransaction();

    // Validate stock & compute total
    $total = 0;
    $items = [];

    foreach ($cart as $product_id => $item) {
        $pid = (int)$product_id;
        $qty = (int)$item['qty'];

        // Get current price & stock from DB (para accurate syempre)
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

        $items[] = [
            'product_id' => $pid,
            'name'       => $product['name'],
            'qty'        => $qty,
            'unit_price' => $product['price'],
            'subtotal'   => $subtotal,
        ];
    }

    // Insert order
    $stmt = $db -> prepare("
        INSERT INTO orders (cashier_id, order_date, total_amount, status)
        VALUES (?, NOW(), ?, 'completed')
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

    // Deduct stock
    $stmt = $db -> prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
    foreach ($items as $item) {
        $stmt->execute([$item['qty'], $item['product_id']]);
    }

    // Generate receipt number
    $receipt_number = 'RCP-' . date('Y') . '-' . str_pad($order_id, 4, '0', STR_PAD_LEFT);

    $stmt = $db->prepare("
        INSERT INTO receipts (order_id, receipt_number, issued_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$order_id, $receipt_number]);

    $db->commit();

    jsonResponse(true, 'Order processed successfully.', [
        'order_id'       => $order_id,
        'receipt_number' => $receipt_number,
        'total'          => $total,
    ]);

} catch (Exception $e) {
    $db -> rollBack();
    jsonResponse(false, 'Transaction failed. Please try again.');
}
