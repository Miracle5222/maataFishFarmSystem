<?php
session_start();
include '../config/db.php';
include 'activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $reservation_type = trim($_POST['reservation_type'] ?? '');
    $cottage_id = isset($_POST['cottage_id']) ? intval($_POST['cottage_id']) : null;
    $table_id = isset($_POST['table_id']) ? intval($_POST['table_id']) : null;
    $num_guests = intval($_POST['num_guests'] ?? 0);
    $reservation_date = trim($_POST['reservation_date'] ?? '');
    $reservation_time = trim($_POST['reservation_time'] ?? '');
    $special_requests = trim($_POST['special_requests'] ?? '');


    // Validation
    $errors = [];

    if ($customer_id <= 0) {
        $errors[] = "Invalid customer session. Please log in again.";
    }

    if (empty($name)) {
        $errors[] = "Customer name is required";
    }

    if (empty($reservation_type) || !in_array($reservation_type, ['dine-in', 'farm-visit', 'private-events','cottage'])) {
        $errors[] = "Valid reservation type is required";
    }

    if ($reservation_type === 'cottage' && (!$cottage_id || $cottage_id <= 0)) {
        $errors[] = "Please select a cottage";
    }

    if ($reservation_type === 'dine-in' && (!$table_id || $table_id <= 0)) {
        $errors[] = "Please select a dining table";
    }

    if ($num_guests < 1 || $num_guests > 200) {
        $errors[] = "Number of guests must be between 1 and 200";
    }

    if (empty($reservation_date) || strtotime($reservation_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Reservation date must be today or in the future";
    }

    if (empty($reservation_time)) {
        $errors[] = "Reservation time is required";
    }

    // Validate cottage availability if cottage reservation
    if ($reservation_type === 'cottage' && $cottage_id) {
        $cottage_check = $conn->prepare("SELECT available_date, available_date_from, available_date_to, available_time_start, available_time_end, status FROM cottages WHERE id = ?");
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

            // Check date range - use available_date_from and available_date_to if available
            $date_from = $cottage['available_date_from'] ?? $cottage['available_date'];
            $date_to = $cottage['available_date_to'] ?? $cottage['available_date'];
            
            $res_date_time = strtotime($reservation_date);
            $from_time = strtotime($date_from);
            $to_time = strtotime($date_to);
            
            if ($res_date_time < $from_time || $res_date_time > $to_time) {
                $errors[] = "Cottage is not available on selected date";
            }

            if ($reservation_time < $cottage['available_time_start'] || $reservation_time > $cottage['available_time_end']) {
                $errors[] = "Cottage is not available at selected time";
            }
        }
        $cottage_check->close();
    }

    // Validate table availability if dine-in reservation
    if ($reservation_type === 'dine-in' && $table_id) {
        $table_check = $conn->prepare("SELECT id, capacity, status FROM availability_tables WHERE id = ?");
        $table_check->bind_param("i", $table_id);
        $table_check->execute();
        $table_result = $table_check->get_result();

        if ($table_result->num_rows === 0) {
            $errors[] = "Selected table does not exist";
        } else {
            $table = $table_result->fetch_assoc();

            if ($table['status'] !== 'available') {
                $errors[] = "Selected table is not available";
            }

            if ($num_guests > $table['capacity']) {
                $errors[] = "Number of guests (" . $num_guests . ") exceeds table capacity (" . $table['capacity'] . ")";
            }
        }
        $table_check->close();
    }

    if (!empty($errors)) {
        header("Location: ../client/booking.php?type=" . urlencode($reservation_type) . "&error=" . urlencode(implode(", ", $errors)));
        exit;
    }

    try {
        // Get customer contact information
        $customer_query = "SELECT email, phone FROM customers WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($customer_query);

        if (!$stmt) {
            throw new Exception("Customer query prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Customer not found");
        }

        $customer = $result->fetch_assoc();
        $email = $customer['email'];
        $phone = $customer['phone'];
        $stmt->close();

        // Generate unique reservation number
        $reservation_number = "RES-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Check if reservation number already exists (ensure results are freed/closed to avoid commands-out-of-sync)
        $check_res_query = "SELECT id FROM reservations WHERE reservation_number = ?";
        while (true) {
            $stmt = $conn->prepare($check_res_query);
            if (!$stmt) {
                $err = trim($conn->error ?: '');
                error_log('[booking_handler] reservation check prepare failed errno=' . $conn->errno . ' err=' . $err);
                throw new Exception('Reservation check prepare failed');
            }
            $stmt->bind_param("s", $reservation_number);
            $stmt->execute();
            $res = $stmt->get_result();
            $exists = $res && $res->num_rows > 0;
            if ($res) $res->free();
            $stmt->close();
            if (!$exists) break;
            // generate a new number and loop
            $reservation_number = "RES-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        // Insert reservation
        $hasCottageColumn = false;
        $hasTableColumn = false;
        
        $colRes = $conn->query("SHOW COLUMNS FROM reservations LIKE 'cottage_id'");
        if ($colRes) {
            $hasCottageColumn = $colRes->num_rows > 0;
            if ($colRes) $colRes->free();
        }
        
        $colRes2 = $conn->query("SHOW COLUMNS FROM reservations LIKE 'table_id'");
        if ($colRes2) {
            $hasTableColumn = $colRes2->num_rows > 0;
            if ($colRes2) $colRes2->free();
        }

        // Build INSERT statement based on available columns
        $columns = "reservation_number, customer_id, reservation_type, num_guests, reservation_date, reservation_time, special_requests, status, contact_phone, contact_email";
        $placeholders = "?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?";
        $bindTypes = "sisisssss";
        $bindValues = [$reservation_number, $customer_id, $reservation_type, $num_guests, $reservation_date, $reservation_time, $special_requests, $phone, $email];

        if ($hasCottageColumn && $cottage_id !== null) {
            $columns .= ", cottage_id";
            $placeholders .= ", ?";
            $bindTypes .= "i";
            $bindValues[] = $cottage_id;
        }

        if ($hasTableColumn && $table_id !== null) {
            $columns .= ", table_id";
            $placeholders .= ", ?";
            $bindTypes .= "i";
            $bindValues[] = $table_id;
        }

        $columns .= ", created_at, updated_at";
        $placeholders .= ", NOW(), NOW()";

        $insert_reservation = "INSERT INTO reservations (" . $columns . ") VALUES (" . $placeholders . ")";

        $stmt = $conn->prepare($insert_reservation);
        if (!$stmt) {
            $err = trim($conn->error ?: '');
            error_log('[booking_handler] Prepare failed errno=' . $conn->errno . ' err=' . $err);
            throw new Exception("Prepare failed (errno=" . $conn->errno . "): " . ($err ?: 'unknown error'));
        }

        // Bind all values dynamically
        if (!empty($bindValues)) {
            $stmt->bind_param($bindTypes, ...$bindValues);
        }

        if ($stmt->execute()) {
            $reservation_id = $conn->insert_id;
            // Log the activity
            $user_id = $customer_id;
            $user_type = 'customer';
            if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
                $user_id = $_SESSION['user_id'];
                $user_type = $_SESSION['role'];
            }
            logActivity(
                $conn,
                $user_id,
                $user_type,
                'CREATE',
                'reservation',
                $reservation_id,
                $reservation_number,
                "Created $reservation_type reservation for $name | Guests: $num_guests | Date: $reservation_date | Time: $reservation_time" . ($cottage_id ? " | Cottage ID: $cottage_id" : "")
            );
            header("Location: ../client/booking.php?success=1");
            exit;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        header("Location: ../client/booking.php?error=" . urlencode("An error occurred: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../client/booking.php");
    exit;
}
