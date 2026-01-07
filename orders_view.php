<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Orders — View Orders</h4>
        <div class="card mt-3">
            <div class="table-responsive">
                <table id="ordersTable" class="table table-sm mb-0">
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
                        $stmt = $conn->prepare('SELECT o.id, o.order_number, o.customer_id, c.first_name, c.last_name, c.email, o.total_amount, o.status, o.order_date, o.pickup_date FROM orders o JOIN customers c ON o.customer_id = c.id ORDER BY o.id DESC');
                        if ($stmt) {
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) {
                                $orders[] = $r;
                            }
                            $stmt->close();
                        }
                        
                        if (empty($orders)) {
                            echo '<tr><td colspan="9" class="text-center text-muted py-4">No orders found.</td></tr>';
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
                                    case 'paid': 
                                        $statusColor = '#4caf50'; 
                                        $statusBg = '#e8f5e9';
                                        break;
                                    case 'cancelled': 
                                        $statusColor = '#f44336'; 
                                        $statusBg = '#ffebee';
                                        break;
                                    default: 
                                        $statusColor = '#666'; 
                                        $statusBg = '#f5f5f5';
                                }
                        ?>
                        <tr>
                            <td><?php echo (int)$o['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?><br><small style="color:#999;"><?php echo htmlspecialchars($o['email']); ?></small></td>
                            <td><?php echo $itemCount; ?> item(s)</td>
                            <td><strong>₱<?php echo number_format($o['total_amount'], 2); ?></strong></td>
                            <td><span style="background-color:<?php echo $statusBg; ?>; color:<?php echo $statusColor; ?>; padding:6px 12px; border-radius:4px; font-weight:600; font-size:12px;"><?php echo htmlspecialchars(ucfirst($o['status'])); ?></span></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($o['order_date']))); ?></td>
                            <td><?php echo $o['pickup_date'] ? htmlspecialchars($o['pickup_date']) : '-'; ?></td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-icon btn-outline-info view-order" 
                                    data-id="<?php echo (int)$o['id']; ?>"
                                    data-order-number="<?php echo htmlspecialchars($o['order_number']); ?>"
                                    data-customer="<?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?>"
                                    data-email="<?php echo htmlspecialchars($o['email']); ?>"
                                    data-total="<?php echo number_format($o['total_amount'], 2); ?>"
                                    data-status="<?php echo htmlspecialchars($o['status']); ?>"
                                    data-order-date="<?php echo htmlspecialchars(date('M d, Y', strtotime($o['order_date']))); ?>"
                                    data-pickup-date="<?php echo htmlspecialchars($o['pickup_date'] ?? ''); ?>"
                                    title="View"><i class="feather icon-eye"></i></button>
                                <button class="btn btn-sm btn-icon btn-outline-danger delete-order" 
                                    data-id="<?php echo (int)$o['id']; ?>"
                                    title="Delete"><i class="feather icon-trash-2"></i></button>
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

    <?php include 'partials/footer.php'; ?>
</div>

<!-- View Order Modal -->
<div id="viewOrderModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details - <span id="viewOrderNumber"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeViewOrder()">&times;</button>
            </div>
            <div class="modal-body">
                <dl>
                    <dt>Customer Name</dt>
                    <dd id="viewCustomerName"></dd>
                    <dt>Customer Email</dt>
                    <dd id="viewCustomerEmail"></dd>
                    <dt>Total Amount</dt>
                    <dd id="viewTotalAmount"></dd>
                    <dt>Status</dt>
                    <dd style="display:flex; gap:10px; align-items:center;">
                        <span id="viewStatus"></span>
                        <select id="statusUpdate" style="padding:6px 10px; border:1px solid #ddd; border-radius:4px; font-size:14px;">
                            <option value="">Change Status...</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="paid">Paid</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </dd>
                    <dt>Order Date</dt>
                    <dd id="viewOrderDate"></dd>
                    <dt>Pickup Date</dt>
                    <dd id="viewPickupDate"></dd>
                </dl>
                <h6 style="margin-top:20px; font-weight:600;">Order Items</h6>
                <table id="viewOrderItemsTable" class="table table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="viewOrderItemsBody">
                        <tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewOrder()">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(function() {
    // Initialize DataTable
    // Ensure DataTables column definition matches the number of THs to avoid tn/18 errors
    var headerCount = $('#ordersTable thead th').length;
    var $firstRow = $('#ordersTable tbody tr:first-child');
    var firstRowTds = $firstRow.length ? $firstRow.children('td').length : 0;
    // If first row is a single placeholder cell with colspan equal to headers, remove it so DataTables can initialize
    if ($firstRow.length && firstRowTds === 1) {
        var colspan = parseInt($firstRow.children('td').attr('colspan') || 0, 10);
        if (colspan === headerCount) {
            $firstRow.remove();
            firstRowTds = 0;
        }
    }

    if (headerCount !== 0 && firstRowTds !== 0 && headerCount !== firstRowTds) {
        console.warn('DataTables init: headerCount=', headerCount, ' firstRowCount=', firstRowTds);
    }

    var columnsArr = [];
    for (var i = 0; i < headerCount; i++) columnsArr.push(null);

    var table = $('#ordersTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 10,
        columns: columnsArr,
        columnDefs: [{ orderable: false, targets: 8 }]
    });

    var currentOrderId = null;

    $('#ordersTable').on('click', '.view-order', function() {
        var id = $(this).data('id');
        currentOrderId = id;
        loadOrderDetails(id);
    });

    // Status update in modal
    $('#statusUpdate').on('change', function() {
        var newStatus = $(this).val();
        if (!newStatus || !currentOrderId) {
            $(this).val('');
            return;
        }
        
        $.post('orders_update.php', { order_id: currentOrderId, status: newStatus }, function(resp) {
            closeViewOrder();
            location.reload();
        }).fail(function() {
            alert('Failed to update status');
            $('#statusUpdate').val('');
        });
    });

    $('#ordersTable').on('click', '.delete-order', function() {
        var id = $(this).data('id');
        if (!confirm('Delete order #' + id + '?')) return;
        
        $.post('handlers/order_delete.php', { id: id }, function(resp) {
            try {
                var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
                if (j.ok) location.reload();
                else alert(j.msg || 'Delete failed');
            } catch (e) {
                alert('Delete failed');
            }
        });
    });
});

function loadOrderDetails(orderId) {
    $('#viewOrderItemsBody').html('<tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>');
    openViewOrder();
    
    $.get('handlers/order_details.php', { order_id: orderId }, function(resp) {
        try {
            var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
            if (j.ok && j.data) {
                var order = j.data;
                $('#viewOrderNumber').text(order.order_number);
                $('#viewCustomerName').text(order.customer_name);
                $('#viewCustomerEmail').text(order.customer_email);
                $('#viewTotalAmount').text('₱' + parseFloat(order.total_amount).toFixed(2));
                $('#viewStatus').text(order.status.charAt(0).toUpperCase() + order.status.slice(1));
                $('#viewOrderDate').text(order.order_date);
                $('#viewPickupDate').text(order.pickup_date || '-');
                
                var html = '';
                if (j.items && j.items.length) {
                    j.items.forEach(function(item) {
                        html += '<tr>';
                        html += '<td>' + item.item_name + '</td>';
                        html += '<td>' + item.quantity + '</td>';
                        html += '<td>₱' + parseFloat(item.unit_price).toFixed(2) + '</td>';
                        html += '<td>₱' + parseFloat(item.subtotal).toFixed(2) + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="4" class="text-center text-muted">No items found</td></tr>';
                }
                $('#viewOrderItemsBody').html(html);
            } else {
                alert('Failed to load order details');
            }
        } catch (e) {
            alert('Error loading order details');
        }
    });
}

function openViewOrder() {
    $('#viewOrderModal').show();
}

function closeViewOrder() {
    $('#viewOrderModal').hide();
}
</script>