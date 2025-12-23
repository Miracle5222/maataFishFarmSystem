<?php
session_start();
require __DIR__ . '/../config/db.php';

// Check if user is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ../admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../availability_check.php');
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$max_capacity = isset($_POST['max_capacity']) ? intval($_POST['max_capacity']) : 0;
$is_available = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if (!$id || $max_capacity <= 0) {
    header('Location: ../availability_check.php?error=' . urlencode('Invalid data'));
    exit();
}

try {
    // Get current slot info first
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
    
    // Check if trying to close a slot with reservations
    if ($is_available == 0 && $slot['current_reservations'] > 0) {
        throw new Exception('Cannot close this slot - it has ' . $slot['current_reservations'] . ' reservation(s). Please cancel them first.');
    }
    
    // Update the slot
    $update_stmt = $conn->prepare('
        UPDATE availability 
        SET max_capacity = ?, is_available = ?, notes = ?, updated_at = NOW()
        WHERE id = ?
    ');
    
    if (!$update_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $update_stmt->bind_param('iisi', $max_capacity, $is_available, $notes, $id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update: ' . $update_stmt->error);
    }
    
    $update_stmt->close();
    header('Location: ../availability_check.php?message=' . urlencode('Availability updated successfully'));
    exit();
    
} catch (Exception $e) {
    error_log('Availability Update Error: ' . $e->getMessage());
    header('Location: ../availability_check.php?error=' . urlencode($e->getMessage()));
    exit();
}
