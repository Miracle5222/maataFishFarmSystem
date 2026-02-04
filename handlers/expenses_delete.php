<?php
// handlers/expenses_delete.php
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/activity_logger.php';

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','manager','admin'])) {
    header('Location: ../index.php?error=Access denied');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../reports_expenses.php?error=Invalid request');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header('Location: ../reports_expenses.php?error=Invalid id');
    exit;
}

// Get expense details before deletion for logging
$getExpense = $conn->prepare('SELECT category, amount FROM expenses WHERE id = ?');
if ($getExpense) {
    $getExpense->bind_param('i', $id);
    $getExpense->execute();
    $result = $getExpense->get_result();
    $expense = $result->fetch_assoc();
    $category = $expense['category'] ?? 'Unknown';
    $amount = $expense['amount'] ?? 0;
    $getExpense->close();
}

$stmt = $conn->prepare('DELETE FROM expenses WHERE id = ?');
if (!$stmt) {
    header('Location: ../reports_expenses.php?error=DB prepare error');
    exit;
}
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    $err = $stmt->error ?: 'Failed to delete';
    $stmt->close();
    header('Location: ../reports_expenses.php?error=' . urlencode($err));
    exit;
}
$stmt->close();

// Log the activity
$user_id = $_SESSION['user_id'] ?? 0;
$user_type = $_SESSION['role'] ?? 'staff';

logActivity(
    $conn,
    $user_id,
    $user_type,
    'DELETE',
    'expense',
    $id,
    $category,
    "Deleted expense: $category - ₱$amount"
);

header('Location: ../reports_expenses.php?success=' . urlencode('Deleted'));
exit;
