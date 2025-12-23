<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Availability — Set Table Availability</h4>
        
        <?php
        require __DIR__ . '/config/db.php';
        
        $success = '';
        $error = '';
        
        if (isset($_GET['message'])) {
            $success = htmlspecialchars($_GET['message']);
        }
        if (isset($_GET['error'])) {
            $error = htmlspecialchars($_GET['error']);
        }
        
        // Fetch available dates (next 30 days)
        $available_dates = [];
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $available_dates[$date] = date('M d, Y (D)', strtotime($date));
        }
        ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert" id="successAlert">
                <div class="d-flex align-items-center">
                    <i class="feather icon-check-circle text-success mr-3" style="font-size: 24px;"></i>
                    <div>
                        <strong>Success!</strong>
                        <div><?php echo $success; ?></div>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert" id="errorAlert">
                <div class="d-flex align-items-center">
                    <i class="feather icon-alert-circle text-danger mr-3" style="font-size: 24px;"></i>
                    <div>
                        <strong>Error!</strong>
                        <div><?php echo $error; ?></div>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mt-3">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Set Table Availability</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="handlers/availability_set_handler.php" id="availabilityForm">
                            <div class="form-group">
                                <label class="form-label">Available Date <span class="text-danger">*</span></label>
                                <select class="form-control" name="available_date" required>
                                    <option value="">-- Select Date --</option>
                                    <?php foreach ($available_dates as $date => $label): ?>
                                        <option value="<?php echo $date; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Available Time From <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="available_time_start" value="10:00" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Available Time To <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="available_time_end" value="20:00" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Maximum Capacity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="max_capacity" value="50" min="1" max="500" required>
                                    <small class="form-text text-muted">Maximum number of guests allowed</small>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-control" name="is_available" required>
                                        <option value="1" selected>Available</option>
                                        <option value="0">Closed/Not Available</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="Add any special notes (optional)"></textarea>
                                <small class="form-text text-muted">e.g., Peak hours, private events scheduled, etc.</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="feather icon-save"></i> Set Availability
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="feather icon-refresh-cw"></i> Clear
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mt-3">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Quick Settings</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="font-weight-bold mb-3">Set Multiple Days</h6>
                        <form method="POST" action="handlers/availability_bulk_handler.php">
                            <div class="form-group">
                                <label class="form-label">Select Days to Apply</label>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="selectWeekdays" name="select_weekdays" value="1">
                                    <label class="custom-control-label" for="selectWeekdays">All Weekdays (Mon-Fri)</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="selectWeekends" name="select_weekends" value="1">
                                    <label class="custom-control-label" for="selectWeekends">All Weekends (Sat-Sun)</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="selectAll" name="select_all" value="1">
                                    <label class="custom-control-label" for="selectAll">All Days (Next 30 Days)</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Time From</label>
                                <input type="time" class="form-control" name="bulk_time_start" value="10:00">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Time To</label>
                                <input type="time" class="form-control" name="bulk_time_end" value="20:00">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Max Capacity</label>
                                <input type="number" class="form-control" name="bulk_capacity" value="50" min="1">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-info btn-block">
                                    <i class="feather icon-settings"></i> Apply to Selected Days
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><small>
                            <strong>Note:</strong> Set the dates and times when your venue is available for reservations. You can update or add multiple dates quickly using the bulk settings.
                        </small></p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="feather icon-check text-success"></i> <strong>Available:</strong> Customers can book on this date</li>
                            <li class="mb-2"><i class="feather icon-x text-danger"></i> <strong>Closed:</strong> No bookings allowed</li>
                            <li class="mb-2"><i class="feather icon-info text-info"></i> <strong>Max Capacity:</strong> Limit bookings by guest count</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Availabilities -->
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Recently Set Availabilities</h5>
                        <a href="availability_check.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Max Capacity</th>
                                    <th>Current Reservations</th>
                                    <th>Remaining Capacity</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent = [];
                                $recent_stmt = $conn->prepare('SELECT id, available_date, available_time_start, available_time_end, max_capacity, current_reservations, is_available, notes FROM availability WHERE available_date >= CURDATE() ORDER BY available_date ASC LIMIT 10');
                                if ($recent_stmt) {
                                    $recent_stmt->execute();
                                    $recent_res = $recent_stmt->get_result();
                                    while ($row = $recent_res->fetch_assoc()) {
                                        $recent[] = $row;
                                    }
                                    $recent_stmt->close();
                                }
                                
                                if (empty($recent)) {
                                    echo '<tr><td colspan="7" class="text-center text-muted py-4">No availabilities set yet. <a href="#availabilityForm">Set one now</a></td></tr>';
                                } else {
                                    foreach ($recent as $avail):
                                        $remaining = $avail['max_capacity'] - $avail['current_reservations'];
                                        $status_text = $avail['is_available'] ? 'Available' : 'Closed';
                                        $status_color = $avail['is_available'] ? '#4caf50' : '#f44336';
                                ?>
                                <tr>
                                    <td><strong><?php echo date('M d, Y', strtotime($avail['available_date'])); ?></strong></td>
                                    <td><?php echo date('H:i', strtotime($avail['available_time_start'])); ?> - <?php echo date('H:i', strtotime($avail['available_time_end'])); ?></td>
                                    <td><?php echo $avail['max_capacity']; ?></td>
                                    <td><?php echo $avail['current_reservations']; ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo max(0, $remaining); ?></span>
                                    </td>
                                    <td>
                                        <span style="background-color: <?php echo $status_color; ?>; color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($avail['notes'] ?? '-'); ?></small></td>
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
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<style>
    .alert {
        border-radius: 6px;
        border: none;
        font-size: 14px;
    }
    
    .alert-success {
        background-color: #f0fdf4 !important;
        color: #166534 !important;
        border-left: 4px solid #22c55e !important;
    }
    
    .alert-danger {
        background-color: #fef2f2 !important;
        color: #7f1d1d !important;
        border-left: 4px solid #ef4444 !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(successAlert);
            bsAlert.close();
        }, 5000);
    }
    
    // Form validation
    document.getElementById('availabilityForm').addEventListener('submit', function(e) {
        const startTime = document.querySelector('input[name="available_time_start"]').value;
        const endTime = document.querySelector('input[name="available_time_end"]').value;
        
        if (startTime >= endTime) {
            e.preventDefault();
            alert('End time must be after start time');
            return false;
        }
    });
});
</script>

<?php include 'partials/footer.php'; ?>
