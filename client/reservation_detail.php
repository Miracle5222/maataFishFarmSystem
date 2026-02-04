<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$cid = (int) $_SESSION['client_id'];
$reservation_id = (int) ($_GET['id'] ?? 0);

if (!$reservation_id) {
    header('Location: reservations.php?error=Invalid reservation');
    exit;
}

// Fetch reservation details
$stmt = $conn->prepare('SELECT r.*, c.cottage_number FROM reservations r LEFT JOIN cottages c ON r.cottage_id = c.id WHERE r.id = ? AND r.customer_id = ? LIMIT 1');
$stmt->bind_param('ii', $reservation_id, $cid);
$stmt->execute();
$res = $stmt->get_result();
$reservation = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$reservation) {
    header('Location: reservations.php?error=Reservation not found');
    exit;
}

// Helper function to format time
function formatTime($time) {
    $parts = explode(':', $time);
    $hour = (int)$parts[0];
    $minute = $parts[1];
    $ampm = $hour >= 12 ? 'pm' : 'am';
    $hour12 = $hour % 12;
    if ($hour12 == 0) $hour12 = 12;
    return sprintf('%d:%s%s', $hour12, $minute, $ampm);
}

include 'partials/header.php';
?>
<main style="padding:40px 20px;">
    <div class="container" style="max-width:900px;">
        <a href="reservations.php" style="color:#27ae60; text-decoration:none; margin-bottom:20px; display:inline-block;">← Back to Reservations</a>

        <h1 style="color:#27ae60; margin-bottom:20px; font-size:28px; font-weight:600;">Reservation Details</h1>

        <!-- Reservation Header -->
        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Reservation Number</p>
                    <p style="color:#333; font-size:18px; font-weight:600; margin:0;"><?php echo htmlspecialchars($reservation['reservation_number']); ?></p>
                </div>
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Status</p>
                    <p style="color:#27ae60; font-size:18px; font-weight:600; margin:0;"><?php echo htmlspecialchars($reservation['status']); ?></p>
                </div>
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Type</p>
                    <p style="color:#333; font-size:16px; margin:0;"><?php echo htmlspecialchars(ucfirst($reservation['reservation_type'])); ?></p>
                </div>
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Date & Time</p>
                    <p style="color:#333; font-size:16px; margin:0;"><?php echo htmlspecialchars($reservation['reservation_date']); ?> at <?php echo formatTime($reservation['reservation_time']); ?></p>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Number of Guests</p>
                    <p style="color:#333; font-size:16px; margin:0;"><?php echo (int)$reservation['num_guests']; ?></p>
                </div>
                <?php if ($reservation['cottage_number']): ?>
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Cottage</p>
                    <p style="color:#333; font-size:16px; margin:0;"><?php echo htmlspecialchars($reservation['cottage_number']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Information -->
        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px;">
            <h3 style="color:#333; margin-bottom:16px; font-size:20px;">Contact Information</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Email</p>
                    <p style="color:#333; font-size:16px; margin:0;"><?php echo htmlspecialchars($reservation['contact_email']); ?></p>
                </div>
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Phone</p>
                    <p style="color:#333; font-size:16px; margin:0;"><?php echo htmlspecialchars($reservation['contact_phone']); ?></p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <?php
        $status = htmlspecialchars($reservation['status']);
        $can_cancel = in_array($status, ['pending', 'confirmed']);
        if ($can_cancel):
        ?>
        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px;">
            <h3 style="color:#333; margin-bottom:16px; font-size:20px;">Actions</h3>
            <a href="../handlers/reservation_update_handler.php?id=<?php echo $reservation_id; ?>&status=cancelled" class="btn btn-danger" style="padding:10px 20px; text-decoration:none; border-radius:4px; background:#dc3545; color:white;" onclick="return confirm('Are you sure you want to cancel this reservation?')">Cancel Reservation</a>
        </div>
        <?php endif; ?>

        <!-- Special Requests -->
        <?php if (!empty($reservation['special_requests'])): ?>
        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h3 style="color:#333; margin-bottom:16px; font-size:20px;">Special Requests</h3>
            <p style="color:#333; margin:0;"><?php echo nl2br(htmlspecialchars($reservation['special_requests'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'partials/footer.php'; ?>