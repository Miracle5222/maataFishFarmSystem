<?php
// handlers/client_profile_handler.php
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../client/profile.php?error=Invalid request');
    exit;
}
if (empty($_SESSION['client_id'])) {
    header('Location: ../client/login.php?error=Please login');
    exit;
}
$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0 || $id !== (int) $_SESSION['client_id']) {
    header('Location: ../client/profile.php?error=Invalid account');
    exit;
}
$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$barangay = trim($_POST['barangay'] ?? '');
$municipality = trim($_POST['municipality'] ?? '');

if ($first === '' || $last === '') {
    header('Location: ../client/profile.php?error=First and last name are required');
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../client/profile.php?error=Invalid email');
    exit;
}

// ensure email/phone unique among other customers
$stmt = $conn->prepare('SELECT id FROM customers WHERE (email = ? OR phone = ?) AND id != ? LIMIT 1');
$stmt->bind_param('ssi', $email, $phone, $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->fetch_assoc()) {
    $stmt->close();
    header('Location: ../client/profile.php?error=Email or phone already used');
    exit;
}
$stmt->close();

$upd = $conn->prepare('UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, barangay = ?, municipality = ?, updated_at = NOW() WHERE id = ?');
$upd->bind_param('sssssssi', $first, $last, $email, $phone, $address, $barangay, $municipality, $id);
$ok = $upd->execute();
$upd->close();

if ($ok) {
    $_SESSION['client_name'] = $first . ' ' . $last;
    header('Location: ../client/profile.php?message=Profile updated');
} else {
    header('Location: ../client/profile.php?error=Failed to update');
}
exit;
