<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin_login.php');
    exit;
}

// Only allow admin to add staff
$user_id = $_SESSION['user_id'];
$user_role_stmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
if ($user_role_stmt) {
    $user_role_stmt->bind_param('i', $user_id);
    $user_role_stmt->execute();
    $user_role_res = $user_role_stmt->get_result();
    $user_role = $user_role_res->fetch_assoc();
    $user_role_stmt->close();
    
    if (!$user_role || $user_role['role'] !== 'admin') {
        header('Location: ../staff_list.php?error=' . urlencode('You do not have permission to add staff'));
        exit;
    }
}

// Get form data
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$position = $_POST['position'] ?? '';
$department = $_POST['department'] ?? '';
$hire_date = $_POST['hire_date'] ?? null;
$role = $_POST['role'] ?? 'staff';
$status = $_POST['status'] ?? 'active';

// Trim whitespace from inputs
$first_name = trim($first_name);
$last_name = trim($last_name);
$username = trim($username);
$email = trim($email);
$phone = trim($phone);
$position = trim($position);
$department = trim($department);

// Log the submission attempt
error_log("Staff add attempt: Username=$username, Email=$email, First=$first_name, Last=$last_name, Position=$position");

// Validate inputs
$errors = [];

if (empty($first_name)) {
    $errors[] = 'First name is required';
}

if (empty($last_name)) {
    $errors[] = 'Last name is required';
}

if (empty($username)) {
    $errors[] = 'Username is required';
} else if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    $errors[] = 'Username must be 3-20 characters and contain only letters, numbers, and underscores';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address';
}

if (empty($password)) {
    $errors[] = 'Password is required';
} else if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

if (empty($position)) {
    $errors[] = 'Position is required';
}

if (!in_array($role, ['staff', 'manager'])) {
    $errors[] = 'Invalid role selected';
}

if (!in_array($status, ['active', 'inactive'])) {
    $errors[] = 'Invalid status selected';
}

// Check if username already exists
if (empty($errors)) {
    $check_username = $conn->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?)');
    if ($check_username) {
        $check_username->bind_param('s', $username);
        if (!$check_username->execute()) {
            error_log("Username check failed: " . $check_username->error);
            $errors[] = 'Unable to verify username availability';
        } else {
            $check_username->store_result();
            
            if ($check_username->num_rows > 0) {
                $errors[] = 'Username "' . htmlspecialchars($username) . '" already exists. Please choose a different username.';
                error_log("Duplicate username detected: $username");
            }
        }
        $check_username->close();
    } else {
        error_log("Username prepare failed: " . $conn->error);
        $errors[] = 'Database error during username check';
    }
}

// Check if email already exists
if (empty($errors)) {
    $check_email = $conn->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?)');
    if ($check_email) {
        $check_email->bind_param('s', $email);
        if (!$check_email->execute()) {
            error_log("Email check failed: " . $check_email->error);
            $errors[] = 'Unable to verify email availability';
        } else {
            $check_email->store_result();
            
            if ($check_email->num_rows > 0) {
                $errors[] = 'Email "' . htmlspecialchars($email) . '" is already registered. Please use a different email.';
                error_log("Duplicate email detected: $email");
            }
        }
        $check_email->close();
    } else {
        error_log("Email prepare failed: " . $conn->error);
        $errors[] = 'Database error during email check';
    }
}

if (!empty($errors)) {
    $error_msg = implode(' | ', $errors);
    error_log("Staff validation errors: " . $error_msg);
    header('Location: ../staff_add.php?error=' . urlencode($error_msg));
    exit;
}

// Hash password using bcrypt
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Prepare full name from first and last name
$full_name = trim($first_name . ' ' . $last_name);

// Start transaction
$conn->begin_transaction();

try {
    // Insert into users table
    $insert_user = $conn->prepare('INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)');
    
    if (!$insert_user) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $insert_user->bind_param('ssssss', $username, $email, $hashed_password, $full_name, $role, $status);
    
    if (!$insert_user->execute()) {
        throw new Exception('Execute failed: ' . $insert_user->error);
    }
    
    $new_user_id = $conn->insert_id;
    $insert_user->close();
    
    // Insert into staff table
    $insert_staff = $conn->prepare('INSERT INTO staff (first_name, last_name, email, phone, position, department, hire_date, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    if (!$insert_staff) {
        throw new Exception('Prepare staff failed: ' . $conn->error);
    }
    
    // Handle null hire_date
    $hire_date_value = !empty($hire_date) ? $hire_date : null;
    
    $insert_staff->bind_param('ssssssssi', $first_name, $last_name, $email, $phone, $position, $department, $hire_date_value, $status, $new_user_id);
    
    if (!$insert_staff->execute()) {
        throw new Exception('Execute staff failed: ' . $insert_staff->error);
    }
    
    $insert_staff->close();
    
    // Commit transaction
    $conn->commit();
    
    // Log the action
    error_log("✓ SUCCESS: New staff member added - ID=$new_user_id | Name=$full_name | Username=$username | Email=$email | Position=$position | Role=$role | Added by Admin ID=$user_id");
    
    // Redirect with success message
    $success_message = "✓ Staff member '$full_name' has been successfully added! Username: '$username'";
    header('Location: ../staff_add.php?message=' . urlencode($success_message));
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    $error_msg = $e->getMessage();
    error_log("✗ ERROR adding staff: " . $error_msg);
    
    // Provide user-friendly error message based on the actual error
    $user_error_msg = 'Unable to add staff member. Please check your input and try again.';
    
    if (strpos($error_msg, 'Duplicate') !== false || strpos($error_msg, '1062') !== false) {
        // MySQL error code 1062 is for duplicate key
        if (strpos($error_msg, 'username') !== false) {
            $user_error_msg = 'This username is already in use. Please choose a different username.';
        } elseif (strpos($error_msg, 'email') !== false) {
            $user_error_msg = 'This email address is already registered. Please use a different email.';
        } else {
            $user_error_msg = 'This information is already registered in the system. Please check your input.';
        }
    } elseif (strpos($error_msg, 'Execute failed') !== false) {
        $user_error_msg = 'Database error: Unable to save staff information. Please try again.';
    } elseif (strpos($error_msg, 'Prepare') !== false) {
        $user_error_msg = 'System error: Unable to prepare database query. Please contact support.';
    } elseif (strpos($error_msg, 'staff table') !== false) {
        $user_error_msg = 'Error saving staff details. Please ensure all required information is provided.';
    }
    
    header('Location: ../staff_add.php?error=' . urlencode($user_error_msg));
    exit;
}

$conn->close();
?>
