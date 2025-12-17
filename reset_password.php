<?php
// Reset password form
session_start();
include 'partials/head.php';

$token = trim($_GET['token'] ?? '');
$valid = false;
$expires = '';
if ($token !== '') {
    include 'config/db.php';
    $stmt = $conn->prepare('SELECT pr.id, pr.expires_at, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $expires = $row['expires_at'];
            if (strtotime($expires) > time()) {
                $valid = true;
            }
        }
        $stmt->close();
    }
}
?>

<div class="layout-content">
    <div class="container d-flex align-items-center justify-content-center" style="min-height:80vh">
        <div class="card col-md-6 col-lg-5 p-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Reset Password</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($_GET['error'])): ?>
                    <div class="alert alert-danger small mb-3"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_GET['message'])): ?>
                    <div class="alert alert-success small mb-3"><?php echo htmlspecialchars($_GET['message']); ?></div>
                <?php endif; ?>

                <?php if (!$token): ?>
                    <div class="alert alert-warning">Invalid reset link.</div>
                <?php elseif (!$valid): ?>
                    <div class="alert alert-warning">This reset link has expired or is invalid.</div>
                <?php else: ?>
                    <form method="post" action="handlers/reset_password_handler.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter a new password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required>
                        </div>
                        <div class="form-group text-right">
                            <button class="btn btn-primary" type="submit">Reset Password</button>
                        </div>
                    </form>
                <?php endif; ?>

                <hr>
                <p class="small mb-0"><a href="admin_login.php">Back to login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>