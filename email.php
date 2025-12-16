<?php

/**
 * SIMPLE TEST - Save as test-email-simple.php
 */

require 'vendor/autoload.php';


use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rgb.dempsey@gmail.com';
    $mail->Password = 'hyig miyw khaa nynx'; // ← REPLACE WITH NEW ONE!
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('rgb.dempsey@gmail.com', 'Test Sender');
    $mail->addAddress('rgb.dempsey@gmail.com'); // Send to yourself

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Simple Test - ' . date('H:i:s');
    $mail->Body    = 'This is a simple test email from PHP.';
    $mail->AltBody = 'This is a simple test email from PHP (plain text).';

    $mail->send();
    echo 'Message has been sent!';
} catch (Exception $e) {
    echo "Message could not be sent. Error: {$mail->ErrorInfo}";
}
