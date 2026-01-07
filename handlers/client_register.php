<?php
// handlers/client_register.php
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../client/register.php?error=Invalid request');
    exit;
}
$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$customer_type = trim($_POST['customer_type'] ?? 'online_customer');
$pw = $_POST['password'] ?? '';
$pw2 = $_POST['password_confirm'] ?? '';

if ($first === '' || $last === '' || $email === '' || $phone === '' || $pw === '' || $pw !== $pw2) {
    header('Location: ../client/register.php?error=Please fill all required fields and ensure passwords match');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../client/register.php?error=Invalid email format');
    exit;
}

// Validate customer type
if (!in_array($customer_type, ['online_customer', 'diner'])) {
    header('Location: ../client/register.php?error=Invalid customer type');
    exit;
}

// hash password using SHA1 to fit varchar(45) (40 chars)
$pw_hash = sha1($pw);

// insert into customers
$ins = $conn->prepare('INSERT INTO customers (first_name, last_name, email, phone, address, password, customer_type) VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$ins) {
    header('Location: ../client/register.php?error=Database error: ' . urlencode($conn->error));
    exit;
}

$ins->bind_param('sssssss', $first, $last, $email, $phone, $address, $pw_hash, $customer_type);
if (!$ins->execute()) {
    $error = $ins->error ? $ins->error : 'Failed to execute query';
    $ins->close();
    header('Location: ../client/register.php?error=' . urlencode($error));
    exit;
}

$insert_id = $ins->insert_id;
$ins->close();

// Always try to lookup the user, whether insert_id returned something or not
$check = $conn->prepare('SELECT id FROM customers WHERE email = ? AND password = ? LIMIT 1');
if (!$check) {
    header('Location: ../client/register.php?error=Database lookup error');
    exit;
}

$check->bind_param('ss', $email, $pw_hash);
$check->execute();
$result = $check->get_result();
$user = $result->fetch_assoc();
$check->close();

if ($user && isset($user['id']) && $user['id'] > 0) {
    // Account created successfully
    $_SESSION['client_id'] = $user['id'];
    $_SESSION['client_name'] = $first . ' ' . $last;
    header('Location: ../client/index.php?success=Account created');
    exit;
} else {
    // Still couldn't find the user - something went wrong
    header('Location: ../client/register.php?error=Account creation failed - please try again or contact support');
    exit;
}
