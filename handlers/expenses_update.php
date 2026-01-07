<?php
// handlers/expenses_update.php
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','manager','admin'])) {
    header('Location: ../index.php?error=Access denied');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../expenses.php?error=Invalid request');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header('Location: ../expenses.php?error=Invalid id');
    exit;
}

$amount = $_POST['amount'] ?? null;
$currency = $_POST['currency'] ?? 'PHP';
$transaction_date = $_POST['transaction_date'] ?? null;
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$category = isset($_POST['category']) ? trim($_POST['category']) : null;
$subcategory = isset($_POST['subcategory']) ? trim($_POST['subcategory']) : null;
$payment_method = $_POST['payment_method'] ?? 'Other';
$vendor = isset($_POST['vendor']) ? trim($_POST['vendor']) : null;
$location = isset($_POST['location']) ? trim($_POST['location']) : null;
$status = $_POST['status'] ?? 'Recorded';
$receipt_available = isset($_POST['receipt_available']) ? 1 : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

if (empty($amount) || empty($transaction_date)) {
    header('Location: ../expenses_edit.php?id=' . $id . '&error=' . urlencode('Amount and transaction date are required'));
    exit;
}

// enums
$allowed_payment = ['Cash','Card','Digital','Other'];
$allowed_status = ['Recorded','Reviewed','Categorized','Reimbursable'];
if (!in_array($payment_method, $allowed_payment, true)) $payment_method = 'Other';
if (!in_array($status, $allowed_status, true)) $status = 'Recorded';

$receipt_path = null;
if (!empty($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['receipt_image']['tmp_name'];
    $name = basename($_FILES['receipt_image']['name']);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $safe = uniqid('rcpt_') . '.' . $ext;
    $destDir = __DIR__ . '/../assets/img/receipts';
    if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
    $dest = $destDir . '/' . $safe;
    if (move_uploaded_file($tmp, $dest)) {
        $receipt_path = 'assets/img/receipts/' . $safe;
        $receipt_available = 1;
    }
}

// build update
if ($receipt_path !== null) {
    $stmt = $conn->prepare('UPDATE expenses SET amount=?, currency=?, transaction_date=?, description=?, category=?, subcategory=?, payment_method=?, vendor=?, location=?, status=?, receipt_available=?, receipt_image_path=?, notes=?, updated_at=NOW() WHERE id=?');
    $stmt->bind_param('dsssssssssissi', $amount, $currency, $transaction_date, $description, $category, $subcategory, $payment_method, $vendor, $location, $status, $receipt_available, $receipt_path, $notes, $id);
} else {
    $stmt = $conn->prepare('UPDATE expenses SET amount=?, currency=?, transaction_date=?, description=?, category=?, subcategory=?, payment_method=?, vendor=?, location=?, status=?, receipt_available=?, notes=?, updated_at=NOW() WHERE id=?');
    $stmt->bind_param('dssssssssssi', $amount, $currency, $transaction_date, $description, $category, $subcategory, $payment_method, $vendor, $location, $status, $receipt_available, $notes, $id);
}

if (!$stmt) {
    header('Location: ../expenses_edit.php?id=' . $id . '&error=' . urlencode('DB prepare error'));
    exit;
}
if (!$stmt->execute()) {
    $err = $stmt->error ?: 'Failed to update';
    $stmt->close();
    header('Location: ../expenses_edit.php?id=' . $id . '&error=' . urlencode($err));
    exit;
}
$stmt->close();

header('Location: ../expenses.php?success=' . urlencode('Updated'));
exit;
