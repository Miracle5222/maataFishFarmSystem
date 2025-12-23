<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../auth_admin.php';

header('Content-Type: application/json');

$ok = false;
$data = null;
$items = [];
$msg = '';

try {
    if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
        throw new Exception('Invalid order ID');
    }

    $order_id = (int)$_GET['order_id'];

    // Get order details
    $stmt = $conn->prepare('SELECT o.id, o.order_number, c.first_name, c.last_name, c.email, o.total_amount, o.status, o.order_date, o.pickup_date FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ?');
    if (!$stmt) {
        throw new Exception('Database error');
    }

    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Get order items
    $itemStmt = $conn->prepare('
        SELECT oi.quantity, oi.unit_price, oi.subtotal, oi.product_id, p.name AS product_name 
        FROM order_items oi 
        LEFT JOIN products p ON p.id = oi.product_id 
        WHERE oi.order_id = ?
    ');
    if ($itemStmt) {
        $itemStmt->bind_param('i', $order_id);
        $itemStmt->execute();
        $itemRes = $itemStmt->get_result();
        while ($itemRow = $itemRes->fetch_assoc()) {
            $itemName = $itemRow['product_name'] ?: ('Item #' . $itemRow['product_id']);
            $items[] = [
                'item_name' => $itemName,
                'quantity' => $itemRow['quantity'],
                'unit_price' => $itemRow['unit_price'],
                'subtotal' => $itemRow['subtotal']
            ];
        }
        $itemStmt->close();
    }

    $data = [
        'order_number' => $order['order_number'],
        'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
        'customer_email' => $order['email'],
        'total_amount' => $order['total_amount'],
        'status' => $order['status'],
        'order_date' => date('M d, Y H:i', strtotime($order['order_date'])),
        'pickup_date' => $order['pickup_date']
    ];

    $ok = true;

} catch (Exception $e) {
    $msg = $e->getMessage();
}

echo json_encode(['ok' => $ok ? 1 : 0, 'data' => $data, 'items' => $items, 'msg' => $msg]);
?>
