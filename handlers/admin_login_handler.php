<?php
session_start();
include '../config/db.php';

// Accept POST
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: ../admin_login.php?error=' . urlencode('Please enter username and password'));
    exit;
}

// Find user by username or email
$query = "SELECT id, username, full_name, email,status,created_at,role, password FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1";
$stmt = $conn->prepare($query);
if (!$stmt) {
    header('Location: ../admin_login.php?error=' . urlencode('Database error'));
    exit;
}
$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $row = $res->fetch_assoc()) {
    $stored = $row['password'];

    $password_ok = false;
    // First try password_verify (newer hashed passwords)
    if (password_verify($password, $stored)) {
        $password_ok = true;
    } else {
        // Fallback: check legacy SHA2 hash
        if (hash('sha256', $password) === $stored) {
            $password_ok = true;
            // Migrate to password_hash for future logins
            $new_hash = password_hash($password, PASSWORD_BCRYPT);
            $u = $conn->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
            if ($u) {
                $u->bind_param('si', $new_hash, $row['id']);
                $u->execute();
                $u->close();
            }
        }
    }

    if ($password_ok) {
        // Login success
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
         $_SESSION['email'] = $row['email'];
        $_SESSION['full_name'] = $row['full_name'];
        $_SESSION['role'] = $row['role'];
      $_SESSION['status'] = $row['status'];
            $_SESSION['created_at'] = $row['created_at'];
        header('Location: ../index.php');
        exit;
    }
}

header('Location: ../admin_login.php?error=' . urlencode('Invalid credentials'));
exit;
