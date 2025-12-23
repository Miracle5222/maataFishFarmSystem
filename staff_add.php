<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Staff — Add Staff</h4>
        
        <?php
        require __DIR__ . '/config/db.php';
        $success = '';
        $error = '';
        
        if (isset($_GET['message'])) {
            $success = htmlspecialchars($_GET['message']);
        }
        if (isset($_GET['error'])) {
            $error = htmlspecialchars($_GET['error']);
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
                        <h5 class="card-header-title">Add New Staff Member</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="handlers/staff_add_handler.php">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="first_name" placeholder="Enter first name" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="last_name" placeholder="Enter last name" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" placeholder="Enter username" required>
                                <small class="form-text text-muted">Must be unique and contain only letters, numbers, and underscores</small>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" placeholder="Enter phone number">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
                                    <small class="form-text text-muted">Minimum 8 characters</small>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Position <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="position" placeholder="e.g., Manager, Assistant" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" name="department" placeholder="e.g., Operations, Sales">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Hire Date</label>
                                    <input type="date" class="form-control" name="hire_date">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-control" name="role" required>
                                        <option value="">-- Select Role --</option>
                                        <option value="staff">Staff</option>
                                        <option value="manager">Manager</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="feather icon-save"></i> Add Staff Member
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
                        <h5 class="card-header-title">Requirements</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Name:</strong> First and last name</li>
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Username:</strong> Unique login identifier</li>
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Email:</strong> Valid email address</li>
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Phone:</strong> Contact number (optional)</li>
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Password:</strong> Minimum 8 characters</li>
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Position:</strong> Job title</li>
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Department:</strong> Work department (optional)</li>
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Hire Date:</strong> Start date (optional)</li>
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Role:</strong> Staff or Manager</li>
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Status:</strong> Active or Inactive</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<script>
// Enhanced notification handling
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss success alert after 5 seconds
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        // Add animation
        successAlert.style.animation = 'slideIn 0.3s ease-out';
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(successAlert);
            bsAlert.close();
        }, 5000);
    }
    
    // Add animation to error alert
    const errorAlert = document.getElementById('errorAlert');
    if (errorAlert) {
        errorAlert.style.animation = 'slideIn 0.3s ease-out';
    }
});

// Password validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const firstName = document.querySelector('input[name="first_name"]').value.trim();
    const lastName = document.querySelector('input[name="last_name"]').value.trim();
    const username = document.querySelector('input[name="username"]').value.trim();
    const email = document.querySelector('input[name="email"]').value.trim();
    const position = document.querySelector('input[name="position"]').value.trim();
    
    // Check all required fields
    if (!firstName || !lastName || !username || !email || !password || !position) {
        e.preventDefault();
        showErrorNotification('All required fields must be filled');
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        showErrorNotification('Password must be at least 8 characters long');
        return false;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        showErrorNotification('Passwords do not match');
        return false;
    }
});

// Function to show inline error notification
function showErrorNotification(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-warning alert-dismissible fade show shadow-sm';
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="feather icon-alert-triangle text-warning mr-3" style="font-size: 24px;"></i>
            <div>
                <strong>Validation Error!</strong>
                <div>${message}</div>
            </div>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    const header = document.querySelector('h4');
    header.parentNode.insertBefore(alertDiv, header.nextSibling);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
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
    .alert-success div {
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
    .alert-danger div {
        color: #7f1d1d !important;
    }
    
    .alert-danger .close {
        color: #7f1d1d !important;
        opacity: 0.7;
    }
    
    .alert-danger .close:hover {
        opacity: 1;
    }
    
    .alert-warning {
        background-color: #fffbeb !important;
        color: #78350f !important;
        border-left: 4px solid #f59e0b !important;
    }
    
    .alert-warning strong,
    .alert-warning div {
        color: #78350f !important;
    }
    
    .alert-warning .close {
        color: #78350f !important;
        opacity: 0.7;
    }
    
    .alert-warning .close:hover {
        opacity: 1;
    }
    
    .alert i {
        color: inherit !important;
    }
`;
document.head.appendChild(style);
</script>

<?php include 'partials/footer.php'; ?>

