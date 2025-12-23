<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../auth_admin.php';

header('Content-Type: application/json');

$ok = false;
$msg = '';

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Invalid order ID');
    }

    $id = (int)$_POST['id'];

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
    } else {
        throw new Exception('Order not found');
    }
    $stmt->close();

} catch (Exception $e) {
    $msg = $e->getMessage();
}

echo json_encode(['ok' => $ok ? 1 : 0, 'msg' => $msg]);
?>
