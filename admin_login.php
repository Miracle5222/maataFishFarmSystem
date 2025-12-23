<?php
// Admin login page (revised)
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
include 'partials/head.php';
?>

<div class="layout-content">
    <div class="container d-flex align-items-center justify-content-center" style="min-height:80vh">
        <div class="card col-md-5 col-lg-4 p-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Sign in to Admin Panel</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($_GET['error'])): ?>
                    <div class="alert alert-danger small font-weight-bold text-dark mb-3"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_GET['message'])): ?>
                    <div class="alert alert-success small text-primary mb-3"><?php echo htmlspecialchars($_GET['message']); ?></div>
                <?php endif; ?>

                <form method="post" action="handlers/admin_login_handler.php" novalidate>
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username or email" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                    </div>

                    <div class="form-row align-items-center mb-3">
                        <div class="col-auto">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                                <label class="custom-control-label" for="remember">Remember me</label>
                            </div>
                        </div>
                        <div class="col text-right">
                            <a href="forgot_password.php" class="small">Forgot password?</a>
                        </div>
                    </div>

                    <div class="form-group text-right">
                        <button class="btn btn-primary" type="submit">Sign In</button>
                    </div>
                </form>

                <hr>
                <p class="small text-muted mb-0">Need help? Contact the system administrator.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const alerts = document.querySelectorAll('.alert');
    if(!alerts.length) return;
    // Auto-hide non-persistent alerts after 5 seconds
    setTimeout(function(){
        alerts.forEach(function(a){
            try {
                a.style.transition = 'opacity 0.35s ease';
                a.style.opacity = '0';
                setTimeout(function(){
                    if (a.parentNode) a.parentNode.removeChild(a);
                }, 400);
            } catch (e) {
                if (a.parentNode) a.parentNode.removeChild(a);
            }
        });
    }, 5000);
});
</script>