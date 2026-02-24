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
    header('Location: ../availability_set.php');
    exit();
}

// Get form data
$table_name = isset($_POST['table_name']) ? trim($_POST['table_name']) : '';
$capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validation
if (empty($table_name)) {
    header('Location: ../availability_set.php?error=' . urlencode('Table name is required.'));
    exit();
}

if ($capacity <= 0 || $capacity > 100) {
    header('Location: ../availability_set.php?error=' . urlencode('Capacity must be between 1 and 100.'));
    exit();
}

try {
    // Check if table already exists
    $check_stmt = $conn->prepare('SELECT id FROM availability_tables WHERE table_name = ?');
    
    if (!$check_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $check_stmt->bind_param('s', $table_name);
    if (!$check_stmt->execute()) {
        throw new Exception('Check query failed: ' . $check_stmt->error);
    }
    
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        header('Location: ../availability_set.php?error=' . urlencode('A table with this name already exists.'));
        exit();
    }
    
    $check_stmt->close();
    
    // Insert new table
    $insert_stmt = $conn->prepare('
        INSERT INTO availability_tables 
        (table_name, capacity, notes, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ');
    
    if (!$insert_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $insert_stmt->bind_param('sis', $table_name, $capacity, $notes);
    
    if (!$insert_stmt->execute()) {
        throw new Exception('Failed to create table: ' . $insert_stmt->error);
    }
    
    $new_id = $insert_stmt->insert_id;
    $insert_stmt->close();
    
    // Log the activity
    logActivity(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['role'],
        'CREATE',
        'availability_tables',
        $new_id,
        $table_name,
        "Created table - Name: $table_name | Capacity: $capacity"
    );
    
    header('Location: ../availability_set.php?message=' . urlencode("Table '$table_name' created successfully!"));
    exit();
    
} catch (Exception $e) {
    $error_detail = 'An error occurred: ' . $e->getMessage();
    error_log('Table Creation Error: ' . $error_detail);
    header('Location: ../availability_set.php?error=' . urlencode($error_detail));
    exit();
}

