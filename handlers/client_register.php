<?php
// handlers/client_register.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Block direct access - handle GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

require __DIR__ . '/../config/db.php';

$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$customer_type = trim($_POST['customer_type'] ?? 'online_customer');
$pw = $_POST['password'] ?? '';
$pw2 = $_POST['password_confirm'] ?? '';

if ($first === '' || $last === '' || $email === '' || $phone === '' || $pw === '' || $pw !== $pw2) {
    $_SESSION['reg_error'] = 'Please fill all required fields and ensure passwords match';
    header('Location: ../client/register.php');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reg_error'] = 'Invalid email format';
    header('Location: ../client/register.php');
    exit;
}

// Validate customer type
if (!in_array($customer_type, ['online_customer', 'diner'])) {
    $_SESSION['reg_error'] = 'Invalid customer type';
    header('Location: ../client/register.php');
    exit;
}

// Check database connection
if (!$conn) {
    $_SESSION['reg_error'] = 'Database connection failed';
    header('Location: ../client/register.php');
    exit;
}

// hash password using SHA1 to fit varchar(45) (40 chars)
$pw_hash = sha1($pw);

// Check if email already exists
$check_email = $conn->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
if ($check_email) {
    $check_email->bind_param('s', $email);
    $check_email->execute();
    $email_result = $check_email->get_result();
    if ($email_result->num_rows > 0) {
        $check_email->close();
        $_SESSION['reg_error'] = 'Email already registered';
        header('Location: ../client/register.php');
        exit;
    }
    $check_email->close();
}

// insert into customers
$ins = $conn->prepare('INSERT INTO customers (first_name, last_name, email, phone, address, password, customer_type) VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$ins) {
    $_SESSION['reg_error'] = 'Database error: ' . $conn->error;
    header('Location: ../client/register.php');
    exit;
}

$ins->bind_param('sssssss', $first, $last, $email, $phone, $address, $pw_hash, $customer_type);
if (!$ins->execute()) {
    $error = $ins->error ? $ins->error : 'Failed to create account';
    $ins->close();
    $_SESSION['reg_error'] = $error;
    header('Location: ../client/register.php');
    exit;
}

$insert_id = $ins->insert_id;
$ins->close();

// Verify the account was created
if ($insert_id > 0) {
    $_SESSION['reg_success'] = 'Account created successfully! Please log in.';
    header('Location: ../client/register.php');
    exit;
} else {
    $_SESSION['reg_error'] = 'Account creation failed - please try again or contact support';
    header('Location: ../client/register.php');
    exit;
}
