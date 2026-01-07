<?php
// handlers/expenses_handler.php
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';

// Allow staff, manager, admin to submit expenses
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','manager','admin'])) {
    header('Location: ../index.php?error=Access denied');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../expenses.php?error=Invalid request');
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
$created_by = $_SESSION['username'] ?? ($_SESSION['user_id'] ?? 'staff');

// Basic validation
if (empty($amount) || empty($transaction_date)) {
    header('Location: ../expenses.php?error=' . urlencode('Amount and transaction date are required'));
    exit;
}

// Validate enums
$allowed_payment = ['Cash','Card','Digital','Other'];
$allowed_status = ['Recorded','Reviewed','Categorized','Reimbursable'];
if (!in_array($payment_method, $allowed_payment, true)) $payment_method = 'Other';
if (!in_array($status, $allowed_status, true)) $status = 'Recorded';

// Allowed category -> subcategory mapping (must match client dropdowns)
$allowed_map = [
    'Operating' => ['Supplies','Rent','Licenses','Insurance'],
    'Inventory' => ['Fish Purchase','Feed','Chemicals','Packaging'],
    'Payroll' => ['Salaries','Benefits','Bonuses','Contractors'],
    'Maintenance' => ['Repairs','Equipment','Tools','Cleaning'],
    'Utilities' => ['Electricity','Water','Internet','Gas'],
    'Marketing' => ['Ads','Promotions','Events','Materials'],
    'Misc' => ['Other']
];

if (empty($category) || !array_key_exists($category, $allowed_map)) {
    header('Location: ../expenses.php?error=' . urlencode('Invalid category'));
    exit;
}

if (!empty($subcategory) && !in_array($subcategory, $allowed_map[$category], true)) {
    header('Location: ../expenses.php?error=' . urlencode('Invalid subcategory for selected category'));
    exit;
}

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

$stmt = $conn->prepare('INSERT INTO expenses (amount, currency, transaction_date, description, category, subcategory, payment_method, vendor, location, status, receipt_available, receipt_image_path, notes, created_at, updated_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)');
if (!$stmt) {
    header('Location: ../expenses.php?error=' . urlencode('DB prepare error'));
    exit;
}
$stmt->bind_param('dsssssssssisss', $amount, $currency, $transaction_date, $description, $category, $subcategory, $payment_method, $vendor, $location, $status, $receipt_available, $receipt_path, $notes, $created_by);
if (!$stmt->execute()) {
    $err = $stmt->error ?: 'Failed to insert';
    $stmt->close();
    header('Location: ../expenses.php?error=' . urlencode($err));
    exit;
}
$stmt->close();

header('Location: ../expenses.php?success=1');
exit;
