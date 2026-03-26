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

        $stmt = $conn->prepare('SELECT o.*, c.first_name AS customer_first, c.last_name AS customer_last, c.email AS customer_email FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.id = ? AND o.is_manual = 0 LIMIT 1');
        $order = null;
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $order = $res->fetch_assoc();
            $stmt->close();
        }

        if (!$order) {
            echo '<div class="alert alert-warning">Online menu order not found.</div>'; include 'partials/footer.php'; exit;
        }
        ?>

        <h4 class="font-weight-bold py-3 mb-0">🍽️ Online Menu Order Details - <?php echo htmlspecialchars($order['order_number']); ?></h4>

        <div class="card mt-3">
            <div class="card-body">
                <dl>
                    <dt>Customer Name</dt>
                    <dd><?php echo htmlspecialchars($order['customer_first'] . ' ' . $order['customer_last']); ?></dd>
                    <dt>Customer Email</dt>
                    <dd><?php echo htmlspecialchars($order['customer_email']); ?></dd>
                    <dt>Total Amount</dt>
                    <dd><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></dd>
                    <dt>Status</dt>
                    <dd style="display: flex; gap: 10px; align-items: center;">
                        <span id="currentStatus"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                        <select id="statusSelect" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="">Change Status...</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="paid">Paid</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button type="button" id="updateStatusBtn" class="btn btn-sm btn-primary">Update</button>
                    </dd>
                    <dt>Order Date</dt>
                    <dd><?php echo htmlspecialchars(date('M d, Y g:iA', strtotime($order['order_date']))); ?></dd>
                    <dt>Pickup Date</dt>
                    <dd><?php echo $order['pickup_date'] ? htmlspecialchars(date('M d, Y g:iA', strtotime($order['pickup_date']))) : '-'; ?></dd>
                </dl>

                <h6 class="mt-4 mb-3" style="font-weight: 600;">Order Items</h6>
                <table class="table table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $itStmt = $conn->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? ORDER BY oi.id');
                        $hasItems = false;
                        if ($itStmt) {
                            $itStmt->bind_param('i', $id);
                            $itStmt->execute();
                            $itRes = $itStmt->get_result();
                            while ($ir = $itRes->fetch_assoc()) {
                                $hasItems = true;
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($ir['name']) . '</td>';
                                echo '<td>' . htmlspecialchars($ir['quantity']) . '</td>';
                                echo '<td>₱' . number_format($ir['unit_price'], 2) . '</td>';
                                echo '<td>₱' . number_format($ir['subtotal'], 2) . '</td>';
                                echo '</tr>';
                            }
                            $itStmt->close();
                        }
                        
                        if (!$hasItems) {
                            echo '<tr><td colspan="4" class="text-center text-muted">No items found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>

                <div style="margin-top: 24px;">
                    <a href="online_menu_orders_view.php" class="btn btn-secondary">← Back to Online Menu Orders</a>
                </div>
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
    
    fetch('handlers/online_menu_order_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order_id=<?php echo (int)$id; ?>&status=' + encodeURIComponent(newStatus),
        credentials: 'include'
    })
    .then(function(res) {
        if (!res.ok) throw new Error('Network response was not ok: ' + res.status);
        return res.text();
    })
    .then(function(text) {
        try {
            var data = JSON.parse(text);
            if (data.ok === true) {
                alert('Status updated to: ' + newStatus);
                location.reload();
            } else {
                alert('Error: ' + (data.msg || 'Update failed'));
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            alert('Error: ' + e.message);
        }
    })
    .catch(function(e) {
        console.error('Fetch error:', e);
        alert('Network error: ' + e.message);
    });
});
</script>
