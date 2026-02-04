<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/activity_logger.php';

header('Content-Type: application/json');

$ok = false;
$msg = '';

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Invalid order ID');
    }

    $id = (int)$_POST['id'];

    // Get order details before deleting
    $getOrder = $conn->prepare('SELECT order_number FROM orders WHERE id = ?');
    $order_number = '';
    if ($getOrder) {
        $getOrder->bind_param('i', $id);
        $getOrder->execute();
        $getResult = $getOrder->get_result();
        if ($row = $getResult->fetch_assoc()) {
            $order_number = $row['order_number'];
        }
        $getOrder->close();
    }

    // Delete order items first
    $delItems = $conn->prepare('DELETE FROM order_items WHERE order_id = ?');
    if ($delItems) {
        $delItems->bind_param('i', $id);
        $delItems->execute();
        $delItems->close();
    }

    // Delete order
    $stmt = $conn->prepare('DELETE FROM orders WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $ok = true;
        $msg = 'Order deleted successfully';
        // Log the activity
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['role'] ?? 'staff';
        logActivity(
            $conn,
            $user_id,
            $user_type,
            'DELETE',
            'order',
            $id,
            $order_number,
            "Deleted order: $order_number"
        );
    } else {
        throw new Exception('Order not found');
    }
    $stmt->close();

} catch (Exception $e) {
    $msg = $e->getMessage();
}

echo json_encode(['ok' => $ok ? 1 : 0, 'msg' => $msg]);
?>
