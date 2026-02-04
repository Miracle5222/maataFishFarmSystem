<?php
// Verification handler for customer government IDs
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include '../auth_admin.php';
require '../config/db.php';
require '../config/email.php';
require __DIR__ . '/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../customer_id_verification.php?error=Invalid request');
    exit;
}

$customer_id = intval($_POST['customer_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$customer_id || !in_array($action, ['approve', 'reject'])) {
    header('Location: ../customer_id_verification.php?error=Invalid parameters');
    exit;
}

// Get customer information
$stmt = $conn->prepare("SELECT email, CONCAT(first_name, ' ', last_name) as name FROM customers WHERE id = ?");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: ../customer_id_verification.php?error=Customer not found');
    exit;
}

$customer = $result->fetch_assoc();
$customer_email = $customer['email'];
$customer_name = $customer['name'];
$stmt->close();

if ($action == 'approve') {
    // Mark as verified
    $stmt = $conn->prepare("UPDATE customers SET government_id_verified = 1, id_verification_date = NOW() WHERE id = ?");
    $stmt->bind_param('i', $customer_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Send verification approval email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtp_port;
            
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($customer_email);
            $mail->isHTML(true);
            $mail->Subject = 'Government ID Verification Approved';
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <p>Hello " . htmlspecialchars($customer_name) . ",</p>
                <p>Your government ID has been verified successfully.</p>
                <p>Your account is now fully activated. You can enjoy all our services.</p>
                <p style='margin-top: 20px;'>Best regards,<br>Maata Fish Farm System</p>
            </body>
            </html>";
            
            $mail->Body = $body;
            @$mail->send();
        } catch (Exception $e) {
            // Log error but don't fail the verification
            error_log("Email send error: " . $e->getMessage());
        }
        
        // Log the activity
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['role'] ?? 'staff';
        
        logActivity(
            $conn,
            $user_id,
            $user_type,
            'APPROVE',
            'customer_id_verification',
            $customer_id,
            $customer_name,
            "Approved government ID for: $customer_name"
        );
        
        header('Location: ../customer_id_verification.php?success=' . urlencode($customer_name . "'s ID has been verified"));
    } else {
        header('Location: ../customer_id_verification.php?error=Failed to verify customer');
    }
} elseif ($action == 'reject') {
    // Mark as rejected (set verified to 2 to distinguish from pending)
    $stmt = $conn->prepare("UPDATE customers SET government_id_verified = 2 WHERE id = ?");
    $stmt->bind_param('i', $customer_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Send rejection email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtp_port;
            
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($customer_email);
            $mail->isHTML(true);
            $mail->Subject = 'Government ID Verification Rejected';
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <p>Hello " . htmlspecialchars($customer_name) . ",</p>
                <p>Unfortunately, your submitted government ID could not be verified.</p>
                <p>This may be due to:</p>
                <ul>
                    <li>Image quality or clarity issues</li>
                    <li>Information mismatch</li>
                    <li>Expired or invalid ID</li>
                </ul>
                <p>Please log in to your account and re-submit a clear photo of your government-issued ID.</p>
                <p style='margin-top: 20px;'>Best regards,<br>Maata Fish Farm System</p>
            </body>
            </html>";
            
            $mail->Body = $body;
            @$mail->send();
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
        }
        
        // Log the activity
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['role'] ?? 'staff';
        
        logActivity(
            $conn,
            $user_id,
            $user_type,
            'REJECT',
            'customer_id_verification',
            $customer_id,
            $customer_name,
            "Rejected government ID for: $customer_name"
        );
        
        header('Location: ../customer_id_verification.php?success=' . urlencode($customer_name . "'s ID has been rejected"));
    } else {
        header('Location: ../customer_id_verification.php?error=Failed to reject customer ID');
    }
}

exit;
?>
