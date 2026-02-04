<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/activity_logger.php';

header('Content-Type: application/json');

$ok = false;
$msg = '';

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Invalid customer ID');
    }

    $id = (int)$_POST['id'];

    // Get customer name before deletion for logging
    $getCustomer = $conn->prepare('SELECT name FROM customers WHERE id = ?');
    if ($getCustomer) {
        $getCustomer->bind_param('i', $id);
        $getCustomer->execute();
        $result = $getCustomer->get_result();
        $customer = $result->fetch_assoc();
        $customer_name = $customer['name'] ?? 'Unknown Customer';
        $getCustomer->close();
    }

    // Delete customer
    $stmt = $conn->prepare('DELETE FROM customers WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Log the activity
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['role'] ?? 'staff';
        
        logActivity(
            $conn,
            $user_id,
            $user_type,
            'DELETE',
            'customer',
            $id,
            $customer_name,
            "Deleted customer: $customer_name"
        );
        
        $ok = true;
        $msg = 'Customer deleted successfully';
    } else {
        throw new Exception('Customer not found');
    }
    $stmt->close();

} catch (Exception $e) {
    $msg = $e->getMessage();
}

echo json_encode(['ok' => $ok ? 1 : 0, 'msg' => $msg]);
?>
