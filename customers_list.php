<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<div class="layout-content">
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="row align-items-center mb-3">
            <div class="col">
                <h4 class="font-weight-bold py-3 mb-0">Customers — Customer List</h4>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table id="customersTable" class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Address</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require __DIR__ . '/config/db.php';
                        $customers = [];
                        $stmt = $conn->prepare('SELECT id, first_name, last_name, email, phone, address, barangay, municipality, customer_type FROM customers ORDER BY id DESC');
                        if ($stmt) {
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) {
                                $customers[] = $r;
                            }
                            $stmt->close();
                        }
                        
                        if (empty($customers)) {
                            echo '<tr><td colspan="8" class="text-center text-muted py-4">No customers found.</td></tr>';
                        } else {
                            foreach ($customers as $c):
                                $addr = isset($c['address']) && $c['address'] ? substr($c['address'], 0, 30) : '-';
                                $phone = isset($c['phone']) ? $c['phone'] : '-';
                                $location = (isset($c['address']) && $c['address'] ? $c['address'] : '') . (isset($c['barangay']) && $c['barangay'] ? ', ' . $c['barangay'] : '') . (isset($c['municipality']) && $c['municipality'] ? ', ' . $c['municipality'] : '');
                                $location = trim($location, ', ') ?: '-';
                        ?>
                        <tr>
                            <td><?php echo (int)$c['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['email']); ?></td>
                            <td><?php echo htmlspecialchars($phone); ?></td>
                            <td>
                                <?php 
                                $type = isset($c['customer_type']) ? $c['customer_type'] : 'online_customer';
                                $badgeClass = ($type === 'diner') ? 'badge-info' : 'badge-primary';
                                $typeLabel = ($type === 'diner') ? 'Diner' : 'Online Customer';
                                echo '<span class="badge ' . $badgeClass . '">' . $typeLabel . '</span>';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($location); ?></td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-icon btn-outline-info view-customer" 
                                    data-id="<?php echo (int)$c['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>"
                                    data-email="<?php echo htmlspecialchars($c['email']); ?>"
                                    data-phone="<?php echo htmlspecialchars($phone); ?>"
                                    data-address="<?php echo htmlspecialchars($addr); ?>"
                                    data-barangay="<?php echo htmlspecialchars($c['barangay'] ?? ''); ?>"
                                    data-municipality="<?php echo htmlspecialchars($c['municipality'] ?? ''); ?>"
                                    data-customer-type="<?php echo htmlspecialchars($c['customer_type'] ?? 'online_customer'); ?>"
                                    title="View"><i class="feather icon-eye"></i></button>
                                <button class="btn btn-sm btn-icon btn-outline-primary edit-customer" 
                                    data-id="<?php echo (int)$c['id']; ?>"
                                    title="Edit"><i class="feather icon-edit-2"></i></button>
                                <button class="btn btn-sm btn-icon btn-outline-warning history-customer" 
                                    data-id="<?php echo (int)$c['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>"
                                    title="Purchase History"><i class="feather icon-book"></i></button>
                                <button class="btn btn-sm btn-icon btn-outline-danger delete-customer" data-id="<?php echo (int)$c['id']; ?>" title="Delete"><i class="feather icon-trash-2"></i></button>
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
</div>

<!-- View Customer Modal -->
<div id="viewCustomerModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewCustomerTitle">Customer Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeViewCustomer()">&times;</button>
            </div>
            <div class="modal-body">
                <dl>
                    <dt>Name</dt>
                    <dd id="viewCustomerName"></dd>
                    <dt>Email</dt>
                    <dd id="viewCustomerEmail"></dd>
                    <dt>Phone</dt>
                    <dd id="viewCustomerPhone"></dd>
                    <dt>Address</dt>
                    <dd id="viewCustomerAddress"></dd>
                    <dt>Barangay</dt>
                    <dd id="viewCustomerBarangay"></dd>
                    <dt>Municipality</dt>
                    <dd id="viewCustomerMunicipality"></dd>
                    <dt>Customer Type</dt>
                    <dd id="viewCustomerType"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewCustomer()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Purchase History Modal -->
<div id="historyModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Purchase History - <span id="historyCustomerName"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeHistory()">&times;</button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-striped" id="historyTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Item Type</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeHistory()">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(function() {
    // Initialize DataTable
    var table = $('#customersTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 10,
        columnDefs: [{
            orderable: false,
            targets: 6 // Actions column
        }]
    });

    $('#customersTable').on('click', '.view-customer', function() {
        var b = $(this);
        var type = b.data('customer-type') || 'online_customer';
        var typeLabel = (type === 'diner') ? 'Diner' : 'Online Customer';
        $('#viewCustomerTitle').text(b.data('name'));
        $('#viewCustomerName').text(b.data('name'));
        $('#viewCustomerEmail').text(b.data('email'));
        $('#viewCustomerPhone').text(b.data('phone'));
        $('#viewCustomerAddress').text(b.data('address') || '-');
        $('#viewCustomerBarangay').text(b.data('barangay') || '-');
        $('#viewCustomerMunicipality').text(b.data('municipality') || '-');
        $('#viewCustomerType').text(typeLabel);
        openViewCustomer();
    });

    $('#customersTable').on('click', '.edit-customer', function() {
        var id = $(this).data('id');
        window.location.href = 'customers_profile.php?id=' + id;
    });

    $('#customersTable').on('click', '.history-customer', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        loadPurchaseHistory(id, name);
    });

    $('#customersTable').on('click', '.delete-customer', function() {
        var id = $(this).data('id');
        if (!confirm('Delete customer #' + id + '?')) return;
        $.post('handlers/customer_delete.php', { id: id }, function(resp) {
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

function openViewCustomer() {
    $('#viewCustomerModal').show();
}

function closeViewCustomer() {
    $('#viewCustomerModal').hide();
}

function openHistory() {
    $('#historyModal').show();
}

function closeHistory() {
    $('#historyModal').hide();
}

function loadPurchaseHistory(customerId, customerName) {
    $('#historyCustomerName').text(customerName);
    $('#historyTableBody').html('<tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>');
    openHistory();
    
    $.get('handlers/customer_history.php', { customer_id: customerId }, function(resp) {
        try {
            var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
            if (j.ok && j.data) {
                var html = '';
                if (j.data.length === 0) {
                    html = '<tr><td colspan="6" class="text-center text-muted">No purchase records found.</td></tr>';
                } else {
                    j.data.forEach(function(row) {
                        html += '<tr>';
                        html += '<td>' + row.order_id + '</td>';
                        var itype = row.item_type || 'menu';
                        var badge = (itype === 'fish') ? 'info' : 'primary';
                        var label = (typeof itype === 'string' && itype.length) ? itype.charAt(0).toUpperCase() + itype.slice(1) : itype;
                        html += '<td><span class="badge badge-light-' + badge + '">' + label + '</span></td>';
                        html += '<td>' + row.item_name + '</td>';
                        html += '<td>' + row.quantity + '</td>';
                        html += '<td>₱' + parseFloat(row.total_price).toFixed(2) + '</td>';
                        html += '<td>' + row.order_date + '</td>';
                        html += '</tr>';
                    });
                }
                $('#historyTableBody').html(html);
            } else {
                $('#historyTableBody').html('<tr><td colspan="6" class="text-center text-muted">No records found or error loading data.</td></tr>');
            }
        } catch (e) {
            $('#historyTableBody').html('<tr><td colspan="6" class="text-center text-muted">Error loading history.</td></tr>');
        }
    });
}
</script>