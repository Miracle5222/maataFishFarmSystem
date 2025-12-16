<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $reservation_type = trim($_POST['reservation_type'] ?? '');
    $num_guests = intval($_POST['num_guests'] ?? 0);
    $reservation_date = trim($_POST['reservation_date'] ?? '');
    $reservation_time = trim($_POST['reservation_time'] ?? '');
    $special_requests = trim($_POST['special_requests'] ?? '');


    // Validation
    $errors = [];

    if (empty($name)) {
        $errors[] = "Full name is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }

    if (empty($reservation_type) || !in_array($reservation_type, ['dine-in', 'farm visit', 'private-event'])) {
        $errors[] = "Valid reservation type is required";
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

    if (!empty($errors)) {
        header("Location: ../client/booking.php?error=" . urlencode(implode(", ", $errors)));
        exit;
    }

    try {
        // Check if customer exists by email, if not create new customer
        $customer_query = "SELECT id FROM customers WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($customer_query);

        if (!$stmt) {
            throw new Exception("Customer query prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $customer_id = $customer['id'];
        } else {
            // Extract first and last name from full name
            $name_parts = explode(' ', $name, 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

            // Create new customer
            $insert_customer = "INSERT INTO customers (first_name, last_name, email, phone, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($insert_customer);

            if (!$stmt) {
                throw new Exception("Customer insert prepare failed: " . $conn->error);
            }

            $stmt->bind_param("ssss", $first_name, $last_name, $email, $phone);

            if (!$stmt->execute()) {
                throw new Exception("Customer insert failed: " . $stmt->error);
            }

            $customer_id = $conn->insert_id;
        }

        // Generate unique reservation number
        $reservation_number = "RES-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Check if reservation number already exists
        $check_res_query = "SELECT id FROM reservations WHERE reservation_number = ?";
        $stmt = $conn->prepare($check_res_query);
        $stmt->bind_param("s", $reservation_number);
        $stmt->execute();

        while ($stmt->get_result()->num_rows > 0) {
            $reservation_number = "RES-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare($check_res_query);
            $stmt->bind_param("s", $reservation_number);
            $stmt->execute();
        }

        // Insert reservation
        $insert_reservation = "INSERT INTO reservations 
                              (reservation_number, customer_id, reservation_type, num_guests, reservation_date, 
                               reservation_time, special_requests, status, contact_phone, contact_email, created_at, updated_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())";

        $stmt = $conn->prepare($insert_reservation);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters: s=string, i=integer
        // reservation_number(s), customer_id(i), reservation_type(s), num_guests(i), 
        // reservation_date(s), reservation_time(s), special_requests(s), contact_phone(s), contact_email(s)
        $stmt->bind_param(
            "sisisssss",
            $reservation_number,
            $customer_id,
            $reservation_type,
            $num_guests,
            $reservation_date,
            $reservation_time,
            $special_requests,
            $phone,
            $email
        );

        if ($stmt->execute()) {
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
