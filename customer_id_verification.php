<?php
include 'auth_admin.php';
include 'partials/head.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Customer ID Verification</h4>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item active">Customer ID Verification</li>
            </ol>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-dismissible fade show" role="alert" style="background-color: #28a745; color: white; border: none; border-radius: 6px; padding: 15px 20px; font-weight: 500;">
                <i class="feather icon-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: white; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Notification Container for AJAX responses -->
        <div id="notificationContainer"></div>

        <!-- Pending Verification -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-header-title">Pending Verification</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="pendingTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th>Government ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require 'config/db.php';
                        
                        // Query that includes both created_at and updated_at
                        $query = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email, phone, created_at, updated_at, government_id_image 
                                 FROM customers 
                                 WHERE government_id_verified = 0 AND government_id_image IS NOT NULL
                                 ORDER BY COALESCE(updated_at, created_at) DESC";
                        $result = $conn->query($query);
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $name = htmlspecialchars($row['name']);
                                $email = htmlspecialchars($row['email']);
                                $phone = htmlspecialchars($row['phone']);
                                
                                // Use updated_at if it exists (means resubmission), otherwise use created_at
                                $display_date = !empty($row['updated_at']) ? $row['updated_at'] : $row['created_at'];
                                $submit_date = date('M d, Y', strtotime($display_date));
                                $image = htmlspecialchars($row['government_id_image']);
                                $id = $row['id'];
                                
                                // Consider it a resubmission only when updated_at exists and differs from created_at
                                $is_resubmission = false;
                                if (!empty($row['updated_at'])) {
                                    // treat as resubmission only if updated_at is different (or later) than created_at
                                    $is_resubmission = ($row['updated_at'] !== $row['created_at']);
                                }
                                $status_badge = $is_resubmission ? '<span class="badge badge-warning"><i class="feather icon-refresh-cw"></i> Resubmission</span>' : '<span class="badge badge-info"><i class="feather icon-clock"></i> Pending</span>';
                                
                                echo "<tr>";
                                echo "<td><strong>$name</strong></td>";
                                echo "<td>$email</td>";
                                echo "<td>$phone</td>";
                                echo "<td>$submit_date</td>";
                                echo "<td>$status_badge</td>";
                                echo "<td><button class='btn btn-sm btn-info view-id-btn' data-id='$id' data-image='$image'><i class='feather icon-eye'></i> View</button></td>";
                                echo "<td style='display:flex; gap:5px;'>";
                                echo "<form method='POST' action='handlers/customer_id_verification.php' style='display:inline;'>";
                                echo "<input type='hidden' name='customer_id' value='$id'>";
                                echo "<button type='submit' name='action' value='approve' class='btn btn-sm btn-success' onclick=\"return confirm('Verify this customer\\\'s ID?')\"><i class='feather icon-check'></i> Verify</button> ";
                                echo "<button type='submit' name='action' value='reject' class='btn btn-sm btn-danger' style='margin-left:8px; margin-right:8px;' onclick=\"return confirm('Reject this ID?\\\n\\\nThe customer will need to re-submit.')\"><i class='feather icon-x'></i> Reject</button>";
                                echo "</form>";
                                echo "<button class='btn btn-sm btn-outline-secondary send-feedback-btn' data-customer-id='$id' data-customer-name='$name' data-customer-email='$email' title='Send Feedback'><i class='feather icon-mail'></i> Feedback</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center text-muted py-4'>No pending verifications</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Verified Customers -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-header-title">Verified Customers</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="verifiedTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Verified Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email, phone, id_verification_date 
                                 FROM customers 
                                 WHERE government_id_verified = 1 AND government_id_image IS NOT NULL
                                 ORDER BY id_verification_date DESC";
                        $result = $conn->query($query);
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $customerId = $row['id'];
                                echo "
                                    <tr>
                                        <td><strong>" . htmlspecialchars($row['name']) . "</strong></td>
                                        <td>" . htmlspecialchars($row['email']) . "</td>
                                        <td>" . htmlspecialchars($row['phone']) . "</td>
                                        <td>" . ($row['id_verification_date'] ? date('M d, Y', strtotime($row['id_verification_date'])) : 'N/A') . "</td>
                                        <td><span class='badge badge-success'>Verified</span></td>
                                        <td><button class='btn btn-sm btn-icon btn-outline-danger delete-verified-customer' data-customer-id='$customerId' data-customer-name='" . htmlspecialchars($row['name']) . "' title='Delete'><i class='feather icon-trash-2'></i></button></td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center text-muted py-4'>No verified customers yet</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->

    <?php include 'partials/footer.php'; ?>

<!-- Modal for viewing ID Image -->
<div class="modal fade" id="viewIdModal" tabindex="-1" role="dialog" aria-labelledby="viewIdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewIdModalLabel">Government ID Verification</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="idImagePreview" src="" alt="Government ID" style="max-width:100%; max-height:500px; border-radius:8px;">
            </div>
            <div class="modal-footer">
                <p class="text-muted mr-auto" id="idVerificationInfo"></p>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for sending feedback -->
<div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">Send Feedback to Customer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="feedbackForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Customer Name</label>
                        <input type="text" class="form-control" id="feedbackCustomerName" readonly>
                    </div>
                    <div class="form-group">
                        <label>Customer Email</label>
                        <input type="email" class="form-control" id="feedbackCustomerEmail" readonly>
                    </div>
                    <input type="hidden" id="feedbackCustomerId">
                    <div class="form-group">
                        <label for="feedbackNotes">Notes/Feedback <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="feedbackNotes" rows="4" placeholder="Enter your feedback or notes for the customer..." required></textarea>
                        <small class="form-text text-muted">This message will be sent to the customer's email.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitFeedbackBtn">Send Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
// Helper function to show Bootstrap notifications with better readability
function showNotification(message, type = 'info') {
    // Custom color scheme with better contrast - dark backgrounds with white text
    const colors = {
        'success': { bg: '#28a745', text: 'white' },
        'error': { bg: '#dc3545', text: 'white' },
        'warning': { bg: '#ff8c00', text: 'white' },
        'info': { bg: '#007bff', text: 'white' }
    };
    
    const color = colors[type] || colors['info'];
    const icon = type === 'success' ? 'check-circle' : 
                type === 'error' ? 'alert-circle' : 
                type === 'warning' ? 'alert-triangle' : 'info';
    
    const html = `
        <div class="alert alert-dismissible fade show" role="alert" style="background-color: ${color.bg}; color: ${color.text}; border: none; border-radius: 6px; padding: 15px 20px; font-weight: 500;">
            <i class="feather icon-${icon}" style="margin-right: 8px;"></i> ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: ${color.text}; opacity: 0.8;">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('#notificationContainer').html(html);
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('#notificationContainer .alert').fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}

// View ID Button - Global Handler (before $(document).ready))
$(document).on('click', '.view-id-btn', function(e) {
    e.preventDefault();
    const customerId = $(this).data('id');
    const imageName = $(this).data('image');

    if (!imageName) {
        showNotification('No government ID image found', 'error');
        return;
    }

    $('#idImagePreview').attr('src', 'assets/img/customer_ids/' + imageName);
    $('#idVerificationInfo').text('Customer ID: ' + customerId);
    $('#viewIdModal').modal('show');
});

// Send Feedback Button - Global Handler
$(document).on('click', '.send-feedback-btn', function(e) {
    e.preventDefault();
    const customerId = $(this).data('customer-id');
    const customerName = $(this).data('customer-name');
    const customerEmail = $(this).data('customer-email');

    $('#feedbackCustomerId').val(customerId);
    $('#feedbackCustomerName').val(customerName);
    $('#feedbackCustomerEmail').val(customerEmail);
    $('#feedbackNotes').val('');
    $('#feedbackModal').modal('show');
});

// Delete Verified Customer Button - Global Handler
$(document).on('click', '.delete-verified-customer', function(e) {
    e.preventDefault();
    const customerId = $(this).data('customer-id');
    const customerName = $(this).data('customer-name');

    if (!confirm('Delete verified customer ' + customerName + '?')) return;

    $.post('handlers/customer_id_delete.php', { customer_id: customerId }, function(resp) {
        try {
            const json = typeof resp === 'string' ? JSON.parse(resp) : resp;
            if (json.ok) {
                showNotification('Customer deleted successfully', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showNotification('Error: ' + (json.msg || 'Failed to delete customer'), 'error');
            }
        } catch (e) {
            showNotification('Error deleting customer', 'error');
        }
    }).fail(function() {
        showNotification('Failed to delete customer', 'error');
    });
});

$(document).ready(function() {
    // Only initialize DataTables if table has data rows (not empty state)
    if ($('#pendingTable tbody tr:not(:last)').length > 0 || $('#pendingTable tbody tr td[colspan]').length === 0) {
        $('#pendingTable').DataTable({
            "columnDefs": [
                { "orderable": false, "targets": [4, 5, 6] }
            ]
        });
    }
    
    // Only initialize DataTables if table has data rows
    if ($('#verifiedTable tbody tr:not(:last)').length > 0 || $('#verifiedTable tbody tr td[colspan]').length === 0) {
        $('#verifiedTable').DataTable({
            "columnDefs": [
                { "orderable": false, "targets": [5] }
            ]
        });
    }
    
    // Submit feedback form
    $('#feedbackForm').on('submit', function(e) {
        e.preventDefault();
        const customerId = $('#feedbackCustomerId').val();
        const customerEmail = $('#feedbackCustomerEmail').val();
        const notes = $('#feedbackNotes').val();
        
        if (!customerId || !customerEmail || !notes.trim()) {
            showNotification('Please fill in all required fields', 'warning');
            return;
        }
        
        $('#submitFeedbackBtn').prop('disabled', true).html('Sending...');
        
        $.post('handlers/send_customer_feedback.php', {
            customer_id: customerId,
            customer_email: customerEmail,
            notes: notes
        }, function(resp) {
            $('#submitFeedbackBtn').prop('disabled', false).html('Send Feedback');
            try {
                const json = typeof resp === 'string' ? JSON.parse(resp) : resp;
                if (json.ok) {
                    showNotification('Feedback sent successfully!', 'success');
                    $('#feedbackModal').modal('hide');
                    $('#feedbackForm')[0].reset();
                } else {
                    showNotification('Error: ' + (json.msg || 'Failed to send feedback'), 'error');
                }
            } catch (e) {
                showNotification('Error sending feedback', 'error');
            }
        }).fail(function() {
            $('#submitFeedbackBtn').prop('disabled', false).html('Send Feedback');
            showNotification('Failed to send feedback', 'error');
        });
    });
});
</script>
