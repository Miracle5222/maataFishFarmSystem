<?php
// client/profile.php
session_start();
require __DIR__ . '/../config/db.php';
if (empty($_SESSION['client_id'])) {
    $next = urlencode('/client/profile.php');
    header('Location: login.php?next=' . $next);
    exit;
}
$cid = (int) $_SESSION['client_id'];
$stmt = $conn->prepare('SELECT id, first_name, last_name, email, phone, address, barangay, municipality, government_id_verified, id_verification_date FROM customers WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $cid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();
if (!$user) {
    header('Location: index.php?error=Profile not found');
    exit;
}
include 'partials/header.php';
?>
<main style="padding:40px 20px;">
    <div class="container">
        <div style="max-width:900px; margin:0 auto; background:white; padding:20px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.06);">
            <h2 style="margin-bottom:12px; color:#233;">My Account</h2>
            <?php if (!empty($_GET['message'])): ?>
                <div style="color:green; margin-bottom:12px"><?php echo htmlspecialchars($_GET['message']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div style="color:#c00; margin-bottom:12px"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <!-- ID Verification Status Card -->
            <div style="background:#f8f9fa; border-left:4px solid #007bff; padding:15px; border-radius:6px; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <p style="margin:0 0 8px 0; color:#666; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">
                            <strong>Government ID Verification Status</strong>
                        </p>
                        <?php 
                            $verified_status = intval($user['government_id_verified'] ?? 0);
                            if ($verified_status === 1) {
                                echo '<p style="margin:0; color:#28a745; font-size:16px; font-weight:600;">✓ Verified</p>';
                                if (!empty($user['id_verification_date'])) {
                                    echo '<small style="color:#666;">Verified on ' . date('F d, Y', strtotime($user['id_verification_date'])) . '</small>';
                                }
                            } elseif ($verified_status === 2) {
                                echo '<p style="margin:0; color:#dc3545; font-size:16px; font-weight:600;">✗ Rejected</p>';
                                echo '<small style="color:#666;">Please re-submit your government ID</small>';
                            } else {
                                echo '<p style="margin:0; color:#ffc107; font-size:16px; font-weight:600;">⏳ Pending Verification</p>';
                                echo '<small style="color:#666;">Admin is reviewing your submission</small>';
                            }
                        ?>
                    </div>
                    <div style="text-align:right;">
                        <a href="id_verification.php" style="display:inline-block; padding:8px 16px; background:#007bff; color:white; text-decoration:none; border-radius:5px; font-size:14px; font-weight:500;">
                            <?php echo $verified_status === 1 ? '🔄 Update ID' : '📋 Upload ID'; ?>
                        </a>
                    </div>
                </div>
            </div>

            <form id="editProfileForm" method="POST" action="../handlers/client_profile_handler.php">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label>First name</label>
                        <input name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required style="padding:10px; border:1px solid #ddd; border-radius:6px; width:100%;">
                    </div>
                    <div>
                        <label>Last name</label>
                        <input name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required style="padding:10px; border:1px solid #ddd; border-radius:6px; width:100%;">
                    </div>
                    <div>
                        <label>Email</label>
                        <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" style="padding:10px; border:1px solid #ddd; border-radius:6px; width:100%;">
                    </div>
                    <div>
                        <label>Phone</label>
                        <input name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" style="padding:10px; border:1px solid #ddd; border-radius:6px; width:100%;">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label>Address</label>
                        <textarea name="address" class="form-control" style="padding:10px; border:1px solid #ddd; border-radius:6px; width:100%;" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                    <div>
                        <label>Barangay</label>
                        <input name="barangay" class="form-control" value="<?php echo htmlspecialchars($user['barangay']); ?>" style="padding:10px; border:1px solid #ddd; border-radius:6px; width:100%;">
                    </div>
                    <div>
                        <label>Municipality</label>
                        <input name="municipality" class="form-control" value="<?php echo htmlspecialchars($user['municipality']); ?>" style="padding:10px; border:1px solid #ddd; border-radius:6px; width:100%;">
                    </div>
                </div>
                <div style="margin-top:12px; text-align:right;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'partials/footer.php'; ?>

