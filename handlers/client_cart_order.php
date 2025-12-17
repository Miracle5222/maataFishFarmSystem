<?php
// handlers/client_cart_order.php
// Accepts POST: cart (json), customer_name, customer_contact, delivery_date
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../client/index.php?error=Invalid request');
    exit;
}
$cartJson = $_POST['cart'] ?? '[]';
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_contact = trim($_POST['customer_contact'] ?? '');
$delivery_date = $_POST['delivery_date'] ?: null;

$cart = json_decode($cartJson, true);
if (!is_array($cart) || empty($cart)) {
    header('Location: ../client/index.php?error=Cart is empty');
    exit;
}
if ($customer_name === '' || $customer_contact === '') {
    header('Location: ../client/index.php?error=Missing customer info');
    exit;
}

// find or create customer
$contact = $customer_contact;
$customer_id = null;
$stmt = $conn->prepare('SELECT id, email, phone, first_name, last_name FROM customers WHERE email = ? OR phone = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('ss', $contact, $contact);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $customer_id = $row['id'];
    }
    $stmt->close();
}
if (!$customer_id) {
    $parts = preg_split('/\s+/', $customer_name, 2);
    $first = $parts[0] ?? $customer_name;
    $last = $parts[1] ?? '';
    $ins = $conn->prepare('INSERT INTO customers (first_name, last_name, email, phone, customer_type) VALUES (?, ?, ?, ?, ?)');
    $ctype = 'fish_buyer';
    if ($ins) {
        $ins->bind_param('sssss', $first, $last, $contact, $contact, $ctype);
        $ins->execute();
        $customer_id = $ins->insert_id;
        $ins->close();
    }
}
if (!$customer_id) {
    header('Location: ../client/index.php?error=Failed to create customer');
    exit;
}

// calculate totals and verify availability
$total = 0.0;
$items = [];
foreach ($cart as $it) {
    $pid = (int) ($it['id'] ?? 0);
    $qty = (int) ($it['qty'] ?? 0);
    if ($pid <= 0 || $qty <= 0) continue;
    $stmt = $conn->prepare('SELECT id, name, price, unit, stock_quantity FROM products WHERE id = ? AND category = "fish" AND status = "available" LIMIT 1');
    if (!$stmt) continue;
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        continue;
    }
    $prod = $res->fetch_assoc();
    $stmt->close();
    $unit_price = (float) $prod['price'];
    $subtotal = $unit_price * $qty;
    $total += $subtotal;
    $items[] = ['product_id' => $pid, 'quantity' => $qty, 'unit_price' => $unit_price, 'subtotal' => $subtotal];
}
if (empty($items)) {
    header('Location: ../client/index.php?error=No valid items to order');
    exit;
}

// create order
$order_number = 'ORD' . time() . rand(100, 999);
$notes = 'Client cart order from site';
$status = 'pending';
$ins = $conn->prepare('INSERT INTO orders (order_number, customer_id, delivery_date, total_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?)');
if (!$ins) {
    header('Location: ../client/index.php?error=Failed to create order');
    exit;
}
$ins->bind_param('sisdss', $order_number, $customer_id, $delivery_date, $total, $status, $notes);
$ok = $ins->execute();
$order_id = $ins->insert_id;
$ins->close();
if (!$ok || !$order_id) {
    header('Location: ../client/index.php?error=Failed to create order');
    exit;
}

// insert order items and decrement stock
foreach ($items as $it) {
    $insi = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
    if ($insi) {
        $insi->bind_param('iiidd', $order_id, $it['product_id'], $it['quantity'], $it['unit_price'], $it['subtotal']);
        $insi->execute();
        $insi->close();
    }
    $up = $conn->prepare('UPDATE products SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE id = ?');
    if ($up) {
        $up->bind_param('ii', $it['quantity'], $it['product_id']);
        $up->execute();
        $up->close();
    }
}

// success
header('Location: ../client/index.php?success=Order placed successfully');
exit;
