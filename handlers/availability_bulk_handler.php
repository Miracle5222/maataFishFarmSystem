<?php
session_start();
require __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin/manager/staff
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get form data
$select_weekdays = isset($_POST['select_weekdays']) ? true : false;
$select_weekends = isset($_POST['select_weekends']) ? true : false;
$select_all = isset($_POST['select_all']) ? true : false;
$bulk_time_start = isset($_POST['bulk_time_start']) ? trim($_POST['bulk_time_start']) : '10:00';
$bulk_time_end = isset($_POST['bulk_time_end']) ? trim($_POST['bulk_time_end']) : '20:00';
$bulk_capacity = isset($_POST['bulk_capacity']) ? intval($_POST['bulk_capacity']) : 50;

// Convert time format to H:i:s if needed
if (!empty($bulk_time_start) && strlen($bulk_time_start) == 5) {
    $bulk_time_start .= ':00';
}
if (!empty($bulk_time_end) && strlen($bulk_time_end) == 5) {
    $bulk_time_end .= ':00';
}

// Validation
if (empty($bulk_time_start) || empty($bulk_time_end)) {
    header('Location: ../availability_set.php?error=' . urlencode('Please select time range'));
    exit();
}

if (!$select_weekdays && !$select_weekends && !$select_all) {
    header('Location: ../availability_set.php?error=' . urlencode('Please select at least one day option'));
    exit();
}

if ($bulk_time_start >= $bulk_time_end) {
    header('Location: ../availability_set.php?error=' . urlencode('End time must be after start time'));
    exit();
}

if ($bulk_capacity <= 0) {
    header('Location: ../availability_set.php?error=' . urlencode('Capacity must be greater than 0'));
    exit();
}

try {
    $dates_to_process = [];
    $current_date = date('Y-m-d');
    
    // Generate list of dates to process
    for ($i = 0; $i < 30; $i++) {
        $check_date = date('Y-m-d', strtotime("+$i days"));
        
        // Skip past dates
        if (strtotime($check_date) < strtotime($current_date)) {
            continue;
        }
        
        $day_of_week = date('N', strtotime($check_date)); // 1=Monday, 7=Sunday
        
        $include = false;
        
        if ($select_all) {
            $include = true;
        } elseif ($select_weekdays && $day_of_week >= 1 && $day_of_week <= 5) {
            $include = true;
        } elseif ($select_weekends && ($day_of_week == 6 || $day_of_week == 7)) {
            $include = true;
        }
        
        if ($include) {
            $dates_to_process[] = $check_date;
        }
    }
    
    if (empty($dates_to_process)) {
        header('Location: ../availability_set.php?error=' . urlencode('No valid dates found to process'));
        exit();
    }
    
    $success_count = 0;
    $is_available = 1; // Always set as available in bulk
    $notes = ''; // Empty notes for bulk operations
    
    // Process each date
    foreach ($dates_to_process as $process_date) {
        // Check if this slot already exists
        $check_stmt = $conn->prepare('
            SELECT id FROM availability 
            WHERE available_date = ? 
            AND available_time_start = ? 
            AND available_time_end = ?
        ');
        
        if (!$check_stmt) {
            continue;
        }
        
        $check_stmt->bind_param('sss', $process_date, $bulk_time_start, $bulk_time_end);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing
            $row = $check_result->fetch_assoc();
            $existing_id = $row['id'];
            
            $update_stmt = $conn->prepare('
                UPDATE availability 
                SET max_capacity = ?, is_available = ?, updated_at = NOW()
                WHERE id = ?
            ');
            
            if ($update_stmt) {
                $update_stmt->bind_param('iii', $bulk_capacity, $is_available, $existing_id);
                if ($update_stmt->execute()) {
                    $success_count++;
                }
                $update_stmt->close();
            }
        } else {
            // Insert new
            $insert_stmt = $conn->prepare('
                INSERT INTO availability 
                (available_date, available_time_start, available_time_end, max_capacity, current_reservations, is_available, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0, ?, ?, NOW(), NOW())
            ');
            
            if ($insert_stmt) {
                $insert_stmt->bind_param('sssiis', $process_date, $bulk_time_start, $bulk_time_end, $bulk_capacity, $is_available, $notes);
                if ($insert_stmt->execute()) {
                    $success_count++;
                }
                $insert_stmt->close();
            }
        }
        
        $check_stmt->close();
    }
    
    if ($success_count > 0) {
        header('Location: ../availability_set.php?message=' . urlencode("Availability set for $success_count days successfully"));
    } else {
        header('Location: ../availability_set.php?error=' . urlencode('Failed to set availability for selected days'));
    }
    exit();
    
} catch (Exception $e) {
    error_log('Availability Bulk Error: ' . $e->getMessage());
    header('Location: ../availability_set.php?error=' . urlencode('An error occurred: ' . $e->getMessage()));
    exit();
}
