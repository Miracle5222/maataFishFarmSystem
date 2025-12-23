<?php
// Order status update handler with email notification
// Expects POST: order_id, status

require __DIR__ . '/auth_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders_view.php');
    exit;
}

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!$order_id || $status === '') {
    header('Location: orders_view.php?error=invalid');
    exit;
}

require __DIR__ . '/config/db.php';

// Get order and customer details before updating
$stmt = $conn->prepare('SELECT o.id, o.order_number, o.customer_id, o.pickup_date, c.first_name, c.last_name, c.email FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ?');
if (!$stmt) {
    header('Location: orders_view.php?error=stmt');
    exit;
}

$stmt->bind_param('i', $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: orders_view.php?error=notfound');
    exit;
}

// Update order status
$upd = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
if (!$upd) {
    header('Location: orders_view.php?error=stmt');
    exit;
}

$upd->bind_param('si', $status, $order_id);
$ok = $upd->execute();
$upd->close();

if (!$ok) {
    header('Location: orders_view.php?error=update');
    exit;
}

// Send email if status changed to 'confirmed'
if ($status === 'confirmed') {
    $config = require __DIR__ . '/config/email.php';
    require __DIR__ . '/email.php';
    
    $customer_name = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
    $order_number = htmlspecialchars($order['order_number']);
    $pickup_date = htmlspecialchars($order['pickup_date']);
    $customer_email = htmlspecialchars($order['email']);
    
    $subject = 'Order Confirmed - ' . $order_number;
    $body = "
Dear {$customer_name},<br><br>

Your order <strong>{$order_number}</strong> has been <strong>confirmed</strong>!<br><br>

<strong>Order Details:</strong><br>
Order Number: {$order_number}<br>
Pickup Date: {$pickup_date}<br><br>

Thank you for your order. Please visit our farm on your scheduled pickup date to collect your items.<br><br>

Best regards,<br>
<strong>Maata Fish Farm</strong>
    ";
    
    sendEmail($config, $customer_email, $customer_name, $subject, $body);
}

header('Location: orders_view.php?success=updated');
exit;
