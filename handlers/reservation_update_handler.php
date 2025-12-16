<?php
session_start();
include '../config/db.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$id = intval($_GET['id'] ?? 0);
$status = trim($_GET['status'] ?? '');
$referrer = $_SERVER['HTTP_REFERER'] ?? '../reservations_list.php';

// Validate status
$valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses) || $id <= 0) {
    header("Location: " . $referrer);
    exit;
}

// Update reservation status
$update_query = "UPDATE reservations SET status = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    // Fetch reservation and customer info to notify
    $fetch_q = "SELECT r.reservation_number, r.reservation_date, r.reservation_time, r.special_requests, c.first_name, c.last_name, c.email
                FROM reservations r
                LEFT JOIN customers c ON r.customer_id = c.id
                WHERE r.id = ? LIMIT 1";
    $gstmt = $conn->prepare($fetch_q);
    if ($gstmt) {
        $gstmt->bind_param('i', $id);
        $gstmt->execute();
        $res = $gstmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $to = $row['email'];
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $customer_name = trim($row['first_name'] . ' ' . $row['last_name']);
                $res_no = $row['reservation_number'];
                $res_date = $row['reservation_date'];
                $res_time = $row['reservation_time'];

                $subject = "Reservation {$res_no} - Status updated to " . ucfirst($status);
                $body = "Hello {$customer_name},\n\n";
                $body .= "Your reservation (No: {$res_no}) scheduled on {$res_date} at {$res_time} has been updated to: " . ucfirst($status) . ".\n\n";
                if (!empty($row['special_requests'])) {
                    $body .= "Special requests: " . $row['special_requests'] . "\n\n";
                }
                $body .= "If you have any questions, please reply to this email or contact our support.\n\nRegards,\nMaata Fish Farm";

                // Load SMTP/email config
                $emailConfig = [];
                $emailConfigFile = __DIR__ . '/../config/email.php';
                if (file_exists($emailConfigFile)) {
                    $emailConfig = include $emailConfigFile;
                }

                // Ensure logs directory exists
                $logDir = __DIR__ . '/../logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $logFile = $logDir . '/email.log';
                $time = date('Y-m-d H:i:s');

                $sent = false;
                $log = '';

                // Send email via PHPMailer (matching working email.php pattern)
                try {
                    $mail = new PHPMailer(true);
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = $emailConfig['host'] ?? 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $emailConfig['username'] ?? '';
                    $mail->Password = $emailConfig['password'] ?? '';
                    $mail->SMTPSecure = $emailConfig['secure'] ?? 'tls';
                    $mail->Port = $emailConfig['port'] ?? 587;

                    // Recipients
                    $mail->setFrom($emailConfig['from_email'] ?? 'rgb.dempsey@gmail.com', $emailConfig['from_name'] ?? 'Maata Fish Farm');
                    $mail->addAddress($to, $customer_name);

                    // Content
                    $mail->isHTML(false);
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    $mail->AltBody = $body;

                    $sent = $mail->send();
                    $log = "[{$time}] Email sent to {$to} | reservation: {$res_no} | status: {$status}\n";
                } catch (Exception $e) {
                    $sent = false;
                    $log = "[{$time}] Email FAILED for {$to} | reservation: {$res_no} | status: {$status}\nError: {$mail->ErrorInfo}\n---\n";
                }

                @file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
            }
        }
        $gstmt->close();
    }

    header("Location: " . $referrer . "?success=Reservation status updated");
} else {
    header("Location: " . $referrer . "?error=Failed to update reservation");
}
exit;
