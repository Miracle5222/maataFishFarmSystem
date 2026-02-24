<?php
// handlers/fish_order_details.php
// Returns fish order details and items for viewing/editing
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$fish_order_id = (int) ($_GET['fish_order_id'] ?? 0);
if ($fish_order_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid fish order id']);
    exit;
}

// Fetch fish order
$stmt = $conn->prepare('SELECT id, order_number, customer_name, customer_contact, total_amount, status, created_at FROM fish_orders WHERE id = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'DB prepare error']);
    exit;
}

$stmt->bind_param('i', $fish_order_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'Fish order not found']);
    $stmt->close();
    exit;
}

$order = $res->fetch_assoc();
$stmt->close();

// Fetch items
$items = [];
$item_stmt = $conn->prepare('SELECT foi.id, foi.quantity, foi.unit_price, foi.subtotal, fs.name as fish_name FROM fish_order_items foi LEFT JOIN fish_species fs ON foi.fish_id = fs.fish_id WHERE foi.fish_order_id = ?');
if ($item_stmt) {
    $item_stmt->bind_param('i', $fish_order_id);
    $item_stmt->execute();
    $item_res = $item_stmt->get_result();
    while ($item = $item_res->fetch_assoc()) {
        $items[] = $item;
    }
    $item_stmt->close();
}

echo json_encode([
    'ok' => true,
    'data' => $order,
    'items' => $items
]);

$conn->close();
?>