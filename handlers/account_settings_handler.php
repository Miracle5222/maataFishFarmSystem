<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if this is customer profile update from admin
        if (isset($_POST['customer_id'])) {
            // Admin customer profile update
            $customer_id = (int)$_POST['customer_id'];
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $barangay = trim($_POST['barangay'] ?? '');
            $municipality = trim($_POST['municipality'] ?? '');
            $customer_type = trim($_POST['customer_type'] ?? 'online_customer');
        
            if ($first_name === '' || $last_name === '') {
                header('Location: ../customers_profile.php?id=' . $customer_id . '&error=First and last name are required');
                exit;
            }
        
            $stmt = $conn->prepare('UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, barangay = ?, municipality = ?, customer_type = ?, updated_at = NOW() WHERE id = ?');
            $stmt->bind_param('ssssssssi', $first_name, $last_name, $email, $phone, $address, $barangay, $municipality, $customer_type, $customer_id);
        
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: ../customers_list.php?success=Customer updated successfully');
            } else {
                $stmt->close();
                header('Location: ../customers_profile.php?id=' . $customer_id . '&error=Failed to update customer');
            }
            exit;
        }
    $user_id = $_SESSION['user_id'] ?? 1; // Use session user_id when available

    // Collect notification preferences
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $order_updates = isset($_POST['order_updates']) ? 1 : 0;
    $system_alerts = isset($_POST['system_alerts']) ? 1 : 0;

    // Collect account preferences
    $language = $_POST['language'] ?? 'en';
    $timezone = $_POST['timezone'] ?? 'Asia/Manila';

    // Validate inputs
    $allowed_languages = ['en', 'tl'];
    $allowed_timezones = ['Asia/Manila', 'UTC'];

    if (!in_array($language, $allowed_languages)) {
        $language = 'en';
    }

    if (!in_array($timezone, $allowed_timezones)) {
        $timezone = 'Asia/Manila';
    }

    // Store settings in session (in production, save to database)
    $_SESSION['email_notifications'] = $email_notifications;
    $_SESSION['order_updates'] = $order_updates;
    $_SESSION['system_alerts'] = $system_alerts;
    $_SESSION['language'] = $language;
    $_SESSION['timezone'] = $timezone;

    // Optional: Save to database if you have a user_settings table
    // Uncomment below if you have a settings table in your database
    /*
    $update_query = "UPDATE user_settings SET 
                     email_notifications = ?, 
                     order_updates = ?, 
                     system_alerts = ?, 
                     language = ?, 
                     timezone = ?,
                     updated_at = NOW()
                     WHERE user_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iiiissi", $email_notifications, $order_updates, $system_alerts, $language, $timezone, $user_id);
    
    if ($stmt->execute()) {
        header("Location: ../user_profile.php?message=Account settings updated successfully");
        exit;
    } else {
        header("Location: ../user_profile.php?error=Failed to update settings");
        exit;
    }
    */

    header("Location: ../user_profile.php?message=Account settings updated successfully");
    exit;
}

header("Location: ../user_profile.php");
exit;
