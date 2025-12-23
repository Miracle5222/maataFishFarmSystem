<?php
// handlers/menu_order_update.php
// Update menu order status
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid request method']);
    exit;
}

// Verify admin is logged in
if (empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Not logged in']);
    exit;
}

$menu_order_id = (int)($_POST['menu_order_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if (!$menu_order_id || !$status) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid parameters']);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'paid', 'unpaid', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid status']);
    exit;
}

// Update menu order status
$stmt = $conn->prepare('UPDATE menu_orders SET status = ?, updated_at = NOW() WHERE id = ?');
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Update prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('si', $status, $menu_order_id);
if (!$stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Update failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}

$affected = $stmt->affected_rows;
$stmt->close();

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'msg' => 'Status updated', 'affected' => $affected]);
exit;
?>
