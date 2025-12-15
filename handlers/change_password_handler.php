<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 1; // Use session user_id when available

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        header("Location: ../user_profile.php?error=All password fields are required");
        exit;
    }

    if (strlen($new_password) < 8) {
        header("Location: ../user_profile.php?error=New password must be at least 8 characters long");
        exit;
    }

    if ($new_password !== $confirm_password) {
        header("Location: ../user_profile.php?error=New passwords do not match");
        exit;
    }

    // Fetch user's current password from database
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: ../user_profile.php?error=User not found");
        exit;
    }

    $user = $result->fetch_assoc();

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        header("Location: ../user_profile.php?error=Current password is incorrect");
        exit;
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update password in database
    $update_query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        header("Location: ../user_profile.php?message=Password changed successfully");
        exit;
    } else {
        header("Location: ../user_profile.php?error=Failed to change password");
        exit;
    }
}

header("Location: ../user_profile.php");
exit;
