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
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="feather icon-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

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
                            <th>Registered Date</th>
                            <th>Government ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require 'config/db.php';
                        
                        $query = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email, phone, created_at, government_id_image 
                                 FROM customers 
                                 WHERE government_id_verified = 0 AND government_id_image IS NOT NULL
                                 ORDER BY created_at DESC";
                        $result = $conn->query($query);
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $name = htmlspecialchars($row['name']);
                                $email = htmlspecialchars($row['email']);
                                $phone = htmlspecialchars($row['phone']);
                                $date = date('M d, Y', strtotime($row['created_at']));
                                $image = htmlspecialchars($row['government_id_image']);
                                $id = $row['id'];
                                echo "<tr>";
                                echo "<td><strong>$name</strong></td>";
                                echo "<td>$email</td>";
                                echo "<td>$phone</td>";
                                echo "<td>$date</td>";
                                echo "<td><button class='btn btn-sm btn-info view-id-btn' data-id='$id' data-image='$image'><i class='feather icon-eye'></i> View</button></td>";
                                echo "<td><form method='POST' action='handlers/customer_id_verification.php' style='display:inline;'>";
                                echo "<input type='hidden' name='customer_id' value='$id'>";
                                echo "<button type='submit' name='action' value='approve' class='btn btn-sm btn-success' onclick=\"return confirm('Verify this customer\\'s ID?')\"><i class='feather icon-check'></i> Verify</button> ";
                                echo "<button type='submit' name='action' value='reject' class='btn btn-sm btn-danger' onclick=\"return confirm('Reject this ID?\\n\\nThe customer will need to re-submit.')\"><i class='feather icon-x'></i> Reject</button>";
                                echo "</form></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td class='text-center text-muted py-4' style='display:none;'></td><td style='display:none;'></td><td style='display:none;'></td><td style='display:none;'></td><td style='display:none;'></td><td style='display:none;'></td></tr>";
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
                                echo "
                                    <tr>
                                        <td><strong>" . htmlspecialchars($row['name']) . "</strong></td>
                                        <td>" . htmlspecialchars($row['email']) . "</td>
                                        <td>" . htmlspecialchars($row['phone']) . "</td>
                                        <td>" . ($row['id_verification_date'] ? date('M d, Y', strtotime($row['id_verification_date'])) : 'N/A') . "</td>
                                        <td><span class='badge badge-success'>Verified</span></td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center text-muted py-4'>No verified customers yet</td></tr>";
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#pendingTable').DataTable({
        "columnDefs": [
            { "orderable": false, "targets": [4, 5] }
        ]
    });
    
    $('.view-id-btn').on('click', function(e) {
        e.preventDefault();
        const customerId = $(this).data('id');
        const imageName = $(this).data('image');
        
        if (!imageName) {
            alert('No government ID image found');
            return;
        }
        
        $('#idImagePreview').attr('src', 'assets/img/customer_ids/' + imageName);
        $('#idVerificationInfo').text('Customer ID: ' + customerId);
        $('#viewIdModal').modal('show');
    });
});
</script>
