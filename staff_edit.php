<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Staff — Edit Staff</h4>
        
        <?php
        require __DIR__ . '/config/db.php';
        
        $staff_id = $_GET['id'] ?? null;
        $staff = null;
        $user = null;
        $error = '';
        $success = '';
        
        if (isset($_GET['error'])) {
            $error = htmlspecialchars($_GET['error']);
        }
        if (isset($_GET['message'])) {
            $success = htmlspecialchars($_GET['message']);
        }
        
        // Fetch staff information
        if ($staff_id && is_numeric($staff_id)) {
            $stmt = $conn->prepare('SELECT s.*, u.username, u.email as user_email, u.role, u.status as user_status FROM staff s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $staff_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0) {
                    $staff = $res->fetch_assoc();
                } else {
                    $error = 'Staff member not found';
                }
                $stmt->close();
            }
        } else {
            $error = 'Invalid staff ID';
        }
        
        if (empty($staff)) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> ' . htmlspecialchars($error ?: 'Staff member not found') . '
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>';
            echo '<a href="staff_list.php" class="btn btn-secondary mt-2">Back to Staff List</a>';
            include 'partials/footer.php';
            exit;
        }
        ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert" id="successAlert">
                <div class="d-flex align-items-center">
                    <i class="feather icon-check-circle text-success mr-3" style="font-size: 24px;"></i>
                    <div>
                        <strong>Success!</strong>
                        <div><?php echo $success; ?></div>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert" id="errorAlert">
                <div class="d-flex align-items-center">
                    <i class="feather icon-alert-circle text-danger mr-3" style="font-size: 24px;"></i>
                    <div>
                        <strong>Error!</strong>
                        <div><?php echo $error; ?></div>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mt-3">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Edit Staff Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="handlers/staff_edit_handler.php">
                            <input type="hidden" name="staff_id" value="<?php echo (int)$staff['id']; ?>">
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($staff['first_name']); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($staff['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($staff['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($staff['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Position <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="position" value="<?php echo htmlspecialchars($staff['position'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($staff['department'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Hire Date</label>
                                    <input type="date" class="form-control" name="hire_date" value="<?php echo htmlspecialchars($staff['hire_date'] ?? ''); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-control" name="role" required>
                                        <option value="staff" <?php echo ($staff['role'] === 'staff' ? 'selected' : ''); ?>>Staff</option>
                                        <option value="manager" <?php echo ($staff['role'] === 'manager' ? 'selected' : ''); ?>>Manager</option>
                                        <option value="admin" <?php echo ($staff['role'] === 'admin' ? 'selected' : ''); ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control" name="status" required>
                                    <option value="active" <?php echo ($staff['status'] === 'active' ? 'selected' : ''); ?>>Active</option>
                                    <option value="inactive" <?php echo ($staff['status'] === 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="feather icon-save"></i> Save Changes
                                </button>
                                <a href="staff_list.php" class="btn btn-secondary">
                                    <i class="feather icon-x"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mt-3">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Staff Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Staff ID:</strong><br>
                                <span class="badge badge-primary"><?php echo (int)$staff['id']; ?></span>
                            </li>
                            <li class="mb-2">
                                <strong>Created:</strong><br>
                                <small><?php echo date('M d, Y H:i', strtotime($staff['created_at'])); ?></small>
                            </li>
                            <li class="mb-2">
                                <strong>Last Updated:</strong><br>
                                <small><?php echo date('M d, Y H:i', strtotime($staff['updated_at'])); ?></small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<style>
    /* Alert Styling for Better Readability */
    .alert {
        border-radius: 6px;
        border: none;
        font-size: 14px;
    }
    
    .alert-success {
        background-color: #f0fdf4 !important;
        color: #166534 !important;
        border-left: 4px solid #22c55e !important;
    }
    
    .alert-success strong,
    .alert-success div,
    .alert-success p {
        color: #166534 !important;
    }
    
    .alert-success .close {
        color: #166534 !important;
        opacity: 0.7;
    }
    
    .alert-success .close:hover {
        opacity: 1;
    }
    
    .alert-danger {
        background-color: #fef2f2 !important;
        color: #7f1d1d !important;
        border-left: 4px solid #ef4444 !important;
    }
    
    .alert-danger strong,
    .alert-danger div,
    .alert-danger p {
        color: #7f1d1d !important;
    }
    
    .alert-danger .close {
        color: #7f1d1d !important;
        opacity: 0.7;
    }
    
    .alert-danger .close:hover {
        opacity: 1;
    }
    
    .alert i {
        color: inherit !important;
    }
    
    .badge {
        color: #fff !important;
        font-weight: 600;
    }
    
    .badge-primary {
        background-color: #2196f3 !important;
        color: #fff !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(successAlert);
            bsAlert.close();
        }, 5000);
    }
});
</script>

<?php include 'partials/footer.php'; ?>
