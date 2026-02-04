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

// Validate government ID image upload
$gov_id_image = null;
if (empty($_FILES['government_id_image']['name'])) {
    $_SESSION['reg_error'] = 'Government ID image is required';
    header('Location: ../client/register.php');
    exit;
}

// Validate file upload
$file = $_FILES['government_id_image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowed_types)) {
    $_SESSION['reg_error'] = 'Invalid file type. Please upload JPG, PNG, or GIF';
    header('Location: ../client/register.php');
    exit;
}

if ($file['size'] > $max_size) {
    $_SESSION['reg_error'] = 'File size exceeds 5MB limit';
    header('Location: ../client/register.php');
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['reg_error'] = 'Error uploading file. Please try again';
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

// Process and save government ID image
$upload_dir = __DIR__ . '/../assets/img/customer_ids/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename with customer info for verification
$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$unique_name = 'govid_' . strtolower($first) . '_' . strtolower($last) . '_' . uniqid() . '.' . $file_ext;
$file_path = $upload_dir . $unique_name;

if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    $_SESSION['reg_error'] = 'Failed to save government ID image';
    header('Location: ../client/register.php');
    exit;
}

$gov_id_image = $unique_name;

// insert into customers
$ins = $conn->prepare('INSERT INTO customers (first_name, last_name, email, phone, address, password, customer_type, government_id_image, government_id_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
if (!$ins) {
    $_SESSION['reg_error'] = 'Database error: ' . $conn->error;
    header('Location: ../client/register.php');
    exit;
}

$verified = 0; // Start as unverified - admin will verify
$ins->bind_param('ssssssssi', $first, $last, $email, $phone, $address, $pw_hash, $customer_type, $gov_id_image, $verified);
if (!$ins->execute()) {
    // Delete uploaded file if insert fails
    @unlink($file_path);
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
