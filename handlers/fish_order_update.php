<?php
// handlers/fish_order_update.php
// Updates fish order status
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Invalid request']);
    exit;
}

$fish_order_id = (int) ($_POST['fish_order_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if ($fish_order_id <= 0 || empty($status)) {
    echo json_encode(['ok' => false, 'error' => 'Missing parameters']);
    exit;
}

$valid_statuses = ['pending', 'paid', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid status']);
    exit;
}

$stmt = $conn->prepare('UPDATE fish_orders SET status = ? WHERE id = ?');
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'DB prepare error']);
    exit;
}

$stmt->bind_param('si', $status, $fish_order_id);
if ($stmt->execute()) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Update failed']);
}

$stmt->close();
$conn->close();
?>