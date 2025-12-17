<?php
// handlers/product_update.php
include __DIR__ . '/auth_admin.php';
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => 0, 'msg' => 'Invalid request']);
    exit;
}
$id = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['product_name'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = (float) ($_POST['price'] ?? 0);
$unit = trim($_POST['unit'] ?? '');
$stock = (int) ($_POST['stock_quantity'] ?? 0);
$status = trim($_POST['status'] ?? 'available');

if ($id <= 0 || $name === '' || $category === '' || $price <= 0) {
    echo json_encode(['ok' => 0, 'msg' => 'Missing or invalid fields']);
    exit;
}

$up = $conn->prepare('UPDATE products SET name = ?, category = ?, description = ?, price = ?, unit = ?, stock_quantity = ?, status = ? WHERE id = ?');
if (!$up) {
    echo json_encode(['ok' => 0, 'msg' => 'Prepare failed']);
    exit;
}
$up->bind_param('sssdsisi', $name, $category, $description, $price, $unit, $stock, $status, $id);
$ok = $up->execute();
$up->close();
if ($ok) echo json_encode(['ok' => 1, 'msg' => 'Updated']);
else echo json_encode(['ok' => 0, 'msg' => 'Update failed']);
