<?php
// Forgot password form
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
include 'partials/head.php';
?>

<div class="layout-content">
    <div class="container d-flex align-items-center justify-content-center" style="min-height:80vh">
        <div class="card col-md-6 col-lg-4 p-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Forgot Password</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($_GET['message'])): ?>
                    <div class="alert alert-success small mb-3 text-dark" role="alert" aria-live="polite">
                        <strong>Success:</strong> <?php echo htmlspecialchars($_GET['message']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_GET['error'])): ?>
                    <div class="alert alert-danger small mb-3 text-dark" role="alert" aria-live="assertive">
                        <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="handlers/reset_password_handler.php">
                    <div class="form-group">
                        <label for="email">Registered Email</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Enter your registered email" required autofocus>
                    </div>
                    <div class="form-group text-right">
                        <button class="btn btn-primary" type="submit">Send Reset Link</button>
                    </div>
                </form>

                <hr>
                <p class="small mb-0">Remember your password? <a href="admin_login.php">Sign in</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const alerts = document.querySelectorAll('.alert');
    if(!alerts.length) return;
    // Auto-hide non-persistent alerts after 5 seconds (fade then remove)
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