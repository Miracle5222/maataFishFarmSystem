<?php
// handlers/client_login.php
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../client/login.php?error=Invalid request');
    exit;
}
$identifier = trim($_POST['identifier'] ?? '');
$pw = $_POST['password'] ?? '';
$next = trim($_POST['next'] ?? 'index.php');

if ($identifier === '' || $pw === '') {
    header('Location: ../client/login.php?error=Missing credentials');
    exit;
}

// Authenticate against `customers` table (password stored as SHA1)
$stmt = $conn->prepare('SELECT id AS customer_id, password, first_name, last_name FROM customers WHERE email = ? OR phone = ? LIMIT 1');
if (!$stmt) {
    header('Location: ../client/login.php?error=Login error');
    exit;
}
$stmt->bind_param('ss', $identifier, $identifier);
$stmt->execute();
$res = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user || sha1($pw) !== ($user['password'] ?? '')) {
    header('Location: ../client/login.php?error=Invalid credentials');
    exit;
}

$_SESSION['client_id'] = $user['customer_id'];
$_SESSION['client_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

// redirect to next (decode and sanitize to avoid duplicated paths or open-redirects)
$next = $next ?: 'index.php';
$next = urldecode($next);
$next = preg_replace('/[\r\n]/', '', $next);
// don't allow absolute URLs or directory traversal
if (stripos($next, 'http://') === 0 || stripos($next, 'https://') === 0 || strpos($next, '..') !== false) {
    $next = 'index.php';
}
if (strpos($next, '/') === 0) {
    // root-relative path (e.g. /maataFishFarmSystem/client/cart.php)
    header('Location: ' . $next);
} else {
    header('Location: ../client/' . ltrim($next, '/'));
}
exit;
