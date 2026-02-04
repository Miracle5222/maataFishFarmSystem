<?php
session_start();
require __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$customer_id = intval($_SESSION['client_id']);
$redirect_from = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : null;

// Get customer data
$stmt = $conn->prepare("SELECT government_id_verified, government_id_image, id_verification_date, created_at FROM customers WHERE id = ?");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: login.php');
    exit;
}

$customer = $result->fetch_assoc();
$stmt->close();

// Handle re-upload
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reupload') {
    if (!isset($_FILES['government_id_image'])) {
        $message = 'No file selected';
        $message_type = 'error';
    } else {
        $file = $_FILES['government_id_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = 'File upload error occurred';
            $message_type = 'error';
        } elseif (!in_array($file['type'], $allowed_types)) {
            $message = 'Invalid file type. Please upload a JPG, PNG, or GIF image';
            $message_type = 'error';
        } elseif ($file['size'] > $max_size) {
            $message = 'File is too large. Maximum size is 5MB';
            $message_type = 'error';
        } else {
            // Create upload directory if it doesn't exist
            if (!is_dir(__DIR__ . '/../assets/img/customer_ids')) {
                mkdir(__DIR__ . '/../assets/img/customer_ids', 0755, true);
            }
            
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'govid_' . strtolower(str_replace(' ', '_', $_SESSION['client_name'])) . '_' . uniqid() . '.' . $ext;
            $filepath = __DIR__ . '/../assets/img/customer_ids/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database
                $stmt = $conn->prepare("UPDATE customers SET government_id_image = ?, government_id_verified = 0 WHERE id = ?");
                $stmt->bind_param('si', $filename, $customer_id);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    $message = 'Government ID uploaded successfully and is pending verification';
                    $message_type = 'success';
                    // Refresh customer data
                    $stmt = $conn->prepare("SELECT government_id_verified, government_id_image FROM customers WHERE id = ?");
                    $stmt->bind_param('i', $customer_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $customer = $result->fetch_assoc();
                    $stmt->close();
                } else {
                    @unlink($filepath);
                    $message = 'Failed to save file information';
                    $message_type = 'error';
                }
            } else {
                $message = 'Failed to upload file';
                $message_type = 'error';
            }
        }
    }
}

// Determine verification status
$status = 'Not submitted';
$status_badge = 'secondary';
$show_upload = true;

if ($customer['government_id_verified'] == 1) {
    $status = 'Verified';
    $status_badge = 'success';
    $show_upload = false;
} elseif ($customer['government_id_verified'] == 2) {
    $status = 'Rejected - Please Re-submit';
    $status_badge = 'danger';
    $show_upload = true;
} elseif ($customer['government_id_image']) {
    $status = 'Pending Verification';
    $status_badge = 'warning';
    $show_upload = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Verification - Maata Fish Farm</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-card {
            border-left: 4px solid #007bff;
            padding: 20px;
            border-radius: 8px;
            background: #f8f9fa;
            margin-bottom: 25px;
        }
        .status-card.success {
            border-left-color: #28a745;
        }
        .status-card.warning {
            border-left-color: #ffc107;
        }
        .status-card.danger {
            border-left-color: #dc3545;
        }
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        .upload-area:hover {
            border-color: #0056b3;
            background: #e7f0ff;
        }
        .upload-area.dragover {
            border-color: #0056b3;
            background: #e7f0ff;
        }
        .btn-upload {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            text-decoration: none;
        }
        .btn-upload:hover {
            background: #0056b3;
            text-decoration: none;
            color: white;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px; margin: 50px auto; padding: 20px;">
        <h2>ID Verification Status</h2>
        <hr>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo ($message_type == 'success') ? 'success' : ($message_type == 'warning' ? 'warning' : 'error'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($redirect_from): ?>
            <div class="alert" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404;">
                <strong>📋 Verification Required:</strong><br>
                To proceed with <?php echo ($redirect_from == 'booking') ? 'making a reservation' : 'placing an order'; ?>, you need to verify your government ID first.
            </div>
        <?php endif; ?>
        
        <div class="status-card <?php echo strtolower($status_badge); ?>">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;">Current Status</p>
                    <h3 style="margin: 5px 0 0 0;"><?php echo $status; ?></h3>
                </div>
                <div>
                    <span class="badge badge-<?php echo $status_badge; ?>" style="padding: 8px 15px; font-size: 14px;">
                        <?php echo $status; ?>
                    </span>
                </div>
            </div>
            
            <?php if ($customer['id_verification_date']): ?>
                <p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">
                    Verified on: <?php echo date('F d, Y', strtotime($customer['id_verification_date'])); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <?php if ($status_badge == 'danger'): ?>
            <div class="info-box">
                <strong>What went wrong?</strong><br>
                Your submitted ID could not be verified. Please ensure:
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>The image is clear and readable</li>
                    <li>All details match your registration information</li>
                    <li>The ID is not expired</li>
                    <li>The photo is taken straight-on (not at an angle)</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($show_upload): ?>
            <h4 style="margin-top: 30px; margin-bottom: 15px;">Upload Government ID</h4>
            
            <div class="info-box">
                <strong>📋 Required Documents:</strong><br>
                Accept any of the following government-issued IDs:
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Passport</li>
                    <li>National ID Card</li>
                    <li>Driver's License</li>
                    <li>Birth Certificate</li>
                </ul>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="reupload">
                
                <div class="upload-area" id="uploadArea">
                    <i style="font-size: 40px; color: #999;">📄</i>
                    <p style="margin: 10px 0 0 0; color: #666;">
                        Click to upload or drag and drop<br>
                        <small style="color: #999;">JPG, PNG or GIF (Max 5MB)</small>
                    </p>
                    <input type="file" name="government_id_image" id="fileInput" accept="image/*" style="display: none;">
                </div>
                
                <div id="fileName" style="margin-top: 10px; font-size: 14px; color: #666;"></div>
                
                <button type="submit" class="btn-upload" style="width: 100%; margin-top: 20px; border: none; cursor: pointer;">
                    Upload ID
                </button>
            </form>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center;">
            <a href="index.php" style="color: #007bff; text-decoration: none; font-size: 16px; padding: 10px 20px; display: inline-block; border-radius: 4px;">Back to Home</a>
        </div>
    </div>
    
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        
        // Click to upload
        uploadArea.addEventListener('click', () => fileInput.click());
        
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            updateFileName();
        });
        
        // File input change
        fileInput.addEventListener('change', updateFileName);
        
        function updateFileName() {
            if (fileInput.files.length > 0) {
                fileName.textContent = '✓ Selected: ' + fileInput.files[0].name;
            } else {
                fileName.textContent = '';
            }
        }
    </script>
</body>
</html>
