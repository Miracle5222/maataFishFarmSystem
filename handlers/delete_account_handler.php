<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? 1;
    $confirm_delete_password = $_POST['confirm_delete_password'] ?? '';

    if (empty($confirm_delete_password)) {
        header("Location: ../user_profile.php?error=Password is required to delete account");
        exit;
    }

    // Fetch user's password from database
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

    // Verify password
    if (!password_verify($confirm_delete_password, $user['password'])) {
        header("Location: ../user_profile.php?error=Password is incorrect");
        exit;
    }

    // Delete user account from database
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        // Destroy session
        session_destroy();

        // Redirect to login page or home page
        header("Location: ../index.php?message=Account deleted successfully");
        exit;
    } else {
        header("Location: ../user_profile.php?error=Failed to delete account");
        exit;
    }
}

header("Location: ../user_profile.php");
exit;
