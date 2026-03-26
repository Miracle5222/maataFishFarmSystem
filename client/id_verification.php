<?php
// Start output buffering to handle any header issues
ob_start();
session_start();

// Error logging function
function logError($message) {
    $log_file = __DIR__ . '/../logs/id_verification_errors.log';
    @mkdir(dirname($log_file), 0777, true);
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $log_file);
}

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logError("PHP Error: $errstr in $errfile:$errline");
});

// Safety check - redirect to index if any errors occur
try {
    if (!file_exists(__DIR__ . '/../config/db.php')) {
        throw new Exception('Database config not found');
    }
    
    require __DIR__ . '/../config/db.php';
    
    if (!isset($conn)) {
        throw new Exception('Database connection not initialized');
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['client_id'])) {
        ob_end_clean();
        header('Location: login.php');
        exit;
    }

    $customer_id = intval($_SESSION['client_id']);
    $redirect_from = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : null;

    // Get customer data
    $query = "SELECT government_id_verified, government_id_image, id_verification_date, created_at FROM customers WHERE id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $customer_id);
    if (!$stmt->execute()) {
        throw new Exception('Database execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $stmt->close();
        ob_end_clean();
        header('Location: login.php');
        exit;
    }

    $customer = $result->fetch_assoc();
    $stmt->close();
    
    ob_end_clean();
    
} catch (Exception $e) {
    logError('Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Generate or use existing CSRF token to prevent form resubmission
if (!isset($_SESSION['id_verification_token'])) {
    $_SESSION['id_verification_token'] = bin2hex(random_bytes(32));
}
$form_token = $_SESSION['id_verification_token'];

// Handle re-upload
$message = '';
$message_type = '';
$old_image = null;

// Check if this is a redirect after successful submission
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = '✅ Government ID updated successfully! Your new ID is now pending verification. The admin will review it shortly.';
    $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reupload') {
    try {
        // Check CSRF token to prevent resubmission
        $submitted_token = $_POST['verification_token'] ?? '';
        
        if (empty($submitted_token) || $submitted_token !== $_SESSION['id_verification_token']) {
            // Token mismatch - this is a refresh attempt or invalid submission
            // Don't process the file, just redirect to prevent browser resubmission warning
            if (!empty($submitted_token)) {
                // This means they're trying to resubmit after the token was changed (refresh case)
                ob_end_clean();
                header('Location: id_verification.php?success=1');
                exit;
            }
            $message = 'Invalid request. Please try again.';
            $message_type = 'error';
        } elseif (!isset($_FILES['government_id_image'])) {
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
                
                // Store old image path for potential cleanup
                $old_image = $customer['government_id_image'];
                
                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $client_name = isset($_SESSION['client_name']) ? strtolower(str_replace(' ', '_', $_SESSION['client_name'])) : 'customer_' . $customer_id;
                $filename = 'govid_' . $client_name . '_' . uniqid() . '.' . $ext;
                $filepath = __DIR__ . '/../assets/img/customer_ids/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Update database with new image and mark as resubmitted
                    // Use simple update without updated_at column
                    $stmt = $conn->prepare("UPDATE customers SET government_id_image = ?, government_id_verified = 0 WHERE id = ?");
                    
                    if (!$stmt) {
                        throw new Exception('Database prepare error: ' . $conn->error);
                    }
                    
                    $stmt->bind_param('si', $filename, $customer_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Database execute error: ' . $stmt->error);
                    }
                    
                    $stmt->close();
                    
                    // Log the resubmission activity (optional - don't break main flow if it fails)
                    $activity_desc = 'Resubmitted government ID for verification (updated from: ' . ($old_image ? basename($old_image) : 'none') . ')';
                    $user_type = 'customer';
                    $activity_type = 'id_resubmission';
                    
                    $log_stmt = @$conn->prepare("INSERT INTO activity_logs (user_id, user_type, activity_type, description) VALUES (?, ?, ?, ?)");
                    if ($log_stmt) {
                        @$log_stmt->bind_param('isss', $customer_id, $user_type, $activity_type, $activity_desc);
                        @$log_stmt->execute();
                        @$log_stmt->close();
                    }
                    
                    // Delete old image file if exists
                    if ($old_image) {
                        $old_filepath = __DIR__ . '/../assets/img/customer_ids/' . $old_image;
                        if (file_exists($old_filepath)) {
                            @unlink($old_filepath);
                        }
                    }
                    
                    $message = '✅ Government ID updated successfully! Your new ID is now pending verification. The admin will review it shortly.';
                    $message_type = 'success';
                    
                    // Refresh customer data
                    $refresh_stmt = $conn->prepare("SELECT government_id_verified, government_id_image, id_verification_date, created_at FROM customers WHERE id = ?");
                    $refresh_stmt->bind_param('i', $customer_id);
                    $refresh_stmt->execute();
                    $refresh_result = $refresh_stmt->get_result();
                    $customer = $refresh_result->fetch_assoc();
                    $refresh_stmt->close();
                    
                    // Regenerate token to prevent resubmission on page refresh
                    $_SESSION['id_verification_token'] = bin2hex(random_bytes(32));
                    
                    // Redirect to prevent form resubmission on page refresh
                    ob_end_clean();
                    header('Location: id_verification.php?success=1');
                    exit;
                } else {
                    throw new Exception('File upload failed: ' . $file['tmp_name']);
                }
            }
        }
    } catch (Exception $e) {
        logError('POST Error: ' . $e->getMessage());
        $message = 'Error: ' . $e->getMessage(); // Show actual error for debugging
        $message_type = 'error';
    }
}

// Determine verification status
$status = 'Not submitted';
$status_badge = 'secondary';
$show_upload = true;
$show_current_image = false;

// Ensure proper type comparison
$verified_status = intval($customer['government_id_verified']);

if ($verified_status === 1) {
    $status = 'Verified ✓';
    $status_badge = 'success';
    $show_upload = false;
    $show_current_image = true;
} elseif ($verified_status === 2) {
    $status = 'Rejected - Please Re-submit';
    $status_badge = 'danger';
    $show_upload = true;
    $show_current_image = true;
} elseif (!empty($customer['government_id_image'])) {
    $status = 'Pending Verification';
    $status_badge = 'warning';
    $show_upload = false;
    $show_current_image = true;
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
            background: #28a745;
            border: 1px solid #218838;
            color: white;
            font-weight: 500;
        }
        .alert-error {
            background: #dc3545;
            border: 1px solid #c82333;
            color: white;
            font-weight: 500;
        }
        .alert-warning {
            background: #ff8c00;
            border: 1px solid #e67e00;
            color: white;
            font-weight: 500;
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
        
        <?php if ($redirect_from && intval($customer['government_id_verified']) !== 1): ?>
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
        
        <!-- Current ID Image Display -->
        <?php if ($show_current_image && $customer['government_id_image']): ?>
            <div style="margin-top: 30px;">
                <h4 style="margin-bottom: 15px;">Your Submitted ID</h4>
                <div style="border-radius: 8px; overflow: hidden; background: #f8f9fa; padding: 15px; text-align: center;">
                    <img src="../assets/img/customer_ids/<?php echo htmlspecialchars($customer['government_id_image']); ?>" 
                         alt="Current Government ID" 
                         style="max-width: 100%; max-height: 300px; border-radius: 6px; border: 1px solid #ddd;">
                    <p style="margin: 15px 0 0 0; font-size: 12px; color: #666;">
                        Submitted: <?php echo date('F d, Y', strtotime($customer['created_at'])); ?>
                    </p>
                </div>
                
                <div style="margin-top: 15px; text-align: center;">
                    <button class="btn-upload" onclick="toggleUpdateForm()" style="width: auto; background: #17a2b8;">
                        🔄 Update ID
                    </button>
                </div>
                    
                    <div id="updateForm" style="display: none; margin-top: 20px; padding: 20px; background: #fff3cd; border-radius: 8px; border: 1px solid #ffc107;">
                        <h4 style="margin-top: 0; margin-bottom: 10px;">⚠️ Update ID Image</h4>
                        <p style="margin: 0 0 15px 0; color: #856404;">
                            <strong>Important:</strong> Uploading a new ID will reset your verification status to "Pending" while the admin reviews your submission.
                        </p>
                        
                        <form method="POST" enctype="multipart/form-data" onsubmit="return confirmResubmit();">
                            <input type="hidden" name="action" value="reupload">
                            <input type="hidden" name="verification_token" value="<?php echo htmlspecialchars($form_token); ?>">
                            
                            <div class="upload-area" id="updateUploadArea">
                                <i style="font-size: 40px; color: #999;">📄</i>
                                <p style="margin: 10px 0 0 0; color: #666;">
                                    Click to upload or drag and drop<br>
                                    <small style="color: #999;">JPG, PNG or GIF (Max 5MB)</small>
                                </p>
                                <input type="file" name="government_id_image" id="updateFileInput" accept="image/*" style="display: none;">
                            </div>
                            
                            <div id="updateFileName" style="margin-top: 10px; font-size: 14px; color: #666;"></div>
                            
                            <button type="submit" class="btn-upload" style="width: 100%; margin-top: 20px; border: none; cursor: pointer; background: #ffc107; color: #333;">
                                ✓ Submit New ID
                            </button>
                            <button type="button" class="btn-upload" onclick="toggleUpdateForm()" style="width: 100%; margin-top: 10px; border: none; cursor: pointer; background: #6c757d;">
                                ✗ Cancel
                            </button>
                        </form>
                    </div>
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
                <input type="hidden" name="verification_token" value="<?php echo htmlspecialchars($form_token); ?>">
                
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
        // Confirmation dialog for ID resubmission
        function confirmResubmit() {
            const fileInp = document.getElementById('updateFileInput');
            if (!fileInp || fileInp.files.length === 0) {
                alert('⚠️ Please select a file before submitting');
                return false;
            }
            
            return confirm('⚠️ Are you sure you want to resubmit your ID?\n\nThis will:\n• Reset your verification status to "Pending"\n• Keep your verification status until the admin reviews\n\nClick OK to continue.');
        }
        
        // Toggle update form for verified customers
        function toggleUpdateForm() {
            const updateForm = document.getElementById('updateForm');
            if (updateForm) {
                updateForm.style.display = updateForm.style.display === 'none' ? 'block' : 'none';
                if (updateForm.style.display === 'block') {
                    updateForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }
        
        // Setup main upload area
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        
        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', () => fileInput.click());
            
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
            
            fileInput.addEventListener('change', updateFileName);
        }
        
        function updateFileName() {
            if (fileInput && fileInput.files.length > 0) {
                fileName.textContent = '✓ Selected: ' + fileInput.files[0].name;
            } else {
                fileName.textContent = '';
            }
        }
        
        // Setup update upload area (for verified customers)
        const updateUploadArea = document.getElementById('updateUploadArea');
        const updateFileInput = document.getElementById('updateFileInput');
        const updateFileNameEl = document.getElementById('updateFileName');
        
        if (updateUploadArea && updateFileInput) {
            updateUploadArea.addEventListener('click', () => updateFileInput.click());
            
            updateUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                updateUploadArea.classList.add('dragover');
            });
            
            updateUploadArea.addEventListener('dragleave', () => {
                updateUploadArea.classList.remove('dragover');
            });
            
            updateUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                updateUploadArea.classList.remove('dragover');
                updateFileInput.files = e.dataTransfer.files;
                updateFileNameDisplay();
            });
            
            updateFileInput.addEventListener('change', updateFileNameDisplay);
        }
        
        function updateFileNameDisplay() {
            if (updateFileInput && updateFileInput.files.length > 0) {
                updateFileNameEl.textContent = '✓ Selected: ' + updateFileInput.files[0].name;
            } else if (updateFileNameEl) {
                updateFileNameEl.textContent = '';
            }
        }
        
        // Disable submit buttons after form submission to prevent accidental double clicks
        document.addEventListener('submit', function(e) {
            if (e.target.method === 'POST' && e.target.enctype === 'multipart/form-data') {
                const submitButtons = e.target.querySelectorAll('button[type="submit"]');
                submitButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.style.opacity = '0.6';
                    btn.style.cursor = 'not-allowed';
                });
            }
        });
    </script>
</body>
</html>
