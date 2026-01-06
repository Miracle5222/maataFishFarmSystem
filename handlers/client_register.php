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
$barangay = trim($_POST['barangay'] ?? '');
$municipality = trim($_POST['municipality'] ?? '');
$customer_type = trim($_POST['customer_type'] ?? 'online_customer');
$pw = $_POST['password'] ?? '';
$pw2 = $_POST['password_confirm'] ?? '';

if ($first === '' || $last === '' || $phone === '' || $pw === '' || $pw !== $pw2) {
    header('Location: ../client/register.php?error=Please fill required fields and ensure passwords match');
    exit;
}
// Ensure `costumers` table exists with the requested schema
$conn->query("CREATE TABLE IF NOT EXISTS customers (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    barangay VARCHAR(100),
    municipality VARCHAR(100),
    customer_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    password VARCHAR(45)
)");

// check existing by email or phone in costumers
$stmt = $conn->prepare('SELECT id FROM customers WHERE email = ? OR phone = ? LIMIT 1');
$stmt->bind_param('ss', $email, $phone);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->fetch_assoc()) {
    $stmt->close();
    header('Location: ../client/register.php?error=Account with that email or phone already exists');
    exit;
}
$stmt->close();

// hash password using SHA1 to fit varchar(45) (40 chars)
$pw_hash = sha1($pw);

// insert into customers
$ins = $conn->prepare('INSERT INTO customers (first_name, last_name, email, phone, address, barangay, municipality, customer_type, created_at, updated_at, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)');
$ins->bind_param('sssssssss', $first, $last, $email, $phone, $address, $barangay, $municipality, $customer_type, $pw_hash);
$ok = $ins->execute();
$insert_id = $conn->insert_id;
$ins->close();
if (!$ok || !$insert_id) {
    header('Location: ../client/register.php?error=Failed to create account');
    exit;
}

// login user
$_SESSION['client_id'] = $insert_id;
$_SESSION['client_name'] = $first . ' ' . $last;

header('Location: ../client/index.php?success=Account created');
exit;
