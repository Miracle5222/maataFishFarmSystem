<?php
session_start();
require __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin/manager/staff
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get form data
$available_date = isset($_POST['available_date']) ? trim($_POST['available_date']) : '';
$available_time_start = isset($_POST['available_time_start']) ? trim($_POST['available_time_start']) : '';
$available_time_end = isset($_POST['available_time_end']) ? trim($_POST['available_time_end']) : '';
$max_capacity = isset($_POST['max_capacity']) ? intval($_POST['max_capacity']) : 0;
$is_available = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Convert time format to H:i:s if needed
if (!empty($available_time_start) && strlen($available_time_start) == 5) {
    $available_time_start .= ':00';
}
if (!empty($available_time_end) && strlen($available_time_end) == 5) {
    $available_time_end .= ':00';
}

// Validation
if (empty($available_date) || empty($available_time_start) || empty($available_time_end)) {
    header('Location: ../availability_set.php?error=' . urlencode('Please fill in all required fields'));
    exit();
}

// Validate dates and times
$date_check = DateTime::createFromFormat('Y-m-d', $available_date);
if (!$date_check || $date_check->format('Y-m-d') !== $available_date) {
    header('Location: ../availability_set.php?error=' . urlencode('Invalid date format'));
    exit();
}

$start_check = DateTime::createFromFormat('H:i:s', $available_time_start);
$end_check = DateTime::createFromFormat('H:i:s', $available_time_end);

if (!$start_check || !$end_check) {
    header('Location: ../availability_set.php?error=' . urlencode('Invalid time format'));
    exit();
}

// Check if end time is after start time
if ($available_time_start >= $available_time_end) {
    header('Location: ../availability_set.php?error=' . urlencode('End time must be after start time'));
    exit();
}

// Validate capacity
if ($max_capacity <= 0) {
    header('Location: ../availability_set.php?error=' . urlencode('Capacity must be greater than 0'));
    exit();
}

// Validate date is not in the past
if (strtotime($available_date) < strtotime(date('Y-m-d'))) {
    header('Location: ../availability_set.php?error=' . urlencode('Cannot set availability for past dates'));
    exit();
}

try {
    // Log the incoming data
    error_log('Availability Set - Date: ' . $available_date . ', Start: ' . $available_time_start . ', End: ' . $available_time_end . ', Capacity: ' . $max_capacity . ', Status: ' . $is_available);
    
    // Check if availability slot already exists for this EXACT date and time
    $check_stmt = $conn->prepare('
        SELECT id, max_capacity, is_available, current_reservations FROM availability 
        WHERE available_date = ? 
        AND available_time_start = ? 
        AND available_time_end = ?
    ');
    
    if (!$check_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $check_stmt->bind_param('sss', $available_date, $available_time_start, $available_time_end);
    if (!$check_stmt->execute()) {
        throw new Exception('Check query failed: ' . $check_stmt->error);
    }
    $check_result = $check_stmt->get_result();
    
    // Also check for overlapping time slots on the same date
    $overlap_stmt = $conn->prepare('
        SELECT id, available_time_start, available_time_end FROM availability 
        WHERE available_date = ? 
        AND (
            (available_time_start < ? AND available_time_end > ?)
            OR (available_time_start < ? AND available_time_end > ?)
            OR (available_time_start >= ? AND available_time_end <= ?)
        )
    ');
    
    if (!$overlap_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $overlap_stmt->bind_param('sssssss', $available_date, $available_time_end, $available_time_start, $available_time_start, $available_time_end, $available_time_start, $available_time_end);
    if (!$overlap_stmt->execute()) {
        throw new Exception('Overlap check failed: ' . $overlap_stmt->error);
    }
    $overlap_result = $overlap_stmt->get_result();
    
    if ($overlap_result->num_rows > 0) {
        $overlap_row = $overlap_result->fetch_assoc();
        throw new Exception('Time slot overlaps with existing availability: ' . $overlap_row['available_time_start'] . ' to ' . $overlap_row['available_time_end']);
    }
    
    $overlap_stmt->close();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $row = $check_result->fetch_assoc();
        $existing_id = $row['id'];
        
        // If trying to close a time slot that has reservations, warn the user
        if ($is_available == 0 && $row['current_reservations'] > 0) {
            throw new Exception('Cannot close this time slot - there are ' . $row['current_reservations'] . ' existing reservations. Please cancel them first or keep the slot available.');
        }
        
        $update_stmt = $conn->prepare('
            UPDATE availability 
            SET max_capacity = ?, is_available = ?, notes = ?, updated_at = NOW()
            WHERE id = ?
        ');
        
        if (!$update_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $update_stmt->bind_param('iisi', $max_capacity, $is_available, $notes, $existing_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update availability: ' . $update_stmt->error);
        }
        
        $update_stmt->close();
        header('Location: ../availability_set.php?message=' . urlencode('Availability updated successfully'));
    } else {
        // Insert new record
        $insert_stmt = $conn->prepare('
            INSERT INTO availability 
            (available_date, available_time_start, available_time_end, max_capacity, current_reservations, is_available, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, 0, ?, ?, NOW(), NOW())
        ');
        
        if (!$insert_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $insert_stmt->bind_param('sssiis', $available_date, $available_time_start, $available_time_end, $max_capacity, $is_available, $notes);
        
        if (!$insert_stmt->execute()) {
            $error_msg = 'Failed to set availability: ' . $insert_stmt->error;
            error_log($error_msg);
            throw new Exception($error_msg);
        }
        
        $insert_stmt->close();
        header('Location: ../availability_set.php?message=' . urlencode('Availability set successfully'));
    }
    
    $check_stmt->close();
    exit();
    
} catch (Exception $e) {
    $error_detail = 'An error occurred: ' . $e->getMessage();
    error_log('Availability Error: ' . $error_detail);
    header('Location: ../availability_set.php?error=' . urlencode($error_detail));
    exit();
}
