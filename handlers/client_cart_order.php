<?php
// handlers/client_cart_order.php
// Accepts POST: cart (json), customer_name, customer_contact, delivery_date, delivery_address

// Start output buffering to prevent header errors
ob_start();

// Set error reporting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start session
@session_start();

// Block GET requests - return 405 and exit before any processing
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_end_clean();
    exit;
}

// Include database config
@require_once __DIR__ . '/../config/db.php';

// If we can't connect to database, redirect
if (!isset($conn) || !$conn) {
    $_SESSION['cart_error'] = 'Database connection failed. Please try again.';
    header('Location: ../client/cart.php', true, 302);
    ob_end_clean();
    exit;
}

// Check session login
if (empty($_SESSION['client_id'])) {
    $_SESSION['cart_error'] = 'Please login first';
    header('Location: ../client/login.php', true, 302);
    ob_end_clean();
    exit;
}

$cid = (int) $_SESSION['client_id'];

// Get POST data
$cartJson = $_POST['cart'] ?? '[]';
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_contact = trim($_POST['customer_contact'] ?? '');
$pickup_date = $_POST['pickup_date'] ?? null;

// Decode cart
$cart = json_decode($cartJson, true);

if (!is_array($cart) || empty($cart)) {
    $_SESSION['cart_error'] = 'Cart is empty';
    header('Location: ../client/cart.php', true, 302);
    ob_end_clean();
    exit;
}

if ($customer_name === '' || $customer_contact === '') {
    $_SESSION['cart_error'] = 'Missing customer information';
    header('Location: ../client/cart.php', true, 302);
    ob_end_clean();
    exit;
}

// Process cart items
$total = 0.0;
$items = [];

foreach ($cart as $it) {
    $fish_id = (int) ($it['id'] ?? 0);
    $qty = (int) ($it['qty'] ?? 0);
    
    if ($fish_id <= 0 || $qty <= 0) {
        continue;
    }
    
    // Query fish by fish_id
    $fish = null;
    $stmt = $conn->prepare('SELECT fish_id, name, price_per_kg, stock FROM fish_species WHERE fish_id = ? AND status = "available" LIMIT 1');
    
    if ($stmt) {
        $stmt->bind_param('i', $fish_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $unit_price = (float) $row['price_per_kg'];
            $subtotal = $unit_price * $qty;
            $total += $subtotal;
            
            $items[] = [
                'fish_id' => (int) $row['fish_id'],
                'name' => $row['name'],
                'quantity' => $qty,
                'unit_price' => $unit_price,
                'subtotal' => $subtotal
            ];
        }
        $stmt->close();
    }
}

if (empty($items)) {
    $_SESSION['cart_error'] = 'No valid items in cart';
    header('Location: ../client/cart.php', true, 302);
    ob_end_clean();
    exit;
}

// Generate order number
$order_number = 'ORD' . date('YmdHis') . rand(100, 999);

// Insert order
$stmt = $conn->prepare('INSERT INTO orders (order_number, customer_id, pickup_date, total_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?)');

if (!$stmt) {
    $_SESSION['cart_error'] = 'Failed to create order';
    header('Location: ../client/cart.php', true, 302);
    ob_end_clean();
    exit;
}

$status = 'pending';
$notes = 'Online order from website';

$stmt->bind_param('sisdss', $order_number, $cid, $pickup_date, $total, $status, $notes);

if (!$stmt->execute()) {
    $_SESSION['cart_error'] = 'Failed to create order: ' . $stmt->error;
    header('Location: ../client/cart.php', true, 302);
    ob_end_clean();
    $stmt->close();
    exit;
}

$order_id = $stmt->insert_id;
$stmt->close();

if (!$order_id) {
    $_SESSION['cart_error'] = 'Order creation failed';
    header('Location: ../client/cart.php', true, 302);
    ob_end_clean();
    exit;
}

// Insert order items
foreach ($items as $item) {
    $stmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
    
    if ($stmt) {
        $stmt->bind_param('iiidd', $order_id, $item['fish_id'], $item['quantity'], $item['unit_price'], $item['subtotal']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update stock
    $stmt = $conn->prepare('UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE fish_id = ?');
    if ($stmt) {
        $stmt->bind_param('ii', $item['quantity'], $item['fish_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// Clear customer cart
$stmt = $conn->prepare('DELETE FROM carts WHERE customer_id = ?');
if ($stmt) {
    $stmt->bind_param('i', $cid);
    $stmt->execute();
    $stmt->close();
}

// Success
$_SESSION['cart_success'] = 'Order placed successfully! Order #' . $order_number;
header('Location: ../client/orders.php', true, 302);
ob_end_clean();
exit;
