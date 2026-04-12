<?php include 'auth_admin.php'; ?>
<?php $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'); ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="font-weight-bold py-3 mb-1">🚶 Walk-in Fish Orders</h4>
                <p class="text-muted small mb-0">Direct orders created at the counter by staff for walk-in customers</p>
            </div>
        </div>
        <div class="card mt-3">
            <div class="table-responsive">
                <table id="fishOrdersTable" class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require __DIR__ . '/config/db.php';
                        $rows = [];
                        
                        // Select from fish_orders table
                        $stmt = $conn->prepare('SELECT id, order_number, customer_name, customer_contact, total_amount, status, created_at FROM fish_orders ORDER BY id DESC');
                        if (!$stmt) {
                            $dberr = $conn->error;
                            echo '<tr><td colspan="8" class="text-danger text-center py-3">DB error: ' . htmlspecialchars($dberr) . '</td></tr>';
                        }
                        if ($stmt) {
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) $rows[] = $r;
                            $stmt->close();
                        }

                        if (empty($rows)) {
                            echo '<tr><td colspan="8" class="text-center text-muted py-4">No walk-in fish orders found.</td></tr>';
                        } else {
                            foreach ($rows as $r):
                                // count items
                                $itemStmt = $conn->prepare('SELECT COUNT(*) as cnt FROM fish_order_items WHERE fish_order_id = ?');
                                $cnt = 0;
                                if ($itemStmt) {
                                    $itemStmt->bind_param('i', $r['id']);
                                    $itemStmt->execute();
                                    $cres = $itemStmt->get_result();
                                    if ($cres && $crow = $cres->fetch_assoc()) $cnt = $crow['cnt'];
                                    $itemStmt->close();
                                }

                                $customer = $r['customer_name'] ?? 'Direct Order';
                                
                                // Determine status badge color
                                $statusColor = '';
                                $statusBg = '';
                                switch (strtolower($r['status'])) {
                                    case 'paid':
                                        $statusColor = '#4caf50';
                                        $statusBg = '#e8f5e9';
                                        break;
                                    case 'pending':
                                        $statusColor = '#ff9800';
                                        $statusBg = '#fff3e0';
                                        break;
                                    case 'completed':
                                        $statusColor = '#2196f3';
                                        $statusBg = '#e3f2fd';
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
                            <td><?php echo (int)$r['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($customer); ?><br><small style="color:#999;"><?php echo htmlspecialchars($r['customer_contact'] ?? ''); ?></small></td>
                            <td><?php echo (int)$cnt; ?> item(s)</td>
                            <td><strong>₱<?php echo number_format($r['total_amount'],2); ?></strong></td>
                            <td><span style="background-color:<?php echo $statusBg; ?>; color:<?php echo $statusColor; ?>; padding:6px 12px; border-radius:4px; font-weight:600; font-size:12px;"><?php echo htmlspecialchars(ucfirst($r['status'])); ?></span></td>
                            <td><?php echo htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($r['created_at']))); ?></td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-icon btn-outline-info view-fish-order"
                                    data-id="<?php echo (int)$r['id']; ?>"
                                    data-order-number="<?php echo htmlspecialchars($r['order_number']); ?>"
                                    data-customer="<?php echo htmlspecialchars($customer); ?>"
                                    data-contact="<?php echo htmlspecialchars($r['customer_contact'] ?? ''); ?>"
                                    data-total="<?php echo number_format($r['total_amount'], 2); ?>"
                                    data-status="<?php echo htmlspecialchars($r['status']); ?>"
                                    data-created="<?php echo htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($r['created_at']))); ?>"
                                    title="View"><i class="feather icon-eye"></i></button>
                                <?php if ($isAdmin): ?>
                                <button class="btn btn-sm btn-icon btn-outline-danger delete-fish-order"
                                    data-id="<?php echo (int)$r['id']; ?>"
                                    title="Delete"><i class="feather icon-trash-2"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->

    <?php include 'partials/footer.php'; ?>
</div>
<!-- [ Layout content ] End -->

<!-- View Fish Order Modal -->
<div id="viewFishOrderModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fish Order Details - <span id="viewOrderNumber"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeViewFishOrder()">&times;</button>
            </div>
            <div class="modal-body">
                <dl>
                    <dt>Customer Name</dt>
                    <dd id="viewCustomerName"></dd>
                    <dt>Customer Contact</dt>
                    <dd id="viewCustomerContact"></dd>
                    <dt>Total Amount</dt>
                    <dd id="viewTotalAmount"></dd>
                    <dt>Status</dt>
                    <dd style="display:flex; gap:10px; align-items:center;">
                        <span id="viewStatus"></span>
                        <select id="statusUpdate" style="padding:6px 10px; border:1px solid #ddd; border-radius:4px; font-size:14px;">
                            <option value="">Change Status...</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </dd>
                    <dt>Order Date</dt>
                    <dd id="viewOrderDate"></dd>
                </dl>
                <h6 style="margin-top:20px; font-weight:600;">Order Items</h6>
                <table id="viewFishOrderItemsTable" class="table table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th>Fish Name</th>
                            <th>Weight (kg/g)</th>
                            <th>Price per kg</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="viewFishOrderItemsBody">
                        <tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewFishOrder()">Close</button>
                <button type="button" id="printReceiptBtn" class="btn btn-primary" style="display:none;" onclick="openFishReceipt()">Print Receipt</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    var fishOrderCount = <?php echo json_encode(count($rows)); ?>;
</script>
<script>
$(function() {
    // Only initialize DataTables if there are rows
    if (fishOrderCount > 0) {
        var table = $('#fishOrdersTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10,
            columnDefs: [{ orderable: false, targets: 7 }],
            columns: [
                { data: 0 },  // Order ID
                { data: 1 },  // Order #
                { data: 2 },  // Customer
                { data: 3 },  // Items
                { data: 4 },  // Total Amount
                { data: 5 },  // Status
                { data: 6 },  // Order Date
                { data: 7, orderable: false }  // Actions
            ]
        });
    }

    var currentFishOrderId = null;

    $('#fishOrdersTable').on('click', '.view-fish-order', function() {
        console.log('=== View button clicked ===');
        var id = $(this).data('id');
        console.log('Order ID:', id);
        currentFishOrderId = id;
        loadFishOrderDetails(id);
    });

    // Status update in modal
    $('#statusUpdate').on('change', function() {
        var newStatus = $(this).val();
        if (!newStatus || !currentFishOrderId) {
            $(this).val('');
            return;
        }
        
        $.post('handlers/fish_order_update.php', { fish_order_id: currentFishOrderId, status: newStatus }, function(resp) {
            closeViewFishOrder();
            location.reload();
        }).fail(function() {
            alert('Failed to update status');
            $('#statusUpdate').val('');
        });
    });

    $('#fishOrdersTable').on('click', '.delete-fish-order', function() {
        var id = $(this).data('id');
        if (!confirm('Delete fish order #' + id + '?')) return;
        
        $.post('handlers/fish_order_delete.php', { id: id }, function(resp) {
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

function loadFishOrderDetails(fishOrderId) {
    console.log('=== loadFishOrderDetails called with:', fishOrderId);
    $('#viewFishOrderItemsBody').html('<tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>');
    openViewFishOrder();
    
    console.log('About to call AJAX to handlers/fish_order_details.php');
    $.get('handlers/fish_order_details.php', { fish_order_id: fishOrderId }, function(resp) {
        console.log('=== AJAX Success callback fired ===');
        console.log('Response type:', typeof resp);
        console.log('Response object:', resp);
        
        try {
            var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
            console.log('Parsed JSON:', j);
            console.log('j.ok:', j.ok);
            console.log('j.data:', j.data);
            
            if (j.ok && j.data) {
                console.log('✓ j.ok and j.data are true');
                var order = j.data;
                console.log('Order object:', order);
                var orderId = order.id || null;
                console.log('ORDER ID EXTRACTED:', orderId);
                console.log('About to set data-order-id on printReceiptBtn...');
                
                $('#viewOrderNumber').text(order.order_number);
                console.log('Setting viewCustomerName to:', order.customer_name);
                $('#viewCustomerName').text(order.customer_name || 'Direct Order');
                console.log('Setting viewCustomerContact to:', order.customer_contact);
                $('#viewCustomerContact').text(order.customer_contact || '-');
                console.log('Setting viewTotalAmount');
                $('#viewTotalAmount').text('₱' + parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                console.log('Setting viewStatus to:', order.status);
                $('#viewStatus').text(order.status.charAt(0).toUpperCase() + order.status.slice(1));
                console.log('Setting viewOrderDate to:', order.created_at);
                // Format date to readable format
                var orderDate = order.created_at ? new Date(order.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '-';
                $('#viewOrderDate').text(orderDate);
                
                // Show print button - always show it for testing
                console.log('✓ Showing print button');
                console.log('Setting data-order-id to:', orderId);
                $('#printReceiptBtn').show().data('order-id', orderId);
                console.log('Verifying data-order-id was set:', $('#printReceiptBtn').data('order-id'));
                
                var html = '';
                console.log('j.items:', j.items);
                console.log('j.items.length:', j.items ? j.items.length : 'N/A');
                
                if (j.items && j.items.length) {
                    console.log('Processing ' + j.items.length + ' items');
                    j.items.forEach(function(item) {
                        console.log('Item:', item);
                        html += '<tr>';
                        html += '<td>' + item.fish_name + '</td>';
                        html += '<td>' + parseFloat(item.quantity).toFixed(2) + '</td>';
                        html += '<td>₱' + parseFloat(item.unit_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                        html += '<td>₱' + parseFloat(item.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                        html += '</tr>';
                    });
                } else {
                    console.log('No items found');
                    html = '<tr><td colspan="4" class="text-center text-muted">No items found</td></tr>';
                }
                console.log('Setting HTML to:', html);
                $('#viewFishOrderItemsBody').html(html);
                console.log('✓ Modal data populated successfully');
            } else {
                console.error('✗ j.ok or j.data is missing');
                console.error('j.ok:', j.ok, 'j.data:', j.data);
                alert('Failed to load order details');
            }
        } catch (e) {
            console.error('✗ Error in try/catch:', e);
            alert('Error loading order details: ' + e.message);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('=== AJAX Failed ===');
        console.error('textStatus:', textStatus);
        console.error('errorThrown:', errorThrown);
        console.error('jqXHR.status:', jqXHR.status);
        console.error('jqXHR.responseText:', jqXHR.responseText);
        alert('AJAX Error: ' + textStatus);
    });
}

function openViewFishOrder() {
    console.log('=== Opening fish order modal ===');
    $('#viewFishOrderModal').show();
    console.log('Modal shown');
}

function closeViewFishOrder() {
    console.log('=== Closing fish order modal ===');
    $('#viewFishOrderModal').hide();
    $('#statusUpdate').val('');
}

function openFishReceipt() {
    console.log('=== openFishReceipt() CALLED ===');
    console.log('Checking #printReceiptBtn element...');
    var btn = $('#printReceiptBtn');
    console.log('Button element found:', btn.length);
    console.log('Button element:', btn);
    var id = btn.data('order-id');
    console.log('Retrieved data-order-id:', id);
    console.log('Type of id:', typeof id);
    if (!id) {
        console.error('❌ NO ORDER ID FOUND!');
        alert('No order selected');
        return;
    }
    var url = 'fish_order_receipt.php?id=' + encodeURIComponent(id);
    console.log('Opening URL:', url);
    console.log('Calling window.open...');
    var w = window.open(url, '_blank');
    console.log('window.open returned:', w);
}
</script>
