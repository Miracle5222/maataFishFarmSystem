<?php
// handlers/order_cancel.php
// Handles order cancellation for pending orders
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$cid = (int) $_SESSION['client_id'];
$action = $_POST['action'] ?? null;
$order_id = (int) ($_POST['order_id'] ?? 0);

if ($action !== 'cancel_order' || !$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Verify order belongs to logged-in customer
$stmt = $conn->prepare('SELECT id, status, total_amount FROM orders WHERE id = ? AND customer_id = ? LIMIT 1');
$stmt->bind_param('ii', $order_id, $cid);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Only allow cancellation of pending orders
if (strtolower($order['status']) !== 'pending') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Only pending orders can be cancelled']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get order items to restore stock
    $items_stmt = $conn->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items_res = $items_stmt->get_result();
    
    // Restore stock for each item
    while ($item = $items_res->fetch_assoc()) {
        $product_id = (int) $item['product_id'];
        $quantity = (int) $item['quantity'];
        
        // Increment stock back
        $stock_stmt = $conn->prepare('UPDATE fish_species SET stock = stock + ? WHERE fish_id = ? OR id = ?');
        $stock_stmt->bind_param('iii', $quantity, $product_id, $product_id);
        $stock_stmt->execute();
        $stock_stmt->close();
    }
    $items_stmt->close();

    // Delete order items
    $delete_items = $conn->prepare('DELETE FROM order_items WHERE order_id = ?');
    $delete_items->bind_param('i', $order_id);
    $delete_items->execute();
    $delete_items->close();

    // Update order status to cancelled
    $cancel_stmt = $conn->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
    $status = 'cancelled';
    $cancel_stmt->bind_param('si', $status, $order_id);
    $cancel_stmt->execute();
    $cancel_stmt->close();

    // Commit transaction
    $conn->commit();

    error_log("[order_cancel] Order {$order_id} cancelled successfully for customer {$cid}");
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    error_log("[order_cancel] Error cancelling order {$order_id}: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to cancel order']);
}

$conn->close();
?>
