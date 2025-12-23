<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../auth_admin.php';

header('Content-Type: application/json');

$ok = false;
$msg = '';

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Invalid customer ID');
    }

    $id = (int)$_POST['id'];

    // Delete customer
    $stmt = $conn->prepare('DELETE FROM customers WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
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
