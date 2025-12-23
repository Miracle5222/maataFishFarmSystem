<?php
session_start();
require __DIR__ . '/../config/db.php';

// Check if user is logged in
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
    // Get slot info first
    $get_stmt = $conn->prepare('SELECT current_reservations FROM availability WHERE id = ?');
    if (!$get_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $get_stmt->bind_param('i', $id);
    if (!$get_stmt->execute()) {
        throw new Exception('Query failed: ' . $get_stmt->error);
    }
    
    $get_result = $get_stmt->get_result();
    if ($get_result->num_rows === 0) {
        throw new Exception('Availability slot not found');
    }
    
    $slot = $get_result->fetch_assoc();
    $get_stmt->close();
    
    // Check if slot has reservations
    if ($slot['current_reservations'] > 0) {
        throw new Exception('Cannot delete this slot - it has ' . $slot['current_reservations'] . ' reservation(s). Please cancel them first.');
    }
    
    // Delete the slot
    $delete_stmt = $conn->prepare('DELETE FROM availability WHERE id = ?');
    if (!$delete_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $delete_stmt->bind_param('i', $id);
    if (!$delete_stmt->execute()) {
        throw new Exception('Failed to delete: ' . $delete_stmt->error);
    }
    
    $delete_stmt->close();
    header('Location: ../availability_check.php?message=' . urlencode('Availability slot deleted successfully'));
    exit();
    
} catch (Exception $e) {
    error_log('Availability Delete Error: ' . $e->getMessage());
    header('Location: ../availability_check.php?error=' . urlencode($e->getMessage()));
    exit();
}
