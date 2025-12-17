<?php
// Handle forgot-password form: generate temporary password and email it to user
session_start();
include '../config/db.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    header('Location: ../forgot_password.php?error=' . urlencode('Please enter your email'));
    exit;
}

// Find user by email
$stmt = $conn->prepare('SELECT id, full_name FROM users WHERE email = ? AND status = "active" LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || !$user = $res->fetch_assoc()) {
    // Do not reveal whether email exists; respond with generic message
    header('Location: ../forgot_password.php?message=' . urlencode('If the email exists, a temporary password has been sent.'));
    exit;
}

$user_id = (int)$user['id'];
$full_name = $user['full_name'];

// Generate temporary password
$temp_password = substr(bin2hex(random_bytes(4)), 0, 8); // 8 hex chars

// Update user's password in DB (using SHA2 to match existing login)
$upd = $conn->prepare('UPDATE users SET password = SHA2(?, 256), updated_at = NOW() WHERE id = ?');
$upd->bind_param('si', $temp_password, $user_id);
$ok = $upd->execute();

// Prepare email
$emailConfigFile = __DIR__ . '/../config/email.php';
$emailConfig = file_exists($emailConfigFile) ? include $emailConfigFile : [];

$sent = false;
$log = '';
$time = date('Y-m-d H:i:s');

if ($ok) {
    // Send temp password via PHPMailer
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $emailConfig['host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['username'] ?? '';
        $mail->Password = $emailConfig['password'] ?? '';
        $mail->SMTPSecure = $emailConfig['secure'] ?? 'tls';
        $mail->Port = $emailConfig['port'] ?? 587;

        $mail->setFrom($emailConfig['from_email'] ?? 'no-reply@maatafishfarm.local', $emailConfig['from_name'] ?? 'Maata Fish Farm');
        $mail->addAddress($email, $full_name ?: 'User');

        $mail->isHTML(true);
        $mail->Subject = 'Temporary password for your Maata Fish Farm account';
        $mail->Body = "Hello " . htmlspecialchars($full_name) . ",<br><br>Your temporary password is: <strong>{" . htmlspecialchars($temp_password) . "}</strong><br><br>Use this password to sign in, then change your password from your account settings.<br><br>Regards,<br>Maata Fish Farm";
        $mail->AltBody = "Hello {$full_name}\n\nYour temporary password is: {$temp_password}\n\nUse this password to sign in, then change your password from your account settings.";

        $mail->send();
        $sent = true;
        $log = "[{$time}] TEMP email sent to {$email} | user_id: {$user_id}\n";
    } catch (Exception $e) {
        $sent = false;
        $log = "[{$time}] TEMP email FAILED to {$email} | user_id: {$user_id} | error: " . $mail->ErrorInfo . "\n";
    }
} else {
    $log = "[{$time}] Failed to update password for user_id {$user_id}\n";
}

// Ensure logs directory exists and append log
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
@file_put_contents($logDir . '/email.log', $log, FILE_APPEND | LOCK_EX);

header('Location: ../forgot_password.php?message=' . urlencode('If the email exists, a temporary password has been sent.'));
exit;
