<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold py-3 mb-0">Availability — View & Manage</h4>
            <a href="availability_set.php" class="btn btn-primary">
                <i class="feather icon-plus"></i> Set New Availability
            </a>
        </div>
        
        <?php
        require __DIR__ . '/config/db.php';
        
        // Get quick stats
        $stats_stmt = $conn->prepare('
            SELECT 
                COUNT(*) as total_slots,
                SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as open_slots,
                SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as closed_slots,
                SUM(max_capacity) as total_capacity,
                SUM(current_reservations) as total_reservations
            FROM availability 
            WHERE available_date >= CURDATE()
        ');
        
        $stats = [];
        if ($stats_stmt) {
            $stats_stmt->execute();
            $stats_res = $stats_stmt->get_result();
            $stats = $stats_res->fetch_assoc() ?? [];
            $stats_stmt->close();
        }
        
        // Fetch all availability records
        $availability = [];
        $avail_stmt = $conn->prepare('
            SELECT id, available_date, available_time_start, available_time_end, 
                   max_capacity, current_reservations, is_available, notes, created_at
            FROM availability 
            WHERE available_date >= CURDATE()
            ORDER BY available_date ASC, available_time_start ASC
        ');
        
        if ($avail_stmt) {
            $avail_stmt->execute();
            $avail_res = $avail_stmt->get_result();
            while ($row = $avail_res->fetch_assoc()) {
                $availability[] = $row;
            }
            $avail_stmt->close();
        }
        ?>
        
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Total Slots</h6>
                                <h3 class="font-weight-bold mb-0"><?php echo $stats['total_slots'] ?? 0; ?></h3>
                            </div>
                            <div style="font-size: 40px; color: #007bff; opacity: 0.3;">
                                <i class="feather icon-calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Open Slots</h6>
                                <h3 class="font-weight-bold mb-0" style="color: #28a745;"><?php echo $stats['open_slots'] ?? 0; ?></h3>
                            </div>
                            <div style="font-size: 40px; color: #28a745; opacity: 0.3;">
                                <i class="feather icon-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Closed Slots</h6>
                                <h3 class="font-weight-bold mb-0" style="color: #dc3545;"><?php echo $stats['closed_slots'] ?? 0; ?></h3>
                            </div>
                            <div style="font-size: 40px; color: #dc3545; opacity: 0.3;">
                                <i class="feather icon-x-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Total Reservations</h6>
                                <h3 class="font-weight-bold mb-0" style="color: #ffc107;"><?php echo $stats['total_reservations'] ?? 0; ?></h3>
                            </div>
                            <div style="font-size: 40px; color: #ffc107; opacity: 0.3;">
                                <i class="feather icon-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Availability Table -->
        <div class="card">
            <div class="card-header with-elements">
                <h5 class="card-header-title">Available Time Slots</h5>
                <div class="card-header-elements ml-4 mb-2">
                    <span class="badge badge-primary"><?php echo count($availability); ?> Slots</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Capacity</th>
                            <th>Reservations</th>
                            <th>Availability</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($availability)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No availability slots set. <a href="availability_set.php">Create one now</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($availability as $slot): 
                                $date = new DateTime($slot['available_date']);
                                $remaining = $slot['max_capacity'] - $slot['current_reservations'];
                                $usage_percent = ($slot['current_reservations'] / $slot['max_capacity']) * 100;
                                $status_text = $slot['is_available'] ? 'Open' : 'Closed';
                                $status_color = $slot['is_available'] ? '#28a745' : '#dc3545';
                                $status_bg = $slot['is_available'] ? '#f0fdf4' : '#fef2f2';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo $date->format('M d, Y'); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo $date->format('l'); ?></small>
                                </td>
                                <td>
                                    <span class="font-weight-bold">
                                        <?php echo date('H:i', strtotime($slot['available_time_start'])); ?> 
                                        <i class="feather icon-arrow-right" style="font-size: 12px;"></i> 
                                        <?php echo date('H:i', strtotime($slot['available_time_end'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo $slot['max_capacity']; ?> guests</span>
                                </td>
                                <td>
                                    <div style="min-width: 200px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="font-weight-bold"><?php echo $slot['current_reservations']; ?> / <?php echo $slot['max_capacity']; ?></small>
                                            <small class="text-muted"><?php echo round($usage_percent); ?>%</small>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $usage_percent; ?>%; background-color: <?php echo $usage_percent > 75 ? '#dc3545' : ($usage_percent > 50 ? '#ffc107' : '#28a745'); ?>;" aria-valuenow="<?php echo $usage_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $remaining; ?> spots left</small>
                                    </div>
                                </td>
                                <td>
                                    <span style="background-color: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; display: inline-block; border: 1px solid <?php echo $status_color; ?>;">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($slot['notes'] ?? '-'); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editModal<?php echo $slot['id']; ?>">
                                            <i class="feather icon-edit-2"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('Delete this availability slot?')) { window.location='handlers/availability_delete.php?id=<?php echo $slot['id']; ?>'; }">
                                            <i class="feather icon-trash-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $slot['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $slot['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?php echo $slot['id']; ?>">Edit Availability</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form method="POST" action="handlers/availability_update.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $slot['id']; ?>">
                                                
                                                <div class="form-group">
                                                    <label>Date</label>
                                                    <input type="text" class="form-control" value="<?php echo date('M d, Y', strtotime($slot['available_date'])); ?>" disabled>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Time Range</label>
                                                    <input type="text" class="form-control" value="<?php echo date('H:i', strtotime($slot['available_time_start'])); ?> - <?php echo date('H:i', strtotime($slot['available_time_end'])); ?>" disabled>
                                                </div>
                                                
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label>Max Capacity</label>
                                                        <input type="number" class="form-control" name="max_capacity" value="<?php echo $slot['max_capacity']; ?>" min="1" required>
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <label>Status</label>
                                                        <select class="form-control" name="is_available" required>
                                                            <option value="1" <?php echo $slot['is_available'] ? 'selected' : ''; ?>>Available</option>
                                                            <option value="0" <?php echo !$slot['is_available'] ? 'selected' : ''; ?>>Closed</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Notes</label>
                                                    <textarea class="form-control" name="notes" rows="2"><?php echo htmlspecialchars($slot['notes'] ?? ''); ?></textarea>
                                                </div>
                                                
                                                <?php if ($slot['current_reservations'] > 0): ?>
                                                    <div class="alert alert-info mb-0">
                                                        <strong>Info:</strong> This slot has <?php echo $slot['current_reservations']; ?> reservation(s)
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<style>
    .progress {
        background-color: #e9ecef;
        border-radius: 3px;
    }
    
    .progress-bar {
        transition: width 0.3s ease;
    }
    
    table tbody tr {
        border-bottom: 1px solid #f0f0f0;
    }
    
    table tbody tr:hover {
        background-color: #f9f9f9;
    }
</style>

<?php include 'partials/footer.php'; ?>
