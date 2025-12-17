<?php
// auth_admin.php - include this at the top of admin pages to require login
session_start();

// Allowed roles
$allowed_roles = ['admin', 'staff', 'manager'];

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    // Not logged in or unauthorized
    header('Location: admin_login.php');
    exit;
}
