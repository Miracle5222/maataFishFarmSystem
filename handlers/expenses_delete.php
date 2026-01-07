<?php
// handlers/expenses_delete.php
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

$stmt = $conn->prepare('DELETE FROM expenses WHERE id = ?');
if (!$stmt) {
    header('Location: ../expenses.php?error=DB prepare error');
    exit;
}
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    $err = $stmt->error ?: 'Failed to delete';
    $stmt->close();
    header('Location: ../expenses.php?error=' . urlencode($err));
    exit;
}
$stmt->close();

header('Location: ../expenses.php?success=' . urlencode('Deleted'));
exit;
