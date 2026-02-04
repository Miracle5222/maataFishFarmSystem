<?php 
session_start();
include 'partials/header.php'; 
?>

<main style="display:flex; align-items:center; justify-content:center; min-height:60vh; padding:24px;">
    <div style="width:100%; max-width:520px;">
        <div style="background:white; padding:20px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.06);">
            <h3 style="margin-bottom:10px; color:#233;">Create Account</h3>
            <?php if (!empty($_SESSION['reg_error'])): ?>
                <div style="color:#c00;margin-bottom:12px"><?php echo htmlspecialchars($_SESSION['reg_error']); ?></div>
                <?php unset($_SESSION['reg_error']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['reg_success'])): ?>
                <div style="color:#090; margin-bottom:12px; padding:12px; background:#e8f5e9; border-radius:6px; border-left:4px solid #4caf50;">
                    <strong>✓ <?php echo htmlspecialchars($_SESSION['reg_success']); ?></strong>
                    <p style="margin:8px 0 0 0; font-size:14px;">Redirecting to login page in 3 seconds...</p>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
                </script>
                <?php unset($_SESSION['reg_success']); ?>
            <?php else: ?>
            <form method="post" action="../handlers/client_register.php" enctype="multipart/form-data">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <input type="text" name="first_name" placeholder="First name" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                    <input type="text" name="last_name" placeholder="Last name" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>
                <div style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
                    <input type="email" name="email" placeholder="Email" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                    <input type="text" name="phone" placeholder="Phone" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                    <input type="text" name="address" placeholder="Address (optional)" style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                    
                    <!-- Government ID Upload -->
                    <div style="margin:12px 0; padding:12px; background:#f0f7ff; border-radius:6px; border:1px solid #cce5ff;">
                        <label style="display:block; font-weight:600; margin-bottom:10px; color:#233;">Government Issued ID *</label>
                        <p style="font-size:13px; color:#666; margin-bottom:10px;">Upload a photo of your valid government ID (Passport, Driver's License, National ID, etc.)</p>
                        <input type="file" name="government_id_image" accept="image/*" required style="padding:10px; border:1px solid #ddd; border-radius:6px; display:block; width:100%; margin-bottom:8px;">
                        <small style="color:#666;">Supported formats: JPG, PNG, GIF (Max 5MB)</small>
                    </div>

                    <div style="margin:10px 0; padding:12px; background:#f9f9f9; border-radius:6px;">
                        <label style="display:block; font-weight:600; margin-bottom:10px; color:#233;">Account Type *</label>
                        <div style="display:flex; gap:15px;">
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                                <input type="radio" name="customer_type" value="online_customer" checked required style="cursor:pointer;">
                                <span>Online Customer (Order Fish)</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                                <input type="radio" name="customer_type" value="diner" required style="cursor:pointer;">
                                <span>Farm Diner (Restaurant)</span>
                            </label>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <input type="password" name="password" placeholder="Password" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <input type="password" name="password_confirm" placeholder="Confirm password" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                    </div>
                    
                    <div style="padding:12px; background:#fff3cd; border-radius:6px; border-left:4px solid #ffc107; font-size:13px; color:#856404;">
                        <strong>ⓘ ID Verification:</strong> Your government ID will be verified by our admin team within 24 hours. Your account will remain active while verification is in progress.
                    </div>
                    
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <button class="btn btn-primary" style="padding:10px; border-radius:6px;">Register</button>
                        <a href="login.php" class="btn btn-secondary" style="padding:10px; border-radius:6px;">Have account? Login</a>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'partials/footer.php'; ?>