<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin_login.php');
    exit;
}

// Verify admin role
$user_id = $_SESSION['user_id'];
$user_role_stmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
if ($user_role_stmt) {
    $user_role_stmt->bind_param('i', $user_id);
    $user_role_stmt->execute();
    $user_role_res = $user_role_stmt->get_result();
    $user_role = $user_role_res->fetch_assoc();
    $user_role_stmt->close();
    
    if (!$user_role || $user_role['role'] !== 'admin') {
        header('Location: ../staff_list.php?error=' . urlencode('You do not have permission to edit staff'));
        exit;
    }
}

// Get form data
$staff_id = $_POST['staff_id'] ?? null;
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$position = trim($_POST['position'] ?? '');
$department = trim($_POST['department'] ?? '');
$hire_date = $_POST['hire_date'] ?? null;
$role = $_POST['role'] ?? 'staff';
$status = $_POST['status'] ?? 'active';

// Validate inputs
$errors = [];

if (!$staff_id || !is_numeric($staff_id)) {
    $errors[] = 'Invalid staff ID';
}

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

if (empty($position)) {
    $errors[] = 'Position is required';
}

if (!in_array($role, ['staff', 'manager', 'admin'])) {
    $errors[] = 'Invalid role selected';
}

if (!in_array($status, ['active', 'inactive'])) {
    $errors[] = 'Invalid status selected';
}

// Get current staff info to check for changes
if (empty($errors)) {
    $get_staff = $conn->prepare('SELECT user_id FROM staff WHERE id = ?');
    if ($get_staff) {
        $get_staff->bind_param('i', $staff_id);
        $get_staff->execute();
        $get_staff->store_result();
        
        if ($get_staff->num_rows === 0) {
            $errors[] = 'Staff member not found';
        } else {
            $get_staff->bind_result($linked_user_id);
            $get_staff->fetch();
        }
        $get_staff->close();
    }
}

// Check for duplicate username (excluding current staff)
if (empty($errors) && $linked_user_id) {
    $check_username = $conn->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?');
    if ($check_username) {
        $check_username->bind_param('si', $username, $linked_user_id);
        $check_username->execute();
        $check_username->store_result();
        
        if ($check_username->num_rows > 0) {
            $errors[] = 'Username "' . htmlspecialchars($username) . '" is already in use';
        }
        $check_username->close();
    }
}

// Check for duplicate email (excluding current staff)
if (empty($errors) && $linked_user_id) {
    $check_email = $conn->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?');
    if ($check_email) {
        $check_email->bind_param('si', $email, $linked_user_id);
        $check_email->execute();
        $check_email->store_result();
        
        if ($check_email->num_rows > 0) {
            $errors[] = 'Email "' . htmlspecialchars($email) . '" is already registered';
        }
        $check_email->close();
    }
}

if (!empty($errors)) {
    $error_msg = implode(' | ', $errors);
    error_log("Staff edit validation errors: " . $error_msg);
    header('Location: ../staff_edit.php?id=' . urlencode($staff_id) . '&error=' . urlencode($error_msg));
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Update staff table
    $full_name = $first_name . ' ' . $last_name;
    
    $update_staff = $conn->prepare('UPDATE staff SET first_name = ?, last_name = ?, email = ?, phone = ?, position = ?, department = ?, hire_date = ?, status = ? WHERE id = ?');
    
    if (!$update_staff) {
        throw new Exception('Prepare staff update failed: ' . $conn->error);
    }
    
    $hire_date_value = !empty($hire_date) ? $hire_date : null;
    $update_staff->bind_param('ssssssssi', $first_name, $last_name, $email, $phone, $position, $department, $hire_date_value, $status, $staff_id);
    
    if (!$update_staff->execute()) {
        throw new Exception('Execute staff update failed: ' . $update_staff->error);
    }
    $update_staff->close();
    
    // Update users table if linked
    if ($linked_user_id) {
        $update_user = $conn->prepare('UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, status = ? WHERE id = ?');
        
        if (!$update_user) {
            throw new Exception('Prepare user update failed: ' . $conn->error);
        }
        
        $update_user->bind_param('sssssi', $username, $email, $full_name, $role, $status, $linked_user_id);
        
        if (!$update_user->execute()) {
            throw new Exception('Execute user update failed: ' . $update_user->error);
        }
        $update_user->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the action
    error_log("✓ SUCCESS: Staff member updated - ID=$staff_id | Name=$full_name | Username=$username | Email=$email | Position=$position | Role=$role | Updated by Admin ID=$user_id");
    
    // Redirect with success message
    $success_message = "Staff member '$full_name' has been successfully updated!";
    header('Location: ../staff_edit.php?id=' . urlencode($staff_id) . '&message=' . urlencode($success_message));
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log("✗ ERROR updating staff: " . $e->getMessage());
    
    $user_error_msg = 'Unable to update staff member. Please check your input and try again.';
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        $user_error_msg = 'This username or email is already in use. Please choose different values.';
    } elseif (strpos($e->getMessage(), 'Execute') !== false) {
        $user_error_msg = 'Database error: Unable to save changes. Please try again.';
    }
    
    header('Location: ../staff_edit.php?id=' . urlencode($staff_id) . '&error=' . urlencode($user_error_msg));
    exit;
}

$conn->close();
?>
