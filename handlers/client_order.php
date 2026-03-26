<?php
// handlers/client_order.php
// Minimal client-side order handler for fish orders from booking page
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../client/booking.php?error=Invalid request');
    exit;
}

$customer_name = trim($_POST['customer_name'] ?? '');
$customer_contact = trim($_POST['customer_contact'] ?? '');
$product_id = (int) ($_POST['product_id'] ?? 0);
$quantity = (float) ($_POST['quantity'] ?? 0);  // Changed from (int) to (float) to support decimal quantities
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

$insOrder = $conn->prepare('INSERT INTO orders (order_number, customer_id, delivery_date, total_amount, status, notes, is_manual) VALUES (?, ?, ?, ?, ?, ?, 0)');
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
// Extract values into variables for proper binding
$insert_order_id = (int)$order_id;
$insert_product_id = (int)$product_id;
$insert_quantity = (float)$quantity;
$insert_unit_price = (float)$unit_price;
$insert_subtotal = (float)$subtotal;

$qty_str = (string)$insert_quantity;
$price_str = (string)$insert_unit_price;
$subtotal_str = (string)$insert_subtotal;

$insItem = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
if ($insItem) {
    $insItem->bind_param('iisss', $insert_order_id, $insert_product_id, $qty_str, $price_str, $subtotal_str);
    $insItem->execute();
    $insItem->close();
}

// Decrease fish stock
$update_qty = (float)$quantity;
$update_product_id = (int)$product_id;
$update_qty_str = (string)$update_qty;

$updateStock = $conn->prepare('UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE id = ?');
if ($updateStock) {
    $updateStock->bind_param('si', $update_qty_str, $update_product_id);
    $updateStock->execute();
    $updateStock->close();
}

// Log the activity
$user_id = $customer_id;
$user_type = 'customer';
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['role'];
}
logActivity(
    $conn,
    $user_id,
    $user_type,
    'CREATE',
    'order',
    $order_id,
    $order_number,
    "Client order: {$fish['name']} x $quantity kg = ₱" . number_format($total, 2)
);

header('Location: ../client/booking.php?success=Order placed successfully');
exit;
