<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../auth_admin.php';

header('Content-Type: application/json');

$ok = false;
$data = [];
$msg = '';

try {
    if (!isset($_GET['customer_id']) || !is_numeric($_GET['customer_id'])) {
        throw new Exception('Invalid customer ID');
    }

    $customer_id = (int)$_GET['customer_id'];

    // Get orders and resolve item names/types from products or fish_species
    $orders = [];
    $stmt = $conn->prepare(
        "SELECT o.id as order_id, o.order_date, oi.quantity, oi.unit_price, oi.subtotal, oi.product_id, p.name AS product_name, p.category AS product_category, fs.name AS fish_name
         FROM orders o
         LEFT JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN products p ON oi.product_id = p.id
         LEFT JOIN fish_species fs ON oi.product_id = fs.fish_id
         WHERE o.customer_id = ?
         ORDER BY o.order_date DESC"
    );
    if ($stmt) {
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $orders[] = $r;
        }
        $stmt->close();
    }

    if (!empty($orders)) {
        foreach ($orders as $order) {
            $item_name = 'Unknown';
            $item_type = 'menu';
            if (!empty($order['fish_name'])) {
                $item_name = $order['fish_name'];
                $item_type = 'fish';
            } elseif (!empty($order['product_name'])) {
                $item_name = $order['product_name'];
                $item_type = ($order['product_category'] === 'fish') ? 'fish' : 'menu';
            }

            $data[] = [
                'order_id' => $order['order_id'],
                'item_type' => $item_type,
                'item_name' => $item_name,
                'quantity' => isset($order['quantity']) ? (int)$order['quantity'] : 0,
                'total_price' => isset($order['subtotal']) ? (float)$order['subtotal'] : (isset($order['unit_price']) ? (float)$order['unit_price'] : 0),
                'order_date' => $order['order_date'] ? date('M d, Y H:i', strtotime($order['order_date'])) : '-'
            ];
        }
    }

    $ok = true;

} catch (Exception $e) {
    $msg = $e->getMessage();
}

echo json_encode(['ok' => $ok ? 1 : 0, 'data' => $data, 'msg' => $msg]);
?>
