<?php
session_start();
require __DIR__ . '/../config/db.php';

// Log everything
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session' => $_SESSION,
    'post' => $_POST,
    'server' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? 'N/A'
    ]
];

error_log('=== AVAILABILITY SET DEBUG ===');
error_log(json_encode($log_data, JSON_PRETTY_PRINT));
error_log('==============================');

// Check auth
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    error_log('AUTH FAILED - user_id: ' . ($_SESSION['user_id'] ?? 'EMPTY') . ', role: ' . ($_SESSION['role'] ?? 'EMPTY'));
    die('Auth Check Failed - Redirecting to login');
}

error_log('AUTH OK - Processing availability');

// Get form data
$available_date = isset($_POST['available_date']) ? trim($_POST['available_date']) : '';
$available_time_start = isset($_POST['available_time_start']) ? trim($_POST['available_time_start']) : '';
$available_time_end = isset($_POST['available_time_end']) ? trim($_POST['available_time_end']) : '';
$max_capacity = isset($_POST['max_capacity']) ? intval($_POST['max_capacity']) : 0;
$is_available = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

error_log('Form Data - Date: ' . $available_date . ', Start: ' . $available_time_start . ', End: ' . $available_time_end);

// Convert time format to H:i:s if needed
if (!empty($available_time_start) && strlen($available_time_start) == 5) {
    $available_time_start .= ':00';
}
if (!empty($available_time_end) && strlen($available_time_end) == 5) {
    $available_time_end .= ':00';
}

error_log('After Time Conversion - Start: ' . $available_time_start . ', End: ' . $available_time_end);

// Validation
if (empty($available_date) || empty($available_time_start) || empty($available_time_end)) {
    error_log('VALIDATION FAILED - Missing fields');
    die('Validation Failed - Missing fields');
}

try {
    // Insert new record
    $insert_stmt = $conn->prepare('
        INSERT INTO availability 
        (available_date, available_time_start, available_time_end, max_capacity, current_reservations, is_available, notes)
        VALUES (?, ?, ?, ?, 0, ?, ?)
    ');
    
    if (!$insert_stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    error_log('About to bind params - Types: sssiis');
    error_log('Values: date=' . $available_date . ', start=' . $available_time_start . ', end=' . $available_time_end . ', capacity=' . $max_capacity . ', status=' . $is_available . ', notes=' . $notes);
    
    $insert_stmt->bind_param('sssiis', $available_date, $available_time_start, $available_time_end, $max_capacity, $is_available, $notes);
    
    if (!$insert_stmt->execute()) {
        throw new Exception('Execute failed: ' . $insert_stmt->error);
    }
    
    $insert_id = $insert_stmt->insert_id;
    error_log('INSERT SUCCESSFUL - New ID: ' . $insert_id);
    $insert_stmt->close();
    
    die('Success! Inserted record ID: ' . $insert_id);
    
} catch (Exception $e) {
    error_log('EXCEPTION: ' . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
