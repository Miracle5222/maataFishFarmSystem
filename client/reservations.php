<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}
$cid = (int) $_SESSION['client_id'];

include 'partials/header.php';

// Helper function to format time
function formatTime($time) {
    $parts = explode(':', $time);
    $hour = (int)$parts[0];
    $minute = $parts[1];
    $ampm = $hour >= 12 ? 'PM' : 'AM';
    $hour12 = $hour % 12;
    if ($hour12 == 0) $hour12 = 12;
    return sprintf('%d:%s %s', $hour12, $minute, $ampm);
}
?>
<main style="padding:40px 20px;">
    <div class="container">
        <h1 style="color:#27ae60; margin-bottom:20px; font-size:28px; font-weight:600;">My Reservations</h1>

        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <?php
            $stmt = $conn->prepare('SELECT id, reservation_number, reservation_date, reservation_time, reservation_type, num_guests, status FROM reservations WHERE customer_id = ? ORDER BY reservation_date DESC, reservation_time DESC');
            if ($stmt) {
                $stmt->bind_param('i', $cid);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows) {
                    while ($row = $res->fetch_assoc()) {
                        $reservation_id = (int)$row['id'];
                        $status = htmlspecialchars($row['status']);
                        $can_cancel = in_array($status, ['pending', 'confirmed']);
                        echo '<div style="border:1px solid #eee; padding:16px; border-radius:8px; margin-bottom:12px;">';
                        echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">';
                        echo '<div><strong style="font-size:16px;">' . htmlspecialchars($row['reservation_number']) . '</strong><br><small style="color:#666;">' . htmlspecialchars($row['reservation_date']) . ' at ' . formatTime($row['reservation_time']) . '</small></div>';
                        echo '<div style="text-align:right;">Type: <strong style="color:#27ae60;">' . htmlspecialchars(ucfirst($row['reservation_type'])) . '</strong><br>Guests: ' . (int)$row['num_guests'] . '<br>Status: <strong style="color:#27ae60;">' . $status . '</strong></div>';
                        echo '</div>';
                        echo '<div style="text-align:right;">';
                        if ($can_cancel) {
                            echo '<a href="../handlers/reservation_update_handler.php?id=' . $reservation_id . '&status=cancelled" class="btn btn-danger" style="padding:8px 16px; text-decoration:none; border-radius:4px; background:#dc3545; color:white; margin-right:8px;" onclick="return confirm(\'Are you sure you want to cancel this reservation?\')">Cancel</a>';
                        }
                        echo '<a href="reservation_detail.php?id=' . $reservation_id . '" class="btn btn-primary" style="padding:8px 16px; text-decoration:none; border-radius:4px; background:#27ae60; color:white;">View Details</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div style="text-align:center; color:#999; padding:24px 20px;">You have no reservations yet.</div>';
                }
                $stmt->close();
            } else {
                echo '<div class="alert alert-danger">Unable to load reservations.</div>';
            }
            ?>
        </div>
    </div>
</main>
<?php include 'partials/footer.php'; ?>