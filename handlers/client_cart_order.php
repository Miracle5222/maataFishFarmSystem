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

// Include database config and activity logger
@require_once __DIR__ . '/../config/db.php';
@require_once __DIR__ . '/activity_logger.php';

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

// Convert datetime-local format (2026-02-03T14:30) to MySQL format (2026-02-03 14:30:00)
if ($pickup_date) {
    $pickup_date = str_replace('T', ' ', $pickup_date) . ':00';
}

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
    $item_id = (int) ($it['item_id'] ?? $it['id'] ?? 0);
    $item_type = $it['item_type'] ?? 'fish';
    $qty = (float) ($it['qty'] ?? 0);  // Changed from (int) to (float) to preserve decimals
    
    if ($item_id <= 0 || $qty <= 0) {
        continue;
    }
    
    // Query item
    $stmt = null;
    if ($item_type === 'fish') {
        $stmt = $conn->prepare('SELECT fish_id AS id, name, price_per_kg AS price, stock FROM fish_species WHERE fish_id = ? AND status = "available" LIMIT 1');
    } else {
        $stmt = $conn->prepare('SELECT id, name, price, stock_quantity AS stock FROM products WHERE id = ? AND status = "available" LIMIT 1');
    }
    
    if ($stmt) {
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $unit_price = (float) $row['price'];
            $subtotal = $unit_price * $qty;
            $total += $subtotal;
            
            $items[] = [
                'item_id' => (int) $row['id'],
                'item_type' => $item_type,
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
$stmt = $conn->prepare('INSERT INTO orders (order_number, customer_id, pickup_date, total_amount, status, notes, is_manual) VALUES (?, ?, ?, ?, ?, ?, 0)');

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
    // Extract values into variables for proper binding
    $insert_order_id = (int)$order_id;
    $insert_product_id = (int)$item['item_id'];
    $insert_quantity = (float)$item['quantity'];
    $insert_unit_price = (float)$item['unit_price'];
    $insert_subtotal = (float)$item['subtotal'];
    
    $stmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
    
    if ($stmt) {
        // Use string binding for decimals to ensure proper conversion
        $qty_str = (string)$insert_quantity;
        $price_str = (string)$insert_unit_price;
        $subtotal_str = (string)$insert_subtotal;
        
        $stmt->bind_param('iisss', $insert_order_id, $insert_product_id, $qty_str, $price_str, $subtotal_str);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update stock
    $update_qty = (float)$item['quantity'];
    $update_item_id = (int)$item['item_id'];
    $update_qty_str = (string)$update_qty;
    
    if ($item['item_type'] === 'fish') {
        $stmt = $conn->prepare('UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE fish_id = ?');
    } else {
        $stmt = $conn->prepare('UPDATE products SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE id = ?');
    }
    if ($stmt) {
        $stmt->bind_param('si', $update_qty_str, $update_item_id);
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

// Log activity for order creation
$order_items_summary = '';
$fish_count = 0;
$product_count = 0;
foreach ($items as $item) {
    if ($item['item_type'] === 'fish') {
        $fish_count++;
    } else {
        $product_count++;
    }
}
if ($fish_count > 0) {
    $order_items_summary .= "$fish_count fish species";
}
if ($product_count > 0) {
    if ($fish_count > 0) $order_items_summary .= ", ";
    $order_items_summary .= "$product_count menu items";
}

$description = "Online order #$order_number | Items: $order_items_summary | Total: ₱" . number_format($total, 2) . " | Customer: $customer_name";
logActivity(
    $conn,
    $cid,
    'customer',
    'CREATE',
    'orders',
    $order_id,
    $order_number,
    $description,
    null,
    [
        'order_number' => $order_number,
        'customer_id' => $cid,
        'customer_name' => $customer_name,
        'customer_contact' => $customer_contact,
        'pickup_date' => $pickup_date,
        'total_amount' => $total,
        'items_count' => count($items),
        'fish_count' => $fish_count,
        'product_count' => $product_count
    ]
);

// Success
$_SESSION['cart_success'] = 'Order placed successfully! Order #' . $order_number;
header('Location: ../client/orders.php', true, 302);
ob_end_clean();
exit;
