<?php
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../handlers/activity_logger.php';

// Check if user is logged in and is admin/manager/staff
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ../admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../availability_check.php');
    exit();
}

// Get form data
$table_id = isset($_POST['table_id']) ? intval($_POST['table_id']) : 0;
$capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validation
if ($table_id <= 0) {
    header('Location: ../availability_check.php?error=' . urlencode('Invalid table ID.'));
    exit();
}

if ($capacity <= 0 || $capacity > 100) {
    header('Location: ../availability_check.php?error=' . urlencode('Capacity must be between 1 and 100.'));
    exit();
}

if (!in_array($status, ['available', 'not available'])) {
    header('Location: ../availability_check.php?error=' . urlencode('Invalid status.'));
    exit();
}

try {
    // Get current table info
    $get_stmt = $conn->prepare('SELECT table_name FROM availability_tables WHERE id = ?');
    if (!$get_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $get_stmt->bind_param('i', $table_id);
    if (!$get_stmt->execute()) {
        throw new Exception('Query failed: ' . $get_stmt->error);
    }
    
    $get_result = $get_stmt->get_result();
    
    if ($get_result->num_rows === 0) {
        $get_stmt->close();
        header('Location: ../availability_check.php?error=' . urlencode('Table not found.'));
        exit();
    }
    
    $table_row = $get_result->fetch_assoc();
    $table_name = $table_row['table_name'];
    $get_stmt->close();
    
    // Update the table
    $update_stmt = $conn->prepare('
        UPDATE availability_tables 
        SET capacity = ?, status = ?, notes = ?, updated_at = NOW()
        WHERE id = ?
    ');
    
    if (!$update_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $update_stmt->bind_param('issi', $capacity, $status, $notes, $table_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update table: ' . $update_stmt->error);
    }
    
    $update_stmt->close();
    
    // Log the activity
    logActivity(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['role'],
        'EDIT',
        'availability_tables',
        $table_id,
        $table_name,
        "Updated table '$table_name' - Capacity: $capacity | Status: $status"
    );
    
    header('Location: ../availability_check.php?message=' . urlencode("Table '$table_name' updated successfully!"));
    exit();
    
} catch (Exception $e) {
    $error_detail = 'An error occurred: ' . $e->getMessage();
    error_log('Table Update Error: ' . $error_detail);
    header('Location: ../availability_check.php?error=' . urlencode($error_detail));
    exit();
}
