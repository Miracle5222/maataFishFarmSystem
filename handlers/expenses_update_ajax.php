<?php
// handlers/expenses_update_ajax.php - accepts multipart/form-data, returns JSON
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
// buffer output to avoid any stray HTML/warnings breaking JSON
ob_start();

function json_exit($data) {
    $buf = ob_get_clean();
    if (!empty($buf)) {
        error_log("expenses_update_ajax.php stray output: " . $buf);
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','manager','admin'])) {
    json_exit(['success'=>false,'error'=>'Access denied']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_exit(['success'=>false,'error'=>'Invalid request']); }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { json_exit(['success'=>false,'error'=>'Invalid id']); }

$amount = $_POST['amount'] ?? null;
$currency = $_POST['currency'] ?? 'PHP';
$transaction_date = $_POST['transaction_date'] ?? null;
$description = $_POST['description'] ?? null;
$category = $_POST['category'] ?? null;
$subcategory = $_POST['subcategory'] ?? null;
$payment_method = $_POST['payment_method'] ?? 'Other';
$vendor = $_POST['vendor'] ?? null;
$location = $_POST['location'] ?? null;
$status = $_POST['status'] ?? 'Recorded';
$receipt_available = isset($_POST['receipt_available']) ? 1 : 0;
$notes = $_POST['notes'] ?? null;

if (empty($amount) || empty($transaction_date)) { json_exit(['success'=>false,'error'=>'Amount and transaction date required']); }

$allowed_payment = ['Cash','Card','Digital','Other'];
$allowed_status = ['Recorded','Reviewed','Categorized','Reimbursable'];
if (!in_array($payment_method,$allowed_payment,true)) $payment_method = 'Other';
if (!in_array($status,$allowed_status,true)) $status = 'Recorded';

$receipt_path = null;
if (!empty($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['receipt_image']['tmp_name'];
    $name = basename($_FILES['receipt_image']['name']);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $safe = uniqid('rcpt_') . '.' . $ext;
    $destDir = __DIR__ . '/../assets/img/receipts';
    if (!is_dir($destDir)) @mkdir($destDir,0755,true);
    $dest = $destDir . '/' . $safe;
    if (move_uploaded_file($tmp,$dest)) { $receipt_path = 'assets/img/receipts/' . $safe; $receipt_available = 1; }
}

// update statement
if ($receipt_path !== null) {
    $stmt = $conn->prepare('UPDATE expenses SET amount=?, currency=?, transaction_date=?, description=?, category=?, subcategory=?, payment_method=?, vendor=?, location=?, status=?, receipt_available=?, receipt_image_path=?, notes=?, updated_at=NOW() WHERE id=?');
    if (!$stmt) { json_exit(['success'=>false,'error'=>'DB prepare error']); }
    $stmt->bind_param('dsssssssssissi', $amount, $currency, $transaction_date, $description, $category, $subcategory, $payment_method, $vendor, $location, $status, $receipt_available, $receipt_path, $notes, $id);
} else {
    $stmt = $conn->prepare('UPDATE expenses SET amount=?, currency=?, transaction_date=?, description=?, category=?, subcategory=?, payment_method=?, vendor=?, location=?, status=?, receipt_available=?, notes=?, updated_at=NOW() WHERE id=?');
    if (!$stmt) { json_exit(['success'=>false,'error'=>'DB prepare error']); }
    $stmt->bind_param('dsssssssssisi', $amount, $currency, $transaction_date, $description, $category, $subcategory, $payment_method, $vendor, $location, $status, $receipt_available, $notes, $id);
}
if (!$stmt->execute()) { $err = $stmt->error ?: 'Failed to update'; $stmt->close(); json_exit(['success'=>false,'error'=>$err]); }
$stmt->close();

json_exit(['success'=>true]);
