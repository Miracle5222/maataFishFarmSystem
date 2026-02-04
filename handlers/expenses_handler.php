<?php
// handlers/expenses_handler.php
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/activity_logger.php';

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
$item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : null;
$quantity = $_POST['quantity'] ?? null;
$unit = isset($_POST['unit']) ? trim($_POST['unit']) : null;
$receipt_number = isset($_POST['receipt_number']) ? trim($_POST['receipt_number']) : null;
$created_by = $_SESSION['username'] ?? ($_SESSION['user_id'] ?? 'staff');

// Basic validation
if (empty($amount) || empty($transaction_date) || empty($category) || empty($item_name) || empty($quantity) || empty($unit)) {
    header('Location: ../expenses.php?error=' . urlencode('All required fields must be filled'));
    exit;
}

// Validate category
$allowed_categories = ['labor', 'ingredients', 'feeds', 'others'];
if (!in_array($category, $allowed_categories, true)) {
    header('Location: ../expenses.php?error=' . urlencode('Invalid category'));
    exit;
}

// Validate unit
$allowed_units = ['pcs', 'kg', 'lg', 'box', 'pack', 'other'];
if (!in_array($unit, $allowed_units, true)) {
    header('Location: ../expenses.php?error=' . urlencode('Invalid unit'));
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
    }
}

$stmt = $conn->prepare('INSERT INTO expenses (amount, currency, transaction_date, description, category, item_name, quantity, unit, receipt_number, receipt_image_path, created_at, updated_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)');
if (!$stmt) {
    header('Location: ../expenses.php?error=' . urlencode('DB prepare error'));
    exit;
}
$stmt->bind_param('dsssssdssss', $amount, $currency, $transaction_date, $description, $category, $item_name, $quantity, $unit, $receipt_number, $receipt_path, $created_by);
if (!$stmt->execute()) {
    $err = $stmt->error ?: 'Failed to insert';
    $stmt->close();
    header('Location: ../expenses.php?error=' . urlencode($err));
    exit;
}

// Get the inserted expense ID
$expense_id = $conn->insert_id;
$stmt->close();

// Log the activity
$user_id = $_SESSION['user_id'] ?? 0;
$user_type = $_SESSION['role'] ?? 'staff';

logActivity(
    $conn,
    $user_id,
    $user_type,
    'CREATE',
    'expense',
    $expense_id,
    $item_name,
    "Created expense: $item_name | Category: $category | Amount: ₱$amount | Quantity: $quantity $unit"
);

header('Location: ../expenses.php?success=Expense recorded successfully.');
exit;
