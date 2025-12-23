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
        header('Location: ../staff_list.php?error=' . urlencode('You do not have permission to delete staff'));
        exit;
    }
}

// Get staff ID from URL
$staff_id = $_GET['id'] ?? null;

if (!$staff_id || !is_numeric($staff_id)) {
    header('Location: ../staff_list.php?error=' . urlencode('Invalid staff ID'));
    exit;
}

try {
    // Get staff information
    $get_staff = $conn->prepare('SELECT first_name, last_name, user_id FROM staff WHERE id = ?');
    if (!$get_staff) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $get_staff->bind_param('i', $staff_id);
    if (!$get_staff->execute()) {
        throw new Exception('Execute failed: ' . $get_staff->error);
    }
    
    $get_staff->store_result();
    if ($get_staff->num_rows === 0) {
        throw new Exception('Staff member not found');
    }
    
    $get_staff->bind_result($first_name, $last_name, $user_id_linked);
    $get_staff->fetch();
    $get_staff->close();
    
    $staff_name = $first_name . ' ' . $last_name;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Delete from staff table
    $delete_staff = $conn->prepare('DELETE FROM staff WHERE id = ?');
    if (!$delete_staff) {
        throw new Exception('Prepare delete staff failed: ' . $conn->error);
    }
    
    $delete_staff->bind_param('i', $staff_id);
    if (!$delete_staff->execute()) {
        throw new Exception('Delete staff failed: ' . $delete_staff->error);
    }
    $delete_staff->close();
    
    // Delete from users table if linked
    if ($user_id_linked) {
        $delete_user = $conn->prepare('DELETE FROM users WHERE id = ?');
        if (!$delete_user) {
            throw new Exception('Prepare delete user failed: ' . $conn->error);
        }
        
        $delete_user->bind_param('i', $user_id_linked);
        if (!$delete_user->execute()) {
            throw new Exception('Delete user failed: ' . $delete_user->error);
        }
        $delete_user->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    error_log("✓ SUCCESS: Staff member deleted - ID=$staff_id | Name=$staff_name | Linked User ID=$user_id_linked | Deleted by Admin ID=$user_id");
    
    header('Location: ../staff_list.php?message=' . urlencode("Staff member '$staff_name' has been successfully deleted."));
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("✗ ERROR deleting staff: " . $e->getMessage());
    
    $error_msg = 'Unable to delete staff member. Please try again.';
    if (strpos($e->getMessage(), 'not found') !== false) {
        $error_msg = 'Staff member not found.';
    } elseif (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        $error_msg = 'Cannot delete staff member. They have associated records.';
    }
    
    header('Location: ../staff_list.php?error=' . urlencode($error_msg));
    exit;
}

$conn->close();
?>
