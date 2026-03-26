<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First, delete menu order items
    $delete_items_stmt = $conn->prepare("DELETE FROM menu_order_items WHERE menu_order_id = ?");
    $delete_items_stmt->bind_param('i', $order_id);
    $delete_items_stmt->execute();
    $delete_items_stmt->close();

    // Then delete the menu order
    $delete_order_stmt = $conn->prepare("DELETE FROM menu_orders WHERE id = ?");
    $delete_order_stmt->bind_param('i', $order_id);
    $delete_order_stmt->execute();

    if ($delete_order_stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Order not found or could not be deleted']);
    }

    $delete_order_stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    error_log('Delete menu order error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

$conn->close();
?>