<?php include 'auth_admin.php'; ?>
<?php include 'config/db.php'; ?>
<?php 
// Check if availability_tables exists
$tableExists = true;
$result = $conn->query("SHOW TABLES LIKE 'availability_tables'");
if (!$result || $result->num_rows === 0) {
    $tableExists = false;
}
?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <?php if (!$tableExists): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert" style="background: #fff3cd; color: #856404; border-color: #ffc107;">
                <i class="feather icon-alert-triangle"></i> <strong>Database Not Set Up!</strong> 
                The availability system needs to be initialized first.
                <a href="db_setup_now.php" class="btn btn-warning btn-sm" style="margin-left: 10px;">Run Setup Now</a>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="background: #d4edda; color: #155724; border-color: #c3e6cb;">
                <i class="feather icon-check-circle"></i> <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                <i class="feather icon-alert-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold py-3 mb-0">Available Tables</h4>
            <a href="availability_set.php" class="btn btn-primary">
                <i class="feather icon-plus"></i> Add New Table
            </a>
        </div>

        <?php if ($tableExists): ?>
        <!-- Tables List -->
        <div class="card">
            <div class="card-header with-elements">
                <h5 class="card-header-title">Dining Tables</h5>
                <div class="card-header-elements ml-4 mb-2">
                    <?php 
                    // Get table count
                    $count_result = $conn->query("SELECT COUNT(*) as total FROM availability_tables WHERE status = 'active'");
                    $count_row = $count_result->fetch_assoc();
                    echo '<span class="badge badge-primary">' . $count_row['total'] . ' Active Tables</span>';
                    ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Table Name</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all tables
                        $query = "SELECT * FROM availability_tables ORDER BY created_at DESC";
                        $result = $conn->query($query);

                        if ($result && $result->num_rows > 0):
                            while ($table = $result->fetch_assoc()):
                                $status = $table['status'];
                                $status_color = $status === 'available' ? 'success' : 'danger';
                                $status_icon = $status === 'available' ? 'check-circle' : 'x-circle';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($table['table_name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $table['capacity']; ?> guests</span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $status_color; ?>">
                                    <i class="feather icon-<?php echo $status_icon; ?>" style="font-size: 12px;"></i>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($table['notes'] ?: '-'); ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($table['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editModal<?php echo $table['id']; ?>" title="Edit">
                                        <i class="feather icon-edit-2"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-table-btn" data-table-id="<?php echo $table['id']; ?>" data-table-name="<?php echo htmlspecialchars($table['table_name']); ?>" title="Delete">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $table['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $table['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel<?php echo $table['id']; ?>">Edit Table: <?php echo htmlspecialchars($table['table_name']); ?></h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <form method="POST" action="handlers/availability_update.php">
                                        <div class="modal-body">
                                            <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                                            
                                            <div class="form-group">
                                                <label>Table Name</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($table['table_name']); ?>" disabled>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Capacity</label>
                                                    <input type="number" class="form-control" name="capacity" value="<?php echo $table['capacity']; ?>" min="1" max="100" required>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Status</label>
                                                    <select class="form-control" name="status" required>
                                            <option value="available" <?php echo $table['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="not available" <?php echo $table['status'] === 'not available' ? 'selected' : ''; ?>>Not Available</option>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No tables created yet. <a href="availability_set.php">Create one now</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <!-- Table doesn't exist - show setup instructions -->
        <div style="max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 30px;">
                <i class="feather icon-alert-circle" style="font-size: 48px; color: #ffc107; margin-bottom: 20px;"></i>
                <h2 style="color: #27ae60; margin-bottom: 10px;">No Tables Yet</h2>
                <p style="color: #666; margin-bottom: 20px;">The availability system hasn't been initialized yet. Please set it up first.</p>
                
                <a href="db_setup_now.php" class="btn btn-primary" style="padding: 12px 24px; font-size: 16px; margin-bottom: 20px;">
                    <i class="feather icon-play"></i> Initialize System Now
                </a>
                
                <div style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; border-radius: 5px; text-align: left; margin-top: 20px;">
                    <strong>What will happen:</strong>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #0c5460;">
                        <li>Creates the <code>availability_tables</code> database table</li>
                        <li>Adds <code>table_id</code> column to reservations</li>
                        <li>Enables table availability features</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<script>
    $(document).ready(function() {
        // Delete table button
        $(document).on('click', '.delete-table-btn', function() {
            var tableId = $(this).data('table-id');
            var tableName = $(this).data('table-name');
            
            if (confirm('Are you sure you want to delete the table "' + tableName + '"?')) {
                window.location.href = 'handlers/availability_delete.php?id=' + tableId;
            }
        });
    });
</script>

<?php include 'partials/footer.php'; ?>
