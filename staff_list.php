<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="font-weight-bold py-3 mb-0">Staff — Staff List</h4>
            <a href="staff_add.php" class="btn btn-primary">
                <i class="feather icon-plus"></i> Add New Staff
            </a>
        </div>

        <div class="card mt-3">
            <div class="table-responsive">
                <table id="staffTable" class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Hire Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require __DIR__ . '/config/db.php';
                        
                        $staff_list = [];
                        $query = 'SELECT s.id, s.first_name, s.last_name, s.email, s.phone, s.position, s.department, s.hire_date, s.status, s.user_id, u.username, u.role FROM staff s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.id DESC';
                        
                        $stmt = $conn->prepare($query);
                        if ($stmt) {
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($row = $res->fetch_assoc()) {
                                $staff_list[] = $row;
                            }
                            $stmt->close();
                        }
                        
                        if (empty($staff_list)) {
                            echo '<tr><td colspan="10" class="text-center text-muted py-4">No staff members found. <a href="staff_add.php">Add one now</a></td></tr>';
                        } else {
                            foreach ($staff_list as $staff):
                                $full_name = htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']);
                                $status = $staff['status'] ?? 'active';
                                $role = $staff['role'] ?? 'staff';
                                
                                // Status color coding
                                $statusColor = '';
                                $statusBg = '';
                                switch ($status) {
                                    case 'active':
                                        $statusColor = '#4caf50';
                                        $statusBg = '#e8f5e9';
                                        break;
                                    case 'inactive':
                                        $statusColor = '#f44336';
                                        $statusBg = '#ffebee';
                                        break;
                                    default:
                                        $statusColor = '#666';
                                        $statusBg = '#f5f5f5';
                                }
                                
                                // Role color coding
                                $roleColor = '';
                                $roleBg = '';
                                switch ($role) {
                                    case 'admin':
                                        $roleColor = '#f44336';
                                        $roleBg = '#ffebee';
                                        break;
                                    case 'manager':
                                        $roleColor = '#2196f3';
                                        $roleBg = '#e3f2fd';
                                        break;
                                    case 'staff':
                                        $roleColor = '#ff9800';
                                        $roleBg = '#fff3e0';
                                        break;
                                    default:
                                        $roleColor = '#666';
                                        $roleBg = '#f5f5f5';
                                }
                                
                                $hire_date = $staff['hire_date'] ? date('M d, Y', strtotime($staff['hire_date'])) : 'N/A';
                        ?>
                        <tr>
                            <td><?php echo (int)$staff['id']; ?></td>
                            <td><strong><?php echo $full_name; ?></strong><br><small style="color:#999;"><?php echo htmlspecialchars($staff['phone'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($staff['username'] ?? '-'); ?></td>
                            <td><small><?php echo htmlspecialchars($staff['email']); ?></small></td>
                            <td><?php echo htmlspecialchars($staff['position'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($staff['department'] ?? '-'); ?></td>
                            <td>
                                <span style="background-color:<?php echo $roleBg; ?>; color:<?php echo $roleColor; ?>; padding:4px 10px; border-radius:4px; font-weight:600; font-size:11px;">
                                    <?php echo htmlspecialchars(ucfirst($role)); ?>
                                </span>
                            </td>
                            <td>
                                <span style="background-color:<?php echo $statusBg; ?>; color:<?php echo $statusColor; ?>; padding:4px 10px; border-radius:4px; font-weight:600; font-size:11px;">
                                    <?php echo htmlspecialchars(ucfirst($status)); ?>
                                </span>
                            </td>
                            <td><?php echo $hire_date; ?></td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-icon btn-outline-primary view-staff" 
                                    data-id="<?php echo (int)$staff['id']; ?>"
                                    data-first-name="<?php echo htmlspecialchars($staff['first_name']); ?>"
                                    data-last-name="<?php echo htmlspecialchars($staff['last_name']); ?>"
                                    data-username="<?php echo htmlspecialchars($staff['username'] ?? ''); ?>"
                                    data-email="<?php echo htmlspecialchars($staff['email']); ?>"
                                    data-phone="<?php echo htmlspecialchars($staff['phone'] ?? ''); ?>"
                                    data-position="<?php echo htmlspecialchars($staff['position'] ?? ''); ?>"
                                    data-department="<?php echo htmlspecialchars($staff['department'] ?? ''); ?>"
                                    data-role="<?php echo htmlspecialchars($role); ?>"
                                    data-status="<?php echo htmlspecialchars($status); ?>"
                                    data-hire-date="<?php echo htmlspecialchars($staff['hire_date'] ?? ''); ?>"
                                    title="View Details"><i class="feather icon-eye"></i></button>
                                <button class="btn btn-sm btn-icon btn-outline-warning edit-staff" 
                                    data-id="<?php echo (int)$staff['id']; ?>"
                                    title="Edit"><i class="feather icon-edit-2"></i></button>
                                <button class="btn btn-sm btn-icon btn-outline-danger delete-staff" 
                                    data-id="<?php echo (int)$staff['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>"
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
</div>
<!-- [ Layout content ] End -->

<!-- View Staff Modal -->
<div id="viewStaffModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Staff Details - <span id="viewStaffName"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeViewStaff()">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">First Name:</dt>
                    <dd class="col-sm-8" id="viewFirstName"></dd>
                    
                    <dt class="col-sm-4">Last Name:</dt>
                    <dd class="col-sm-8" id="viewLastName"></dd>
                    
                    <dt class="col-sm-4">Username:</dt>
                    <dd class="col-sm-8" id="viewUsername"></dd>
                    
                    <dt class="col-sm-4">Email:</dt>
                    <dd class="col-sm-8" id="viewEmail"></dd>
                    
                    <dt class="col-sm-4">Phone:</dt>
                    <dd class="col-sm-8" id="viewPhone"></dd>
                    
                    <dt class="col-sm-4">Position:</dt>
                    <dd class="col-sm-8" id="viewPosition"></dd>
                    
                    <dt class="col-sm-4">Department:</dt>
                    <dd class="col-sm-8" id="viewDepartment"></dd>
                    
                    <dt class="col-sm-4">Role:</dt>
                    <dd class="col-sm-8" id="viewRole"></dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8" id="viewStatus"></dd>
                    
                    <dt class="col-sm-4">Hire Date:</dt>
                    <dd class="col-sm-8" id="viewHireDate"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewStaff()">Close</button>
                <a href="#" id="editStaffLink" class="btn btn-primary">Edit</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
    /* Alert and Badge Styling for Better Readability */
    .alert-success {
        background-color: #f0fdf4 !important;
        color: #166534 !important;
        border-left: 4px solid #22c55e !important;
    }
    
    .alert-success strong,
    .alert-success div,
    .alert-success p {
        color: #166534 !important;
    }
    
    .alert-danger {
        background-color: #fef2f2 !important;
        color: #7f1d1d !important;
        border-left: 4px solid #ef4444 !important;
    }
    
    .alert-danger strong,
    .alert-danger div,
    .alert-danger p {
        color: #7f1d1d !important;
    }
    
    .alert-warning {
        background-color: #fffbeb !important;
        color: #78350f !important;
        border-left: 4px solid #f59e0b !important;
    }
    
    .alert-warning strong,
    .alert-warning div,
    .alert-warning p {
        color: #78350f !important;
    }
    
    /* Badge Styling */
    .badge {
        color: #fff !important;
        font-weight: 600;
    }
    
    .badge-success {
        background-color: #22c55e !important;
        color: #fff !important;
    }
    
    .badge-danger {
        background-color: #ef4444 !important;
        color: #fff !important;
    }
    
    .badge-primary {
        background-color: #2196f3 !important;
        color: #fff !important;
    }
</style>
<script>
$(document).ready(function() {
    // View Staff
    $('.view-staff').on('click', function() {
        const staffId = $(this).data('id');
        const firstName = $(this).data('first-name');
        const lastName = $(this).data('last-name');
        const username = $(this).data('username');
        const email = $(this).data('email');
        const phone = $(this).data('phone');
        const position = $(this).data('position');
        const department = $(this).data('department');
        const role = $(this).data('role');
        const status = $(this).data('status');
        const hireDate = $(this).data('hire-date');
        
        $('#viewStaffName').text(firstName + ' ' + lastName);
        $('#viewFirstName').text(firstName);
        $('#viewLastName').text(lastName);
        $('#viewUsername').text(username || 'N/A');
        $('#viewEmail').text(email);
        $('#viewPhone').text(phone || 'N/A');
        $('#viewPosition').text(position || 'N/A');
        $('#viewDepartment').text(department || 'N/A');
        $('#viewRole').html('<span style="background-color:#e3f2fd; color:#2196f3; padding:4px 10px; border-radius:4px; font-weight:600; font-size:11px;">' + role.charAt(0).toUpperCase() + role.slice(1) + '</span>');
        $('#viewStatus').html('<span style="background-color:' + (status === 'active' ? '#e8f5e9' : '#ffebee') + '; color:' + (status === 'active' ? '#4caf50' : '#f44336') + '; padding:4px 10px; border-radius:4px; font-weight:600; font-size:11px;">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>');
        $('#viewHireDate').text(hireDate ? new Date(hireDate).toLocaleDateString() : 'N/A');
        
        $('#editStaffLink').attr('href', 'staff_edit.php?id=' + staffId);
        
        $('#viewStaffModal').modal('show');
    });
    
    // Edit Staff
    $('.edit-staff').on('click', function() {
        const staffId = $(this).data('id');
        window.location.href = 'staff_edit.php?id=' + staffId;
    });
    
    // Delete Staff
    $('.delete-staff').on('click', function() {
        const staffId = $(this).data('id');
        const staffName = $(this).data('name');
        
        if (confirm('Are you sure you want to delete ' + staffName + '? This action cannot be undone.')) {
            window.location.href = 'handlers/staff_delete.php?id=' + staffId;
        }
    });
});

function closeViewStaff() {
    $('#viewStaffModal').modal('hide');
}
</script>

<?php include 'partials/footer.php'; ?>
