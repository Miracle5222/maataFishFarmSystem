<?php
session_start();
ob_start();
include '../config/db.php';
require_once '../vendor/autoload.php';
require_once __DIR__ . '/activity_logger.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$id = intval($_GET['id'] ?? 0);
$status = trim($_GET['status'] ?? '');
$action = trim($_GET['action'] ?? '');
$referrer = $_SERVER['HTTP_REFERER'] ?? '../reservations_list.php';

// Validate
if ($id <= 0) {
    header("Location: " . $referrer);
    exit;
}

// Handle delete action
if ($action === 'delete') {
    // Get reservation details before deletion for logging
    $get_res = $conn->prepare("SELECT r.id, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM reservations r JOIN customers c ON r.customer_id = c.id WHERE r.id = ?");
    $get_res->bind_param("i", $id);
    $get_res->execute();
    $res_result = $get_res->get_result();
    $res_data = $res_result->fetch_assoc();
    $customer_name = $res_data['customer_name'] ?? 'Unknown';
    $get_res->close();
    
    $delete_stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
    $delete_stmt->bind_param("i", $id);
    if ($delete_stmt->execute()) {
        // Log the activity
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['role'] ?? 'staff';
        
        logActivity(
            $conn,
            $user_id,
            $user_type,
            'DELETE',
            'reservation',
            $id,
            $customer_name,
            "Deleted reservation for: $customer_name"
        );
        
        header("Location: " . $referrer . "?success=Reservation deleted");
    } else {
        header("Location: " . $referrer . "?error=Failed to delete reservation");
    }
    $delete_stmt->close();
    exit;
}

// Validate status for update
$valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    header("Location: " . $referrer);
    exit;
}

// Update reservation status
$update_query = "UPDATE reservations SET status = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    // If status is confirmed (approved), mark the availability slot as booked
    if ($status === 'confirmed') {
        // Get reservation details to find the cottage and time
        $fetch_q = "SELECT r.cottage_id, r.reservation_date, r.reservation_time FROM reservations r WHERE r.id = ? LIMIT 1";
        $gstmt = $conn->prepare($fetch_q);
        if ($gstmt) {
            $gstmt->bind_param('i', $id);
            $gstmt->execute();
            $res = $gstmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $cottage_id = $row['cottage_id'];
                $reservation_date = $row['reservation_date'];
                $reservation_time = $row['reservation_time'];
                
                // Find matching availability slot - check if time slot contains the reservation time
                $avail_q = "SELECT id FROM cottage_availability 
                           WHERE cottage_id = ? 
                           AND available_date = ? 
                           AND available_time_start <= ? 
                           AND available_time_end > ?
                           AND status = 'available'
                           LIMIT 1";
                $astmt = $conn->prepare($avail_q);
                if ($astmt) {
                    $astmt->bind_param('isss', $cottage_id, $reservation_date, $reservation_time, $reservation_time);
                    $astmt->execute();
                    $ares = $astmt->get_result();
                    if ($ares && $arow = $ares->fetch_assoc()) {
                        $avail_id = $arow['id'];
                        // Mark this availability slot as booked
                        $update_avail = "UPDATE cottage_availability SET status = 'booked' WHERE id = ?";
                        $ustmt = $conn->prepare($update_avail);
                        if ($ustmt) {
                            $ustmt->bind_param('i', $avail_id);
                            $ustmt->execute();
                            $ustmt->close();
                        }
                    }
                    $astmt->close();
                }
            }
            $gstmt->close();
        }
    }
    // If status is cancelled, mark the availability slot back as available
    elseif ($status === 'cancelled') {
        $fetch_q = "SELECT r.cottage_id, r.reservation_date, r.reservation_time FROM reservations r WHERE r.id = ? LIMIT 1";
        $gstmt = $conn->prepare($fetch_q);
        if ($gstmt) {
            $gstmt->bind_param('i', $id);
            $gstmt->execute();
            $res = $gstmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $cottage_id = $row['cottage_id'];
                $reservation_date = $row['reservation_date'];
                $reservation_time = $row['reservation_time'];
                
                // Find matching availability slot
                $avail_q = "SELECT id FROM cottage_availability 
                           WHERE cottage_id = ? 
                           AND available_date = ? 
                           AND available_time_start <= ? 
                           AND available_time_end > ?
                           AND status = 'booked'
                           LIMIT 1";
                $astmt = $conn->prepare($avail_q);
                if ($astmt) {
                    $astmt->bind_param('isss', $cottage_id, $reservation_date, $reservation_time, $reservation_time);
                    $astmt->execute();
                    $ares = $astmt->get_result();
                    if ($ares && $arow = $ares->fetch_assoc()) {
                        $avail_id = $arow['id'];
                        // Mark this availability slot as available again
                        $update_avail = "UPDATE cottage_availability SET status = 'available' WHERE id = ?";
                        $ustmt = $conn->prepare($update_avail);
                        if ($ustmt) {
                            $ustmt->bind_param('i', $avail_id);
                            $ustmt->execute();
                            $ustmt->close();
                        }
                    }
                    $astmt->close();
                }
            }
            $gstmt->close();
        }
    }
    
    // Log the activity - use appropriate activity type based on status
    $activity_type = 'EDIT';
    if ($status === 'confirmed') {
        $activity_type = 'APPROVE';
    } elseif ($status === 'cancelled') {
        $activity_type = 'REJECT';
    }
    
    $get_customer = $conn->prepare("SELECT CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM reservations r JOIN customers c ON r.customer_id = c.id WHERE r.id = ?");
    $get_customer->bind_param('i', $id);
    $get_customer->execute();
    $cust_res = $get_customer->get_result();
    $cust_data = $cust_res->fetch_assoc();
    $customer_name = $cust_data['customer_name'] ?? 'Unknown';
    $get_customer->close();
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_type = $_SESSION['role'] ?? 'staff';
    
    logActivity(
        $conn,
        $user_id,
        $user_type,
        $activity_type,
        'reservation',
        $id,
        $customer_name,
        "Updated reservation " . $reservation['reservation_number'] . " status to $status | Type: " . ucfirst($reservation['reservation_type']) . " | Guests: " . $reservation['num_guests']
    );
    
    // Redirect immediately
    header("Location: " . $referrer . "?success=Reservation status updated");
    ob_end_flush();
    flush();

    // Fire-and-forget call to send email in background to avoid blocking the approval flow
    $host = '127.0.0.1';
    $port = 80;
    $path = '/maataFishFarmSystem/handlers/send_reservation_email.php';
    $query = http_build_query(['id' => $id, 'status' => $status]);

    $fp = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($fp) {
        $out = "GET {$path}?{$query} HTTP/1.1\r\n";
        $out .= "Host: {$host}\r\n";
        $out .= "Connection: Close\r\n\r\n";
        fwrite($fp, $out);
        fclose($fp);
    }
} else {
    header("Location: " . $referrer . "?error=Failed to update reservation");
}
exit;
