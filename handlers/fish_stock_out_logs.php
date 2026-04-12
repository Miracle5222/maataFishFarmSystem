<?php
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$fishId = isset($_GET['fish_id']) ? (int) $_GET['fish_id'] : 0;
if ($fishId <= 0) {
    echo json_encode(['ok' => 0, 'msg' => 'Missing fish ID']);
    exit;
}

$stmt = $conn->prepare('SELECT quantity, reason, DATE_FORMAT(created_at, "%b %e, %Y %l:%i %p") AS created_at FROM fish_stock_out_logs WHERE fish_id = ? ORDER BY created_at DESC LIMIT 50');
if (!$stmt) {
    echo json_encode(['ok' => 0, 'msg' => 'Prepare failed']);
    exit;
}
$stmt->bind_param('i', $fishId);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

echo json_encode(['ok' => 1, 'rows' => $rows]);
