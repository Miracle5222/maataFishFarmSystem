<?php
session_start();
include '../config/db.php';
include 'activity_logger.php';

// Check admin/staff authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    header('Location: ../admin_login.php?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $customer_name = trim($_POST['customer_name'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $cottage_id = isset($_POST['cottage_id']) ? intval($_POST['cottage_id']) : null;
    $num_guests = intval($_POST['num_guests'] ?? 0);
    $reservation_date = trim($_POST['reservation_date'] ?? '');
    $reservation_time = trim($_POST['reservation_time'] ?? '');
    $special_requests = trim($_POST['special_requests'] ?? '');
    $status = trim($_POST['status'] ?? 'pending');

    // Validation
    $errors = [];

    if (empty($customer_name)) {
        $errors[] = "Customer name is required";
    }

    if (empty($contact_phone)) {
        $errors[] = "Phone number is required";
    }

    if (!$cottage_id || $cottage_id <= 0) {
        $errors[] = "Please select a cottage";
    }

    if ($num_guests < 1 || $num_guests > 200) {
        $errors[] = "Number of guests must be between 1 and 200";
    }

    if (empty($reservation_date)) {
        $errors[] = "Reservation date is required";
    }

    if (empty($reservation_time)) {
        $errors[] = "Reservation time is required";
    }

    if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        $errors[] = "Invalid reservation status";
    }

    // Validate cottage availability
    if (!$errors && $cottage_id) {
        $cottage_check = $conn->prepare("SELECT id, available_date, available_date_from, available_date_to, available_time_start, available_time_end, status FROM cottages WHERE id = ?");
        $cottage_check->bind_param("i", $cottage_id);
        $cottage_check->execute();
        $cottage_result = $cottage_check->get_result();

        if ($cottage_result->num_rows === 0) {
            $errors[] = "Selected cottage does not exist";
        } else {
            $cottage = $cottage_result->fetch_assoc();

            if ($cottage['status'] !== 'available') {
                $errors[] = "Selected cottage is not available";
            }

            // Check date range
            $date_from = $cottage['available_date_from'] ?? $cottage['available_date'];
            $date_to = $cottage['available_date_to'] ?? $cottage['available_date'];
            
            $res_date_time = strtotime($reservation_date);
            $from_time = strtotime($date_from);
            $to_time = strtotime($date_to);
            
            if ($res_date_time < $from_time || $res_date_time > $to_time) {
                $errors[] = "Cottage is not available on the selected date";
            }

            if ($reservation_time < $cottage['available_time_start'] || $reservation_time > $cottage['available_time_end']) {
                $errors[] = "Cottage is not available at the selected time";
            }
        }
        $cottage_check->close();
    }

    // Check if the time slot is already booked (only active reservations block availability)
    if (!$errors && $cottage_id && $reservation_date && $reservation_time) {
        $booked_check = $conn->prepare("SELECT id FROM reservations WHERE cottage_id = ? AND reservation_date = ? AND reservation_time = ? AND status IN ('pending', 'confirmed')");
        $booked_check->bind_param("iss", $cottage_id, $reservation_date, $reservation_time);
        $booked_check->execute();
        $booked_result = $booked_check->get_result();

        if ($booked_result->num_rows > 0) {
            $errors[] = "This time slot is already booked for this cottage";
        }
        $booked_check->close();
    }

    if (!empty($errors)) {
        $error_msg = implode(", ", $errors);
        header("Location: ../manual_cottage_reservation.php?error=" . urlencode($error_msg));
        exit;
    }

    try {
        // Create or get customer ID
        // For walk-in customers, we always create a new customer record
        $customer_id = null;
        
        // Split the full name into first and last name
        $name_parts = explode(' ', trim($customer_name), 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        
        // Always create a new customer record for walk-in customers
        // This ensures each walk-in gets their own record with their correct information
        $insert_customer = $conn->prepare("INSERT INTO customers (first_name, last_name, email, phone, government_id_verified, created_at, updated_at) VALUES (?, ?, ?, ?, 0, NOW(), NOW())");
        if ($insert_customer) {
            $insert_customer->bind_param("ssss", $first_name, $last_name, $contact_email, $contact_phone);
            if ($insert_customer->execute()) {
                $customer_id = $conn->insert_id;
            } else {
                throw new Exception("Failed to create customer record");
            }
            $insert_customer->close();
        } else {
            throw new Exception("Could not prepare customer insert statement");
        }

        if (!$customer_id) {
            throw new Exception("Could not create or find customer record");
        }

        // Generate unique reservation number
        $reservation_number = "RES-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Check if reservation number already exists
        $check_res_query = "SELECT id FROM reservations WHERE reservation_number = ?";
        while (true) {
            $stmt = $conn->prepare($check_res_query);
            if (!$stmt) {
                throw new Exception('Reservation check prepare failed');
            }
            $stmt->bind_param("s", $reservation_number);
            $stmt->execute();
            $res = $stmt->get_result();
            $exists = $res && $res->num_rows > 0;
            if ($res) $res->free();
            $stmt->close();
            if (!$exists) break;
            $reservation_number = "RES-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        // Insert reservation
        $hasCottageColumn = false;
        
        $colRes = $conn->query("SHOW COLUMNS FROM reservations LIKE 'cottage_id'");
        if ($colRes) {
            $hasCottageColumn = $colRes->num_rows > 0;
            if ($colRes) $colRes->free();
        }

        // Fetch cottage price for total_amount calculation
        $cottage_price_query = "SELECT price FROM cottages WHERE id = ?";
        $price_stmt = $conn->prepare($cottage_price_query);
        $cottage_price = 0;
        if ($price_stmt) {
            $price_stmt->bind_param("i", $cottage_id);
            $price_stmt->execute();
            $price_result = $price_stmt->get_result();
            if ($price_result && $price_row = $price_result->fetch_assoc()) {
                $cottage_price = floatval($price_row['price']);
            }
            $price_stmt->close();
        }
        
        // Note: total_amount will be calculated when reservation is marked as completed (checkout)
        // At creation time, total_amount defaults to 0

        // Build INSERT statement
        $columns = "reservation_number, customer_id, reservation_type, num_guests, reservation_date, reservation_time, special_requests, status, contact_phone, contact_email";
        $placeholders = "?, ?, 'cottage', ?, ?, ?, ?, ?, ?, ?";
        $bindTypes = "siissssss";
        $params = array();
        $params[] = $reservation_number;
        $params[] = $customer_id;
        $params[] = $num_guests;
        $params[] = $reservation_date;
        $params[] = $reservation_time;
        $params[] = $special_requests;
        $params[] = $status;
        $params[] = $contact_phone;
        $params[] = $contact_email;

        if ($hasCottageColumn) {
            $columns .= ", cottage_id";
            $placeholders .= ", ?";
            $bindTypes .= "i";
            $params[] = $cottage_id;
        }

        $columns .= ", is_manual, created_at, updated_at";
        $placeholders .= ", 1, NOW(), NOW()";

        $insert_reservation = "INSERT INTO reservations (" . $columns . ") VALUES (" . $placeholders . ")";

        $stmt = $conn->prepare($insert_reservation);
        if (!$stmt) {
            throw new Exception("Prepare failed (errno=" . $conn->errno . "): " . $conn->error);
        }

        // Bind all values dynamically using references
        $refs = array();
        $refs[] = &$bindTypes;
        foreach ($params as $key => $value) {
            $refs[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $refs);

        if ($stmt->execute()) {
            $reservation_id = $conn->insert_id;
            
            // Log the activity
            $user_id = $_SESSION['user_id'];
            $user_type = $_SESSION['role'];
            
            logActivity(
                $conn,
                $user_id,
                $user_type,
                'CREATE',
                'reservation',
                $reservation_id,
                $reservation_number,
                "Walk-in customer checked in to cottage | Customer: $customer_name | Phone: $contact_phone | Guests: $num_guests | Check-In Date: $reservation_date | Check-In Time: $reservation_time | Status: $status"
            );

            // If status is 'confirmed', mark the availability slot as booked
            if ($status === 'confirmed') {
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

            header("Location: ../manual_cottage_reservation.php?success=1");
            exit;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("Manual cottage reservation error: " . $e->getMessage());
        header("Location: ../manual_cottage_reservation.php?error=" . urlencode("An error occurred: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../manual_cottage_reservation.php");
    exit;
}
