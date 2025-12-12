<?php
// Simple order status update handler
// Expects POST: order_id, status

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

// include database connection (adjust credentials in config/db.php)
require __DIR__ . '/config/db.php';

// Use a prepared statement to update the order status
$stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
if (!$stmt) {
    header('Location: orders_view.php?error=stmt');
    exit;
}

$stmt->bind_param('si', $status, $order_id);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

if ($ok) {
    header('Location: orders_view.php?success=updated');
} else {
    header('Location: orders_view.php?error=update');
}
exit;
