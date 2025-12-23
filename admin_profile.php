<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Admin Profile</h4>
        
        <?php
        require __DIR__ . '/config/db.php';
        
        // Get logged in user ID from session
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            header('Location: admin_login.php');
            exit;
        }
        
        // Initialize user array with defaults
        $user = array(
            'id' => $user_id,
            'username' => '',
            'email' => '',
            'full_name' => '',
            'role' => '',
            'status' => '',
            'created_at' => date('Y-m-d')
        );
        
        // Fetch from database
        $u_stmt = $conn->prepare('SELECT id, username, email, full_name, role, status, created_at FROM users WHERE id = ? LIMIT 1');
        if ($u_stmt) {
            $u_stmt->bind_param('i', $user_id);
            if ($u_stmt->execute()) {
                if (method_exists($u_stmt, 'get_result')) {
                    $u_res = $u_stmt->get_result();
                    if ($u_res && $u_res->num_rows > 0) {
                        $db_user = $u_res->fetch_assoc();
                        $user = array_merge($user, array_filter($db_user, function($v) { return !is_null($v); }));
                    }
                } else {
                    $u_stmt->store_result();
                    if ($u_stmt->num_rows > 0) {
                        $u_stmt->bind_result($id, $username, $email, $full_name, $role, $status, $created_at);
                        if ($u_stmt->fetch()) {
                            $db_user = [
                                'id' => $id,
                                'username' => $username,
                                'email' => $email,
                                'full_name' => $full_name,
                                'role' => $role,
                                'status' => $status,
                                'created_at' => $created_at
                            ];
                            $user = array_merge($user, array_filter($db_user, function($v) { return !is_null($v); }));
                        }
                    }
                }
            }
            $u_stmt->close();
        }
        
        // Check for success messages
        $success = '';
        if (isset($_GET['message'])) {
            $success = htmlspecialchars($_GET['message']);
        }
        ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($user['role'] ?? '')); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <div>
                                <span class="badge badge-success"><?php echo htmlspecialchars(ucfirst($user['status'] ?? '')); ?></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Member Since</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['created_at'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Quick Actions</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="javascript:" class="list-group-item list-group-item-action" onclick="openEditModal(event);">
                            <i class="feather icon-edit text-primary"></i> &nbsp; Edit Profile
                        </a>
                        <a href="javascript:" class="list-group-item list-group-item-action" onclick="openChangePasswordModal(event);">
                            <i class="feather icon-lock text-warning"></i> &nbsp; Change Password
                        </a>
                        <a href="javascript:" class="list-group-item list-group-item-action" onclick="openSettingsModal(event);">
                            <i class="feather icon-settings text-info"></i> &nbsp; Settings
                        </a>
                        <a href="handlers/logout.php" class="list-group-item list-group-item-action" onclick="return confirm('Are you sure you want to sign out?');">
                            <i class="feather icon-log-out text-danger"></i> &nbsp; Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->
    
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileLabel">Edit Profile</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editProfileForm" method="POST" action="handlers/user_profile_handler.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="action" value="edit_profile">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordLabel">Change Password</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="changePasswordForm" method="POST" action="handlers/change_password_handler.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" id="newPassword" required>
                            <small class="form-text text-muted">Password must be at least 8 characters long</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="settingsLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="settingsLabel">Account Settings</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="settingsForm" method="POST" action="handlers/account_settings_handler.php">
                    <div class="modal-body">
                        <h6 class="mb-3">Notification Preferences</h6>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="emailNotifications" name="email_notifications" checked>
                            <label class="custom-control-label" for="emailNotifications">Email Notifications</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="orderUpdates" name="order_updates" checked>
                            <label class="custom-control-label" for="orderUpdates">Order Updates</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="systemAlerts" name="system_alerts" checked>
                            <label class="custom-control-label" for="systemAlerts">System Alerts</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- [ Layout content ] End -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function openEditModal(e) {
        e.preventDefault();
        $('#editProfileModal').modal('show');
    }
    
    function openChangePasswordModal(e) {
        e.preventDefault();
        $('#changePasswordModal').modal('show');
    }
    
    function openSettingsModal(e) {
        e.preventDefault();
        $('#settingsModal').modal('show');
    }
    
    // Validate password confirmation
    document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long');
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match');
            return false;
        }
    });
</script>

<?php include 'partials/footer.php'; ?>
