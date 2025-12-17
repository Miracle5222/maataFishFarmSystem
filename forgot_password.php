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
                    <div class="alert alert-success small mb-3"><?php echo htmlspecialchars($_GET['message']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_GET['error'])): ?>
                    <div class="alert alert-danger small mb-3"><?php echo htmlspecialchars($_GET['error']); ?></div>
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