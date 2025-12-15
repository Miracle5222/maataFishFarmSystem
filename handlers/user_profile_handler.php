<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 1; // Use session user_id when available

    if ($action === 'edit_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');

        // Validation
        if (empty($full_name) || empty($email) || empty($username)) {
            header("Location: ../user_profile.php?error=All fields are required");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../user_profile.php?error=Invalid email format");
            exit;
        }

        // Check if email or username already exists (excluding current user)
        $check_query = "SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ssi", $email, $username, $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            header("Location: ../user_profile.php?error=Email or username already exists");
            exit;
        }

        // Update user profile
        $update_query = "UPDATE users SET full_name = ?, email = ?, username = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $full_name, $email, $username, $user_id);

        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $username;

            header("Location: ../user_profile.php?message=Profile updated successfully");
            exit;
        } else {
            header("Location: ../user_profile.php?error=Failed to update profile");
            exit;
        }
    }
}

header("Location: ../user_profile.php");
exit;
