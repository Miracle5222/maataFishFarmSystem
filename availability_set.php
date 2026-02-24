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

        <?php if ($tableExists): ?>
        <div class="row mb-4">
            <div class="col-md-8">
                <h4 class="font-weight-bold py-3 mb-4">Create Table Availability</h4>
                
                <!-- Create Table Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-header-title">Add New Table</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="handlers/availability_set_handler.php">
                            <div class="form-group">
                                <label for="table_name" class="form-label font-weight-bold">Table Name</label>
                                <input type="text" class="form-control" id="table_name" name="table_name" placeholder="e.g., Table 1, VIP Table, Counter" required>
                                <small class="form-text text-muted">Enter a descriptive name for the table</small>
                            </div>

                            <div class="form-group">
                                <label for="capacity" class="form-label font-weight-bold">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" placeholder="e.g., 4, 6, 8" min="1" max="100" required>
                                <small class="form-text text-muted">Maximum number of guests this table can accommodate</small>
                            </div>

                            <div class="form-group">
                                <label for="notes" class="form-label font-weight-bold">Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any special notes or details about this table"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="feather icon-plus"></i> Create Table
                                </button>
                                <a href="availability_check.php" class="btn btn-secondary ml-2">View Tables</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title font-weight-bold mb-3">
                            <i class="feather icon-info"></i> How It Works
                        </h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Step 1:</strong> Create a table with a name and capacity
                            </li>
                            <li class="mb-2">
                                <strong>Step 2:</strong> Set availability times for the table
                            </li>
                            <li class="mb-2">
                                <strong>Step 3:</strong> Customers can book available tables
                            </li>
                        </ul>
                        <hr>
                        <p class="text-muted mb-0 small">
                            <strong>Tip:</strong> Give tables clear names and set accurate capacity limits to help customers find the right table for their needs.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Table doesn't exist - show setup instructions -->
        <div style="max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 30px;">
                <i class="feather icon-alert-circle" style="font-size: 48px; color: #ffc107; margin-bottom: 20px;"></i>
                <h2 style="color: #27ae60; margin-bottom: 10px;">System Not Initialized</h2>
                <p style="color: #666; margin-bottom: 20px;">The availability system needs to be set up before you can create tables.</p>
                
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

<?php include 'partials/footer.php'; ?>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<?php include 'partials/footer.php'; ?>
