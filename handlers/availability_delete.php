<?php
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../handlers/activity_logger.php';

// Check if user is logged in and is admin/manager/staff
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ../admin_login.php');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    header('Location: ../availability_check.php?error=' . urlencode('Invalid ID'));
    exit();
}

try {
    // Get table info first
    $get_stmt = $conn->prepare('SELECT table_name FROM availability_tables WHERE id = ?');
    if (!$get_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $get_stmt->bind_param('i', $id);
    if (!$get_stmt->execute()) {
        throw new Exception('Query failed: ' . $get_stmt->error);
    }
    
    $get_result = $get_stmt->get_result();
    if ($get_result->num_rows === 0) {
        throw new Exception('Table not found');
    }
    
    $table = $get_result->fetch_assoc();
    $table_name = $table['table_name'];
    $get_stmt->close();
    
    // Delete the table
    $delete_stmt = $conn->prepare('DELETE FROM availability_tables WHERE id = ?');
    if (!$delete_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $delete_stmt->bind_param('i', $id);
    if (!$delete_stmt->execute()) {
        throw new Exception('Failed to delete: ' . $delete_stmt->error);
    }
    
    $delete_stmt->close();
    
    // Log the activity
    logActivity(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['role'],
        'DELETE',
        'availability_tables',
        $id,
        $table_name,
        "Deleted table '$table_name'"
    );
    
    header('Location: ../availability_check.php?message=' . urlencode("Table '$table_name' deleted successfully"));
    exit();
    
} catch (Exception $e) {
    error_log('Table Delete Error: ' . $e->getMessage());
    header('Location: ../availability_check.php?error=' . urlencode($e->getMessage()));
    exit();
}
