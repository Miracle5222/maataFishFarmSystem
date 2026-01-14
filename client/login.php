<?php 
session_start();
include 'partials/header.php'; 
?>

<main style="display:flex; align-items:center; justify-content:center; min-height:60vh; padding:24px;">
    <div style="width:100%; max-width:420px;">
        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.08);">
            <h3 style="margin-bottom:12px; color:#233;">Sign in</h3>
            <?php if (!empty($_SESSION['login_error'])): ?>
                <div style="color:#c00;margin-bottom:12px"><?php echo htmlspecialchars($_SESSION['login_error']); ?></div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div style="color:#c00;margin-bottom:12px"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <form method="post" action="../handlers/client_login.php">
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <input type="text" name="identifier" placeholder="Email or phone" class="form-control" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                    <input type="password" name="password" placeholder="Password" class="form-control" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                    <input type="hidden" name="next" value="<?php echo htmlspecialchars($_GET['next'] ?? 'index.php'); ?>">
                    <button class="btn btn-primary" style="padding:10px; border-radius:6px;">Login</button>
                </div>
            </form>
            <div style="margin-top:12px; text-align:center; color:#666; font-size:14px;">
                Don't have an account? <a href="register.php">Create one</a>
            </div>
        </div>
    </div>
</main>

<?php include 'partials/footer.php'; ?>