<?php
// handlers/fish_order_delete.php
// Deletes a fish order
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

$fish_order_id = (int) ($_POST['id'] ?? 0);
if ($fish_order_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid fish order id']);
    exit;
}

// Delete order items first
$stmt = $conn->prepare('DELETE FROM fish_order_items WHERE fish_order_id = ?');
if ($stmt) {
    $stmt->bind_param('i', $fish_order_id);
    $stmt->execute();
    $stmt->close();
}

// Delete order
$stmt = $conn->prepare('DELETE FROM fish_orders WHERE id = ?');
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'DB prepare error']);
    exit;
}

$stmt->bind_param('i', $fish_order_id);
if ($stmt->execute()) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Delete failed']);
}

$stmt->close();
$conn->close();
?>