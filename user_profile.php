<?php
include 'auth_admin.php';
include 'config/db.php';

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

// Fetch from database with fallback (get_result() may not be available)
$u_stmt = $conn->prepare('SELECT id, username, email, full_name, role, status, created_at FROM users WHERE id = ? LIMIT 1');
if ($u_stmt) {
    $u_stmt->bind_param('i', $user_id);
    if (!$u_stmt->execute()) {
        error_log('Failed to execute user query: ' . $u_stmt->error);
    } else {
        if (method_exists($u_stmt, 'get_result')) {
            $u_res = $u_stmt->get_result();
            if ($u_res && $u_res->num_rows > 0) {
                $db_user = $u_res->fetch_assoc();
                $user = array_merge($user, array_filter($db_user, function($v) { return !is_null($v); }));
            } else {
                error_log('User not found via get_result for id=' . $user_id);
            }
        } else {
            // bind_result fallback
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
            } else {
                error_log('User not found via bind_result for id=' . $user_id);
            }
        }
    }
    $u_stmt->close();
} else {
    error_log('Failed to prepare user query: ' . $conn->error);
}

// Check for success messages
$success = '';
if (isset($_GET['message'])) {
    $success = htmlspecialchars($_GET['message']);
}

include 'partials/head.php';
?>
<style>
/* Styled circular modal close button */
.btn-close-custom{
    width:36px;
    height:36px;
    border-radius:50%;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:1px solid rgba(0,0,0,0.08);
    box-shadow:0 1px 2px rgba(0,0,0,0.05);
    background:#fff;
    color:#333;
}
.btn-close-custom i{font-size:16px;}
.btn-close-custom:hover{background:#f5f5f5;}
.btn-close-custom.btn-close-white{background:transparent;border:1px solid rgba(255,255,255,0.18);color:#fff}
/* Ensure navbar is always clickable and above content */
.layout-navbar {
    position: relative;
    z-index: 1001;
    pointer-events: auto;
}
.layout-navbar a,
.layout-navbar button {
    pointer-events: auto;
}
.dropdown-menu {
    pointer-events: auto;
    z-index: 10000;
}</style>

<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">

    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">User Profile</h4>
        <!-- <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item active">Profile</li>
            </ol>
        </div> -->

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
                        <?php
                        // For demo purposes, showing sample user data
                        // In production, this should fetch from database using session
                        ?>
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
                <!-- <div class="card mb-4">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Profile Picture</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="assets/img/avatars/1.png" alt="Profile Picture" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        <div class="mt-3">
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#changePictureModal">Change Picture</button>
                        </div>
                    </div>
                </div> -->

                <div class="card mb-4">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Quick Actions</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#editProfileModal" class="list-group-item list-group-item-action" onclick="openEditModal(event);">
                            <i class="feather icon-edit text-primary"></i> &nbsp; Edit Profile
                        </a>
                        <a href="#changePasswordModal" class="list-group-item list-group-item-action" onclick="openChangePasswordModal(event);">
                            <i class="feather icon-lock text-warning"></i> &nbsp; Change Password
                        </a>

                        <a href="handlers/logout.php" class="list-group-item list-group-item-action" onclick="return confirm('Are you sure you want to sign out?');">
                            <i class="feather icon-log-out text-danger"></i> &nbsp; Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Layout content ] End -->

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileLabel">Edit Profile</h5>
                <button type="button" class="btn-close btn-close-custom" data-bs-dismiss="modal" aria-label="Close"><i class="feather icon-x"></i></button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
                <button type="button" class="btn-close btn-close-custom" data-bs-dismiss="modal" aria-label="Close"><i class="feather icon-x"></i></button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Account Settings Modal -->
<div class="modal fade" id="accountSettingsModal" tabindex="-1" role="dialog" aria-labelledby="accountSettingsLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountSettingsLabel">Account Settings</h5>
                <button type="button" class="btn-close btn-close-custom" data-bs-dismiss="modal" aria-label="Close"><i class="feather icon-x"></i></button>
            </div>
            <form id="accountSettingsForm" method="POST" action="handlers/account_settings_handler.php">
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

                    <hr>

                    <h6 class="mb-3">Account Preferences</h6>
                    <div class="form-group">
                        <label class="form-label">Language</label>
                        <select class="form-control" name="language">
                            <option value="en">English</option>
                            <option value="tl">Tagalog</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Timezone</label>
                        <select class="form-control" name="timezone">
                            <option value="Asia/Manila">Asia/Manila</option>
                            <option value="UTC">UTC</option>
                        </select>
                    </div>

                    <hr>

                    <h6 class="mb-3">Danger Zone</h6>
                    <div class="alert alert-warning">
                        <small>Account deletion is permanent and cannot be undone.</small>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteAccountModal" data-dismiss="modal">Delete Account</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="deleteAccountLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountLabel">Delete Account</h5>
                <button type="button" class="btn-close btn-close-white btn-close-custom" data-bs-dismiss="modal" aria-label="Close"><i class="feather icon-x"></i></button>
            </div>
            <form method="POST" action="handlers/delete_account_handler.php">
                <div class="modal-body">
                    <p><strong>Warning:</strong> This action cannot be undone. All your data will be permanently deleted.</p>
                    <div class="form-group">
                        <label class="form-label">Enter your password to confirm deletion:</label>
                        <input type="password" class="form-control" name="confirm_delete_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete My Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Modal handler functions - must be defined globally
    function openEditModal(e) {
        e.preventDefault();
        e.stopPropagation();
        try {
            const modalEl = document.getElementById('editProfileModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            } else {
                console.error('Edit profile modal not found');
            }
        } catch (err) {
            console.error('Error opening edit modal:', err);
        }
    }

    function openChangePasswordModal(e) {
        e.preventDefault();
        e.stopPropagation();
        try {
            const modalEl = document.getElementById('changePasswordModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            } else {
                console.error('Change password modal not found');
            }
        } catch (err) {
            console.error('Error opening change password modal:', err);
        }
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