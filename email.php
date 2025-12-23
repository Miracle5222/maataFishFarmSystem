<?php

/**
 * Send email function using PHPMailer
 */

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function sendEmail($config, $recipient_email, $recipient_name, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['secure'];
        $mail->Port = $config['port'];
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($recipient_email, $recipient_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

// Test function - only when called directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $config = require 'config/email.php';
    $result = sendEmail(
        $config,
        'rgb.dempsey@gmail.com',
        'Test User',
        'Simple Test - ' . date('H:i:s'),
        'This is a simple test email from PHP.'
    );
    echo $result ? 'Message has been sent!' : 'Message could not be sent.';
}
?>
