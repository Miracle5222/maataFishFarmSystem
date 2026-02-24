<?php
// handlers/send_customer_feedback.php
// Sends feedback/notes to customer email and stores in database
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    echo json_encode(['ok' => false, 'msg' => 'Not authorized']);
    exit;
}

$ok = false;
$msg = '';

try {
    $customer_id = (int) ($_POST['customer_id'] ?? 0);
    $customer_email = trim($_POST['customer_email'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (!$customer_id || !$customer_email || !$notes) {
        throw new Exception('Missing required fields');
    }
    
    // Validate email
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid customer email address');
    }
    
    // Get customer name from database
    $stmt = $conn->prepare('SELECT first_name, last_name FROM customers WHERE id = ?');
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $customer = $res->fetch_assoc();
    $stmt->close();
    
    if (!$customer) {
        throw new Exception('Customer not found');
    }
    
    $customer_name = $customer['first_name'] . ' ' . $customer['last_name'];
    
    // Create feedback_messages table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS feedback_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        admin_id INT,
        message TEXT,
        email_sent TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Store feedback in database (always succeed even if email fails)
    $feedbackStmt = $conn->prepare('INSERT INTO feedback_messages (customer_id, admin_id, message, email_sent) VALUES (?, ?, ?, 0)');
    if (!$feedbackStmt) {
        throw new Exception('Failed to prepare feedback statement');
    }
    
    $admin_id = $_SESSION['user_id'];
    $feedbackStmt->bind_param('iis', $customer_id, $admin_id, $notes);
    $feedbackResult = $feedbackStmt->execute();
    $feedbackStmt->close();
    
    if (!$feedbackResult) {
        throw new Exception('Failed to store feedback in database');
    }
    
    // Try to send email
    $email_sent = 0;
    $to = $customer_email;
    $subject = 'Feedback from Maata Fish Farm';
    
    $messageBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
            .header { background: #27ae60; color: white; padding: 15px; border-radius: 8px 8px 0 0; text-align: center; }
            .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
            .message-box { background: #f0f0f0; padding: 15px; border-left: 4px solid #27ae60; margin: 20px 0; border-radius: 4px; }
            .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Feedback from Maata Fish Farm</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($customer_name) . "</strong>,</p>
                
                <p>We wanted to reach out to you with some feedback:</p>
                
                <div class='message-box'>
                    " . nl2br(htmlspecialchars($notes)) . "
                </div>
                
                <p>If you have any questions or concerns, please don't hesitate to contact us.</p>
                
                <p>Thank you for your business!</p>
                
                <p>Best regards,<br>
                <strong>Maata Fish Farm Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please reply to this email if you have any questions.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email if possible
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Maata Fish Farm <support@localhost>\r\n";
    
    if (@mail($to, $subject, $messageBody, $headers)) {
        $email_sent = 1;
        // Update the feedback record to mark email as sent
        $updateStmt = $conn->prepare('UPDATE feedback_messages SET email_sent = 1 WHERE customer_id = ? AND admin_id = ? ORDER BY created_at DESC LIMIT 1');
        if ($updateStmt) {
            $updateStmt->bind_param('ii', $customer_id, $admin_id);
            @$updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    // Try to log the activity (optional - don't fail if this errors)
    $activity_type = 'customer_feedback_sent';
    $description = "Sent feedback to customer ID {$customer_id} ({$customer_name})";
    $user_type = 'admin';
    
    $logStmt = $conn->prepare('INSERT INTO activity_logs (user_id, user_type, activity_type, description, created_at) VALUES (?, ?, ?, ?, NOW())');
    if ($logStmt) {
        $logStmt->bind_param('isss', $admin_id, $user_type, $activity_type, $description);
        @$logStmt->execute();
        $logStmt->close();
    }
    
    $ok = true;
    if ($email_sent) {
        $msg = 'Feedback sent to ' . $customer_email . ' and saved!';
    } else {
        $msg = 'Feedback saved successfully! (Email delivery was attempted)';
    }
    
} catch (Exception $e) {
    $msg = $e->getMessage();
}

echo json_encode(['ok' => $ok ? 1 : 0, 'msg' => $msg]);
?>
