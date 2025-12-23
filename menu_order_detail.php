<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<div class="layout-content">
    <div class="container-fluid flex-grow-1 container-p-y">
        <?php
        require __DIR__ . '/config/db.php';
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo '<div class="alert alert-danger">Invalid order ID</div>'; include 'partials/footer.php'; exit;
        }

        $stmt = $conn->prepare('SELECT mo.*, s.first_name AS admin_first, s.last_name AS admin_last FROM menu_orders mo LEFT JOIN staff s ON mo.admin_id = s.user_id WHERE mo.id = ? LIMIT 1');
        $order = null;
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $order = $res->fetch_assoc();
            $stmt->close();
        }

        if (!$order) {
            echo '<div class="alert alert-warning">Order not found.</div>'; include 'partials/footer.php'; exit;
        }
        ?>

        <h4 class="font-weight-bold py-3 mb-0">Menu Order Details - <?php echo htmlspecialchars($order['order_number']); ?></h4>

        <div class="card mt-3">
            <div class="card-body">
                <dl>
                   <dt>Total</dt>
                    <dd>₱<?php echo number_format($order['total_amount'],2); ?></dd>
                    <dt>Status</dt>
                    <dd style="display: flex; gap: 10px; align-items: center;">
                        <span id="currentStatus"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                        <select id="statusSelect" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="">Change Status...</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button type="button" id="updateStatusBtn" class="btn btn-sm btn-primary">Update</button>
                    </dd>
                    <dt>Notes</dt>
                    <dd><?php echo nl2br(htmlspecialchars($order['notes'])); ?></dd>
                </dl>

                <h6 class="mt-4">Items</h6>
                <table class="table table-sm">
                    <thead>
                        <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $itStmt = $conn->prepare('SELECT moi.*, IF(moi.item_type = "fish", (SELECT name FROM fish_species WHERE fish_id = moi.item_id), (SELECT name FROM products WHERE id = moi.item_id)) AS item_name FROM menu_order_items moi WHERE moi.menu_order_id = ?');
                        if ($itStmt) {
                            $itStmt->bind_param('i', $id);
                            $itStmt->execute();
                            $itRes = $itStmt->get_result();
                            while ($ir = $itRes->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($ir['item_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($ir['quantity']) . '</td>';
                                echo '<td>₱' . number_format($ir['unit_price'],2) . '</td>';
                                echo '<td>₱' . number_format($ir['subtotal'],2) . '</td>';
                                echo '</tr>';
                            }
                            $itStmt->close();
                        } else {
                            echo '<tr><td colspan="5">No items found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>

                <a href="menu_orders_view.php" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<script>
document.getElementById('updateStatusBtn').addEventListener('click', function() {
    var newStatus = document.getElementById('statusSelect').value;
    if (!newStatus) {
        alert('Please select a status');
        return;
    }
    
    // Simple fetch to update status
    fetch('handlers/menu_order_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'menu_order_id=<?php echo (int)$id; ?>&status=' + encodeURIComponent(newStatus),
        credentials: 'include'
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.ok) {
            alert('Status updated to: ' + newStatus);
            location.reload();
        } else {
            alert('Error: ' + (data.msg || 'Update failed'));
        }
    })
    .catch(function(e) {
        alert('Error: ' + e.message);
    });
});
</script>
