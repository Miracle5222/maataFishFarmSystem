<?php
// handlers/fish_delete.php
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => 0, 'msg' => 'Invalid request']);
    exit;
}
$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => 0, 'msg' => 'Invalid id']);
    exit;
}
$del = $conn->prepare('DELETE FROM fish_species WHERE fish_id = ?');
if (!$del) {
    echo json_encode(['ok' => 0, 'msg' => 'Prepare failed']);
    exit;
}
$del->bind_param('i', $id);
$ok = $del->execute();
$del->close();
if ($ok) echo json_encode(['ok' => 1]);
else echo json_encode(['ok' => 0, 'msg' => 'Delete failed']);
