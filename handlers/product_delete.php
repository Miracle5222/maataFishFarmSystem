<?php
// handlers/product_delete.php
include __DIR__ . '/auth_admin.php';
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => 0, 'msg' => 'Invalid request']);
    exit;
}
$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => 0, 'msg' => 'Invalid product id']);
    exit;
}

// remove images
$stmt = $conn->prepare('SELECT filename FROM product_images WHERE product_id = ?');
if ($stmt) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $file = __DIR__ . '/../assets/img/products/' . $r['filename'];
        if (is_file($file)) @unlink($file);
    }
    $stmt->close();
}

$delImgs = $conn->prepare('DELETE FROM product_images WHERE product_id = ?');
if ($delImgs) {
    $delImgs->bind_param('i', $id);
    $delImgs->execute();
    $delImgs->close();
}

$del = $conn->prepare('DELETE FROM products WHERE id = ?');
if (!$del) {
    echo json_encode(['ok' => 0, 'msg' => 'Prepare failed']);
    exit;
}
$del->bind_param('i', $id);
$ok = $del->execute();
$del->close();
if ($ok) echo json_encode(['ok' => 1, 'msg' => 'Deleted']);
else echo json_encode(['ok' => 0, 'msg' => 'Delete failed']);
