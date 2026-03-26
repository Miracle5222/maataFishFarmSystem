<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<style>
    .dataTables_wrapper { padding: 15px 0; }
    .dataTables_filter { margin-bottom: 15px; }
    .dataTables_info { padding-top: 15px; }
    .dataTables_length { margin-bottom: 15px; }
    @media (max-width: 768px) {
        .dataTables_filter,
        .dataTables_paginate,
        .dataTables_info,
        .dataTables_length {
            text-align: center !important;
            margin-bottom: 10px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.5rem;
            margin: 0 2px;
        }
    }
</style>

<?php
function formatDateTime($datetime) {
    if (empty($datetime)) return '';
    $dt = new DateTime($datetime);
    return $dt->format('M j, Y g:iA');
}
?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="font-weight-bold py-3 mb-1">🍽️ Online Menu Orders</h4>
                <p class="text-muted small mb-0">Menu orders placed by customers through the online platform</p>
            </div>
        </div>
        <div class="card mt-3">
            <div class="table-responsive">
                <table id="onlineMenuOrdersTable" class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Pickup Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require __DIR__ . '/config/db.php';
                        $orders = [];
                        
                        // Get online menu orders - orders that contain product items (menu items)
                        // We get orders that have menu items (products) but no fish items
                        $stmt = $conn->prepare('
                            SELECT DISTINCT o.id, o.order_number, o.customer_id, c.first_name, c.last_name, c.email, 
                                   o.total_amount, o.status, o.order_date, o.pickup_date
                            FROM orders o 
                            LEFT JOIN customers c ON o.customer_id = c.id
                            WHERE o.is_manual = 0 
                            AND EXISTS (
                                SELECT 1 FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = o.id
                            )
                            ORDER BY o.id DESC
                        ');
                        
                        if ($stmt) {
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) {
                                $orders[] = $r;
                            }
                            $stmt->close();
                        }
                        
                        if (empty($orders)) {
                            echo '<tr><td colspan="9" class="text-center text-muted py-4">No online menu orders found.</td></tr>';
                        } else {
                            foreach ($orders as $o):
                                // Get item count for this order
                                $itemStmt = $conn->prepare('SELECT COUNT(*) as cnt FROM order_items WHERE order_id = ?');
                                $itemCount = 0;
                                if ($itemStmt) {
                                    $itemStmt->bind_param('i', $o['id']);
                                    $itemStmt->execute();
                                    $itemRes = $itemStmt->get_result();
                                    if ($itemRes && $itemRow = $itemRes->fetch_assoc()) {
                                        $itemCount = $itemRow['cnt'];
                                    }
                                    $itemStmt->close();
                                }
                                
                                $statusColor = '';
                                $statusBg = '';
                                switch ($o['status']) {
                                    case 'pending': 
                                        $statusColor = '#ff9800'; 
                                        $statusBg = '#fff3e0';
                                        break;
                                    case 'confirmed': 
                                        $statusColor = '#2196f3'; 
                                        $statusBg = '#e3f2fd';
                                        break;
                                    case 'ready': 
                                        $statusColor = '#4caf50'; 
                                        $statusBg = '#e8f5e9';
                                        break;
                                    case 'completed': 
                                        $statusColor = '#8bc34a'; 
                                        $statusBg = '#f1f8e9';
                                        break;
                                    case 'cancelled': 
                                        $statusColor = '#f44336'; 
                                        $statusBg = '#ffebee';
                                        break;
                                    default: 
                                        $statusColor = '#757575'; 
                                        $statusBg = '#eeeeee';
                                }
                                ?>
                                <tr>
                                    <td><?php echo (int)$o['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                                    <td>
                                        <?php 
                                        $customer_name = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                                        if (empty($customer_name)) {
                                            echo '<em style="color:#999;">Customer not found</em>';
                                        } else {
                                            echo htmlspecialchars($customer_name);
                                        }
                                        ?>
                                        <br><small style="color:#999;"><?php echo htmlspecialchars($o['email'] ?? ''); ?></small>
                                    </td>
                                    <td><span class="badge badge-secondary"><?php echo $itemCount . ' item' . ($itemCount != 1 ? 's' : ''); ?></span></td>
                                    <td><strong>₱<?php echo number_format($o['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; border: 1px solid <?php echo $statusColor; ?>; text-transform: capitalize;">
                                            <?php echo htmlspecialchars($o['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($o['order_date']); ?></td>
                                    <td><?php echo formatDateTime($o['pickup_date']); ?></td>
                                    <td class="text-right">
                                        <button class="btn btn-sm btn-icon btn-outline-info view-menu-order" data-id="<?php echo (int)$o['id']; ?>" title="View"><i class="feather icon-eye"></i></button>
                                        <button class="btn btn-sm btn-icon btn-outline-danger delete-menu-order" data-id="<?php echo (int)$o['id']; ?>" data-order="<?php echo htmlspecialchars($o['order_number']); ?>" title="Delete"><i class="feather icon-trash-2"></i></button>
                                    </td>
                                </tr>
                                <?php
                            endforeach;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<?php include 'partials/footer.php'; ?>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function () {
        $('#onlineMenuOrdersTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10,
            responsive: true,
            columnDefs: [
                { orderable: false, targets: 8 },
                { className: 'text-center', targets: [3, 5, 8] },
                { className: 'text-right', targets: [4] }
            ],
            language: {
                search: "Search orders:",
                lengthMenu: "Show _MENU_ orders per page",
                info: "Showing _START_ to _END_ of _TOTAL_ orders",
                infoEmpty: "No orders found",
                infoFiltered: "(filtered from _MAX_ total orders)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    });

    $('#onlineMenuOrdersTable').on('click', '.view-menu-order', function(){
        var id = $(this).data('id');
        window.location = 'online_menu_order_detail.php?id=' + id;
    });

    $('#onlineMenuOrdersTable').on('click', '.delete-menu-order', function(){
        var id = $(this).data('id');
        var orderNumber = $(this).data('order');
        
        if (confirm('Are you sure you want to delete order "' + orderNumber + '"? This action cannot be undone.')) {
            fetch('handlers/delete_online_menu_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'order_id=' + encodeURIComponent(id),
                credentials: 'include'
            })
            .then(function(res) {
                if (!res.ok) throw new Error('Network response was not ok: ' + res.status);
                return res.json();
            })
            .then(function(data) {
                if (data.success) {
                    alert('Order deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete order'));
                }
            })
            .catch(function(e) {
                console.error('Delete error:', e);
                alert('Network error: ' + e.message);
            });
        }
    });
</script>
