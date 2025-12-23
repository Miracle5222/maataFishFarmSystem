<?php
// handlers/client_order.php
// Minimal client-side order handler for fish orders from booking page
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../client/booking.php?error=Invalid request');
    exit;
}

$customer_name = trim($_POST['customer_name'] ?? '');
$customer_contact = trim($_POST['customer_contact'] ?? '');
$product_id = (int) ($_POST['product_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 0);
$delivery_date = $_POST['delivery_date'] ?? null;

if ($customer_name === '' || $customer_contact === '' || $product_id <= 0 || $quantity <= 0) {
    header('Location: ../client/booking.php?error=Please complete the order form');
    exit;
}

// Try to find customer by email or phone
$customer_id = null;
$contact = $customer_contact;
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
    // Insert a simple customer record. Try to split name into first/last
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
    header('Location: ../client/booking.php?error=Failed to create customer record');
    exit;
}

// Get fish info from fish_species table
$stmt = $conn->prepare('SELECT fish_id, name, price_per_kg, stock FROM fish_species WHERE fish_id = ? AND status = "available" LIMIT 1');
if (!$stmt) {
    header('Location: ../client/booking.php?error=Fish not found');
    exit;
}
$stmt->bind_param('i', $product_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    header('Location: ../client/booking.php?error=Fish not available');
    exit;
}
$fish = $res->fetch_assoc();
$stmt->close();

$unit_price = (float) $fish['price_per_kg'];
$subtotal = $unit_price * $quantity;
$total = $subtotal;

// Create order_number simple
$order_number = 'ORD' . time() . rand(100, 999);

$insOrder = $conn->prepare('INSERT INTO orders (order_number, customer_id, delivery_date, total_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?)');
$status = 'pending';
$notes = 'Client order from booking page';
if (!$insOrder) {
    header('Location: ../client/booking.php?error=Failed to create order');
    exit;
}
$insOrder->bind_param('sisdss', $order_number, $customer_id, $delivery_date, $total, $status, $notes);
$ok = $insOrder->execute();
$order_id = $insOrder->insert_id;
$insOrder->close();

if (!$ok || !$order_id) {
    header('Location: ../client/booking.php?error=Failed to create order');
    exit;
}

// Insert order item
$insItem = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
if ($insItem) {
    $insItem->bind_param('iiidd', $order_id, $product_id, $quantity, $unit_price, $subtotal);
    $insItem->execute();
    $insItem->close();
}

// Decrease fish stock
$updateStock = $conn->prepare('UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE id = ?');
if ($updateStock) {
    $updateStock->bind_param('ii', $quantity, $product_id);
    $updateStock->execute();
    $updateStock->close();
}

header('Location: ../client/booking.php?success=Order placed successfully');
exit;
