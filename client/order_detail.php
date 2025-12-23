<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$cid = (int) $_SESSION['client_id'];
$order_id = (int) ($_GET['id'] ?? 0);

if (!$order_id) {
    header('Location: orders.php?error=Invalid order');
    exit;
}

// Fetch order details
$stmt = $conn->prepare('SELECT id, order_number, order_date, pickup_date, total_amount, status FROM orders WHERE id = ? AND customer_id = ? LIMIT 1');
$stmt->bind_param('ii', $order_id, $cid);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$order) {
    header('Location: orders.php?error=Order not found');
    exit;
}

include 'partials/header.php';
?>
<main style="padding:40px 20px;">
    <div class="container" style="max-width:900px;">
        <a href="orders.php" style="color:#27ae60; text-decoration:none; margin-bottom:20px; display:inline-block;">← Back to Orders</a>

        <h1 style="color:#27ae60; margin-bottom:20px; font-size:28px; font-weight:600;">Order Details</h1>

        <!-- Order Header -->
        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Order Number</p>
                    <p style="color:#333; font-size:18px; font-weight:600; margin:0;"><?php echo htmlspecialchars($order['order_number']); ?></p>
                </div>
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Status</p>
                    <p style="color:#27ae60; font-size:18px; font-weight:600; margin:0;"><?php echo htmlspecialchars($order['status']); ?></p>
                </div>
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Order Date</p>
                    <p style="color:#333; font-size:16px; margin:0;"><?php echo htmlspecialchars($order['order_date']); ?></p>
                </div>
                <div>
                    <p style="color:#666; font-size:14px; margin:0 0 4px 0;">Pickup Date</p>
                    <p style="color:#333; font-size:16px; margin:0;"><?php echo $order['pickup_date'] ? htmlspecialchars($order['pickup_date']) : 'Not specified'; ?></p>
                </div>
            </div>

        </div>

        <!-- Order Items -->
        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px;">
            <h2 style="color:#233; margin:0 0 20px 0; font-size:20px;">Items Ordered</h2>

            <?php
            // Check what's in the orders table
            $orders_stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND customer_id = ?');
            $orders_stmt->bind_param('ii', $order_id, $cid);
            $orders_stmt->execute();
            $orders_res = $orders_stmt->get_result();
            $orders_data = $orders_res->fetch_assoc();
            $orders_stmt->close();
            
            echo '<!-- DEBUG: Orders table data for order_id=' . $order_id . ': ' . json_encode($orders_data) . ' -->';
            error_log('[order_detail] Orders table: ' . json_encode($orders_data));
            
            // Check order_items
            $check_stmt = $conn->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $check_stmt->bind_param('i', $order_id);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
            $order_items_rows = [];
            while ($row = $check_res->fetch_assoc()) {
                $order_items_rows[] = $row;
            }
            $check_stmt->close();
            
            echo '<!-- DEBUG: order_items rows=' . count($order_items_rows) . ': ' . json_encode($order_items_rows) . ' -->';
            error_log('[order_detail] order_items: ' . json_encode($order_items_rows));
            
            if (count($order_items_rows) > 0) {
                foreach ($order_items_rows as $item_count => $it) {
                    $item_count++;
                    $product_id = (int)$it['product_id'];
                    
                    // Try to fetch name and image from fish_species
                    $fish = null;
                    $fs_stmt = $conn->prepare('SELECT name, image FROM fish_species WHERE fish_id = ?');
                    if ($fs_stmt) {
                        $fs_stmt->bind_param('i', $product_id);
                        $fs_stmt->execute();
                        $fs_res = $fs_stmt->get_result();
                        $fish = $fs_res->fetch_assoc();
                        $fs_stmt->close();
                    }
                    
                    // Try to fetch from products
                    $product = null;
                    $p_stmt = $conn->prepare('SELECT name, image FROM products WHERE id = ?');
                    if ($p_stmt) {
                        $p_stmt->bind_param('i', $product_id);
                        $p_stmt->execute();
                        $p_res = $p_stmt->get_result();
                        $product = $p_res->fetch_assoc();
                        $p_stmt->close();
                    }
                    
                    $iname = ($fish ? $fish['name'] : null) ?: ($product ? $product['name'] : null) ?: ('Item #' . $product_id);
                    $img = ($fish ? $fish['image'] : null) ?: ($product ? $product['image'] : null);
                    $folder = $fish ? 'fish_species/' : 'products/';
                    $image_path = $img ? ('../assets/img/' . $folder . htmlspecialchars($img)) : '../assets/img/placeholder.png';
                    
                    error_log('[order_detail] Item ' . $item_count . ': product_id=' . $product_id . ', name=' . $iname . ', img=' . ($img ?? 'null'));
                    echo '<!-- Item ' . $item_count . ': product_id=' . $product_id . ', name=' . htmlspecialchars($iname) . ', img=' . ($img ?? 'null') . ', folder=' . $folder . ' -->';
                    
                    echo '<div style="display:flex; gap:16px; padding:16px; border:1px solid #eee; border-radius:6px; margin-bottom:12px;">';
                    
                    // Image
                    echo '<div style="flex-shrink:0;">';
                    echo '<img src="' . $image_path . '" alt="' . htmlspecialchars($iname) . '" style="width:120px; height:120px; object-fit:cover; border-radius:6px; background:#f0f0f0;">';
                    echo '</div>';
                    
                    // Item Details
                    echo '<div style="flex:1;">';
                    echo '<h3 style="color:#233; margin:0 0 8px 0; font-size:16px;">' . htmlspecialchars($iname) . '</h3>';
                    echo '<p style="color:#666; font-size:14px; margin:0 0 8px 0;">Unit Price: ₱' . number_format($it['unit_price'], 2) . '</p>';
                    echo '<p style="color:#666; font-size:14px; margin:0 0 8px 0;">Quantity: ' . (int)$it['quantity'] . '</p>';
                    echo '<p style="color:#27ae60; font-size:16px; font-weight:600; margin:0;">Subtotal: ₱' . number_format($it['subtotal'], 2) . '</p>';
                    echo '</div>';
                    
                    echo '</div>';
                }
            } else {
                echo '<p class="text-muted">No items found for this order.</p>';
            }
            ?>
        </div>

        <!-- Order Summary -->
        <div style="background:#f7f9f7; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; font-size:18px; font-weight:700;">
                <span style="color:#233;">Total Amount:</span>
                <span style="color:#27ae60;">₱<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex; gap:12px; margin-bottom:24px;">
            <a href="orders.php" class="btn btn-secondary" style="padding:12px 24px; text-decoration:none; display:inline-block;">Back to Orders</a>
            <?php if (strtolower($order['status']) === 'pending'): ?>
                <button id="cancelOrderBtn" class="btn btn-danger" style="padding:12px 24px; background:#c0392b; border:none; border-radius:6px; color:white; font-weight:600; cursor:pointer;">Cancel Order</button>
            <?php endif; ?>
        </div>

        <!-- Cancel Confirmation Modal -->
        <div id="cancelModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:white; padding:32px; border-radius:8px; max-width:400px; box-shadow:0 4px 16px rgba(0,0,0,0.2);">
                <h2 style="color:#c0392b; margin:0 0 16px 0;">Cancel Order?</h2>
                <p style="color:#666; margin:0 0 24px 0; line-height:1.6;">Are you sure you want to cancel this order? This action cannot be undone.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button id="cancelOrderConfirm" class="btn btn-danger" style="padding:10px 20px; background:#c0392b; border:none; border-radius:6px; color:white; font-weight:600; cursor:pointer;">Yes, Cancel Order</button>
                    <button id="cancelOrderCancel" class="btn btn-secondary" style="padding:10px 20px; background:#95a5a6; border:none; border-radius:6px; color:white; font-weight:600; cursor:pointer;">No, Keep Order</button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'partials/footer.php'; ?>

<script>
    var orderStatus = '<?php echo htmlspecialchars($order['status']); ?>';
    var orderId = <?php echo (int)$order['id']; ?>;

    document.getElementById('cancelOrderBtn')?.addEventListener('click', function() {
        document.getElementById('cancelModal').style.display = 'flex';
    });

    document.getElementById('cancelOrderCancel')?.addEventListener('click', function() {
        document.getElementById('cancelModal').style.display = 'none';
    });

    document.getElementById('cancelOrderConfirm')?.addEventListener('click', function() {
        var formData = new FormData();
        formData.append('action', 'cancel_order');
        formData.append('order_id', orderId);

        fetch('../handlers/order_cancel.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Order cancelled successfully');
                setTimeout(function() {
                    window.location.href = 'orders.php';
                }, 500);
            } else {
                alert('Error cancelling order: ' + (data.error || 'Unknown error'));
                document.getElementById('cancelModal').style.display = 'none';
            }
        })
        .catch(function(e) {
            console.error('Cancel error:', e);
            alert('Error cancelling order');
            document.getElementById('cancelModal').style.display = 'none';
        });
    });
</script>
