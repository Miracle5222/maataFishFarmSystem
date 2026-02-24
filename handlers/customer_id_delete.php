<?php
// handlers/customer_id_delete.php
// Deletes a customer from the database
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../handlers/activity_logger.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    echo json_encode(['ok' => false, 'msg' => 'Not authorized']);
    exit;
}

$ok = false;
$msg = '';

try {
    $customer_id = (int) ($_POST['customer_id'] ?? 0);
    
    if (!$customer_id) {
        throw new Exception('Invalid customer ID');
    }
    
    // Get customer name before deletion
    $stmt = $conn->prepare('SELECT first_name, last_name, email FROM customers WHERE id = ?');
    if (!$stmt) {
        throw new Exception('Database error');
    }
    
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $customer = $res->fetch_assoc();
    $stmt->close();
    
    if (!$customer) {
        throw new Exception('Customer not found');
    }
    
    // Delete the customer
    $deleteStmt = $conn->prepare('DELETE FROM customers WHERE id = ?');
    if (!$deleteStmt) {
        throw new Exception('Database error');
    }
    
    $deleteStmt->bind_param('i', $customer_id);
    $delOk = $deleteStmt->execute();
    $deleteStmt->close();
    
    if (!$delOk) {
        throw new Exception('Failed to delete customer');
    }
    
    // Log the activity
    $customer_name = $customer['first_name'] . ' ' . $customer['last_name'];
    $activity_type = 'customer_deleted';
    $description = "Deleted customer ID {$customer_id} ({$customer_name})";
    $user_id = $_SESSION['user_id'];
    $user_type = 'admin';
    
    $logStmt = $conn->prepare('INSERT INTO activity_logs (user_id, user_type, activity_type, description, created_at) VALUES (?, ?, ?, ?, NOW())');
    if ($logStmt) {
        $logStmt->bind_param('isss', $user_id, $user_type, $activity_type, $description);
        $logStmt->execute();
        $logStmt->close();
    }
    
    $ok = true;
    $msg = 'Customer deleted successfully';
    
} catch (Exception $e) {
    $msg = $e->getMessage();
}

echo json_encode(['ok' => $ok ? 1 : 0, 'msg' => $msg]);
?>
