<?php
// Generate reset token and email link
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

// Find user
$stmt = $conn->prepare('SELECT id, full_name FROM users WHERE email = ? AND status = "active" LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || !$user = $res->fetch_assoc()) {
    // Do not reveal whether email exists; respond with success message
    header('Location: ../forgot_password.php?message=' . urlencode('If the email exists, a reset link has been sent.'));
    exit;
}
$user_id = (int)$user['id'];
$full_name = $user['full_name'];

// Create password_resets table if not exists
$create_sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (token(64)),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($create_sql);

// Generate token
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour

// Insert token
$ins = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
$ins->bind_param('iss', $user_id, $token, $expires_at);
$ins->execute();

// Build reset URL
$host = $_SERVER['HTTP_HOST'];
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$path = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
$reset_url = $scheme . '://' . $host . $path . '/reset_password.php?token=' . $token;

// Load email config
$emailConfigFile = __DIR__ . '/../config/email.php';
$emailConfig = file_exists($emailConfigFile) ? include $emailConfigFile : [];

// Send email via PHPMailer
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
    $mail->Subject = 'Password reset request';
    $mail->Body = "Hello " . htmlspecialchars($full_name) . ",<br><br>We received a request to reset your password. Click the link below to set a new password (link expires in 1 hour):<br><br><a href=\"{$reset_url}\">Reset your password</a><br><br>If you did not request this, you can safely ignore this email.<br><br>Regards,<br>Maata Fish Farm";
    $mail->AltBody = "Hello {$full_name}\n\nOpen the link to reset your password: {$reset_url}\n\nIf you did not request this, ignore this email.";

    $mail->send();
} catch (Exception $e) {
    // Log error to logs/email.log (best effort)
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    @file_put_contents($logDir . '/email.log', '[' . date('Y-m-d H:i:s') . '] Forgot email error: ' . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
}

header('Location: ../forgot_password.php?message=' . urlencode('If the email exists, a reset link has been sent.'));
exit;
