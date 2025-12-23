<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}
$cid = (int) $_SESSION['client_id'];

include 'partials/header.php';
?>
<main style="padding:40px 20px;">
    <div class="container">
        <h1 style="color:#27ae60; margin-bottom:20px; font-size:28px; font-weight:600;">My Orders</h1>

        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <?php
            $stmt = $conn->prepare('SELECT id, order_number, order_date, pickup_date, total_amount, status FROM orders WHERE customer_id = ? ORDER BY id DESC');
            if ($stmt) {
                $stmt->bind_param('i', $cid);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows) {
                    while ($row = $res->fetch_assoc()) {
                        $order_id = (int)$row['id'];
                        echo '<div style="border:1px solid #eee; padding:16px; border-radius:8px; margin-bottom:12px;">';
                        echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">';
                        echo '<div><strong style="font-size:16px;">' . htmlspecialchars($row['order_number']) . '</strong><br><small style="color:#666;">' . htmlspecialchars($row['order_date']) . '</small></div>';
                        echo '<div style="text-align:right;">Status: <strong style="color:#27ae60;">' . htmlspecialchars($row['status']) . '</strong><br>Total: ₱' . number_format($row['total_amount'], 2) . '</div>';
                        echo '</div>';
                        echo '<div style="text-align:right;"><a href="order_detail.php?id=' . (int)$order_id . '" class="btn btn-primary" style="padding:8px 16px; text-decoration:none; border-radius:4px; background:#27ae60; color:white;">View Details</a></div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div style="text-align:center; color:#999; padding:24px 20px;">You have no orders yet.</div>';
                }
                $stmt->close();
            } else {
                echo '<div class="alert alert-danger">Unable to load orders.</div>';
            }
            ?>
        </div>
    </div>
</main>

<?php include 'partials/footer.php'; ?>
