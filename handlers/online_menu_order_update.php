<?php
// handlers/online_menu_order_update.php
// Update online menu order status (from orders table)
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/activity_logger.php';

// Ensure no output before JSON
ob_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid request method']);
    exit;
}

// Verify admin is logged in
if (empty($_SESSION['user_id'])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Not logged in']);
    exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if (!$order_id || !$status) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid parameters']);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'confirmed', 'paid', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid status']);
    exit;
}

// Update order status (only for online menu orders: is_manual = 0)
$stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ? AND is_manual = 0');
if (!$stmt) {
    error_log('[online_menu_order_update] Prepare failed: ' . $conn->error);
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Update prepare failed: ' . $conn->error]);
    exit;
}

if (!$stmt->bind_param('si', $status, $order_id)) {
    error_log('[online_menu_order_update] Bind param failed: ' . $stmt->error);
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Bind param failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}

if (!$stmt->execute()) {
    error_log('[online_menu_order_update] Execute failed: ' . $stmt->error . ' | order_id=' . $order_id . ' | status=' . $status);
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Update failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}

$affected = $stmt->affected_rows;
error_log('[online_menu_order_update] Update executed. Affected rows: ' . $affected . ' | order_id=' . $order_id . ' | status=' . $status);
$stmt->close();

// Log activity for status update if update was successful
if ($affected > 0) {
    // Fetch order number for logging
    $order_stmt = $conn->prepare('SELECT order_number FROM orders WHERE id = ? LIMIT 1');
    if ($order_stmt) {
        $order_stmt->bind_param('i', $order_id);
        $order_stmt->execute();
        $order_res = $order_stmt->get_result();
        $order_row = $order_res->fetch_assoc();
        $order_number = $order_row['order_number'] ?? '';
        $order_stmt->close();
    } else {
        $order_number = '';
    }
    
    $user_id = (int) $_SESSION['user_id'];
    $user_type = $_SESSION['role'] ?? 'admin';
    logActivity($conn, $user_id, $user_type, 'EDIT', 'order', $order_id, $order_number, "Updated online menu order status to {$status}");
    error_log('[online_menu_order_update] Activity logged for order_id=' . $order_id . ' | order_number=' . $order_number);
}

ob_clean();
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'msg' => 'Status updated successfully', 'affected' => $affected]);
