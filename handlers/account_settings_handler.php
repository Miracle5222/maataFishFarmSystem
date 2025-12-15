<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
