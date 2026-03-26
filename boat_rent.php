<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<?php
    // Initialize UI state and defaults
    $message = '';
    $message_type = '';

    // Load current hourly rate (fallback to 100.00)
    $current_rate = 100.00;
    $rate_stmt = $conn->prepare("SELECT hourly_rate FROM boat_rental_rates WHERE rate_type = 'standard' LIMIT 1");
    if ($rate_stmt) {
        $rate_stmt->execute();
        $rate_res = $rate_stmt->get_result();
        if ($rate_row = $rate_res->fetch_assoc()) {
            $current_rate = floatval($rate_row['hourly_rate']);
        }
        $rate_stmt->close();
    }

    // Pull one-time flash messages (used after create to avoid form resubmission)
    if (!empty($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $message_type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }

    // Load available boats for the form
    $available_boats = [];
    // detect rental_price column presence
    $has_rental_price = false;
    $col_check = $conn->query("SHOW COLUMNS FROM `boat_inventory` LIKE 'rental_price'");
    if ($col_check && $col_check->num_rows > 0) {
        $has_rental_price = true;
    }

    if ($has_rental_price) {
        $boats_stmt = $conn->prepare("SELECT id, boat_name, boat_type, capacity, rental_price, status FROM boat_inventory WHERE status = 'available' ORDER BY boat_name");
    } else {
        $boats_stmt = $conn->prepare("SELECT id, boat_name, boat_type, capacity, status FROM boat_inventory WHERE status = 'available' ORDER BY boat_name");
    }
    if ($boats_stmt) {
        $boats_stmt->execute();
        $boats_res = $boats_stmt->get_result();
        while ($b = $boats_res->fetch_assoc()) {
            $available_boats[] = $b;
        }
        $boats_stmt->close();
    }

    // Handle new rental booking
    if (isset($_POST['action']) && $_POST['action'] === 'create_rental') {
        $boat_id = intval($_POST['boat_id'] ?? 0);
        $rental_start = $_POST['rental_start'] ?? '';
        $hours = floatval($_POST['hours'] ?? 0);
        $num_people = intval($_POST['num_people'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($boat_id < 1 || empty($rental_start) || $hours < 0.5 || $num_people < 1) {
            $message = '❌ Please fill in all required fields (minimum 0.5 hours, at least 1 person)';
            $message_type = 'error';
        } else {
            // Get boat name and capacity
            $boat_check = $conn->prepare("SELECT boat_name, capacity" . ($has_rental_price ? ", rental_price" : "") . " FROM boat_inventory WHERE id = ?");
            $boat_check->bind_param('i', $boat_id);
            $boat_check->execute();
            $boat_data = $boat_check->get_result()->fetch_assoc();
            $boat_check->close();

            if (!$boat_data) {
                $message = '❌ Boat not found';
                $message_type = 'error';
            } elseif ($num_people > intval($boat_data['capacity'])) {
                $message = '❌ Number of people exceeds boat capacity (' . intval($boat_data['capacity']) . ')';
                $message_type = 'error';
            } else {
                $boat_name = $boat_data['boat_name'];
                $boat_rate = floatval($boat_data['rental_price'] ?? 0);
                $hourly_rate_used = ($boat_rate > 0) ? $boat_rate : $current_rate;
                $rental_start_dt = new DateTime($rental_start);
                $rental_end_dt = clone $rental_start_dt;
                $rental_end_dt->add(new DateInterval('PT' . intval($hours) . 'H' . intval(($hours - intval($hours)) * 60) . 'M'));

                $rental_end = $rental_end_dt->format('Y-m-d H:i:s');
                $total_amount = $num_people * $hours * $hourly_rate_used;

                // Insert rental (no customer_id) and record num_people
                $customer_id = null;
                $rental_stmt = $conn->prepare("INSERT INTO boat_rentals (customer_id, boat_name, rental_start, rental_end, hours_rented, hourly_rate, total_amount, num_people, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
                if ($rental_stmt) {
                    $rental_stmt->bind_param('isssdddis', $customer_id, $boat_name, $rental_start, $rental_end, $hours, $hourly_rate_used, $total_amount, $num_people, $notes);

                    if ($rental_stmt->execute()) {
                        // Update boat status
                        $update_boat = $conn->prepare("UPDATE boat_inventory SET status = 'rented' WHERE id = ?");
                        $update_boat->bind_param('i', $boat_id);
                        $update_boat->execute();
                        $update_boat->close();

                        // Log activity
                        require 'handlers/activity_logger.php';
                        logActivity(
                            $conn,
                            $_SESSION['user_id'],
                            $_SESSION['role'],
                            'CREATE',
                            'boat_rentals',
                            $conn->insert_id,
                            'Boat Rental Creation',
                            "Boat rental for boat: {$boat_name}, {$hours} hours, {$num_people} people, total: ₱" . number_format($total_amount, 2),
                            null,
                            ['boat_name' => $boat_name, 'hours' => $hours, 'num_people' => $num_people, 'rate' => $current_rate, 'total' => $total_amount]
                        );

                        // Set a session flash and redirect client-side to avoid POST resubmission
                        $_SESSION['flash_message'] = '✅ Boat rental created successfully!';
                        $_SESSION['flash_type'] = 'success';
                        echo "<script>window.location = 'boat_rent.php';</script>";
                        exit;
                    } else {
                        $message = '❌ Failed to create boat rental: ' . $conn->error;
                        $message_type = 'error';
                    }
                    $rental_stmt->close();
                } else {
                    $message = '❌ Database error: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        }
    }

// Get all rentals (pending, active, completed) in one DataTable
$rentals = [];
$rentals_stmt = $conn->prepare("SELECT br.id, br.customer_id, c.first_name, c.last_name, br.boat_name, br.rental_start, br.rental_end, br.hours_rented, br.hourly_rate, br.total_amount, br.num_people, br.status, br.notes FROM boat_rentals br LEFT JOIN customers c ON br.customer_id = c.id ORDER BY br.rental_start DESC LIMIT 100");
if ($rentals_stmt) {
    $rentals_stmt->execute();
    $rentals_res = $rentals_stmt->get_result();
    while ($rental = $rentals_res->fetch_assoc()) {
        $rentals[] = $rental;
    }
    $rentals_stmt->close();
}


// Get all customers for dropdown (limit to boat renters or all)
$customers = [];
$cust_stmt = $conn->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as full_name, phone FROM customers ORDER BY first_name LIMIT 100");
if ($cust_stmt) {
    $cust_stmt->execute();
    $cust_res = $cust_stmt->get_result();
    while ($cust = $cust_res->fetch_assoc()) {
        $customers[] = $cust;
    }
    $cust_stmt->close();
}
?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="font-weight-bold py-3 mb-0">Boat Rentals</h4>
            <div>
                <a href="boat_management.php" class="btn btn-sm btn-success">Manage Boats</a>
            </div>
        </div>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item"><a href="#">Reservation</a></li>
                <li class="breadcrumb-item active">Boat Rent</li>
            </ol>
        </div>

        <?php if ($message): ?>
                <?php
                    // Use accessible alert colors (Bootstrap-like) with good contrast
                    if ($message_type === 'success') {
                        $alert_style = 'background-color:#d4edda;color:#155724;border:none;';
                        $icon = 'check-circle';
                    } else {
                        $alert_style = 'background-color:#f8d7da;color:#721c24;border:none;';
                        $icon = 'alert-circle';
                    }
                ?>
                <div class="alert alert-<?php echo ($message_type === 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert" style="<?php echo $alert_style; ?>">
                    <i class="feather icon-<?php echo $icon; ?>"></i>
                    <span style="margin-left:8px;"><?php echo htmlspecialchars($message); ?></span>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color:inherit;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
        <?php endif; ?>

        <!-- Admin Rate Control (Visible to admin only) -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-header-title">⚙️ Rental Rate Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" style="display: flex; gap: 10px; align-items: flex-end;">
                    <input type="hidden" name="action" value="update_rate">
                    <div class="form-group mb-0" style="flex: 1; max-width: 300px;">
                        <label for="hourlyRate"><strong>Hourly Rate (₱)</strong></label>
                        <input type="number" class="form-control" id="hourlyRate" name="hourly_rate" value="<?php echo number_format($current_rate, 2); ?>" step="0.01" min="1" required>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="feather icon-edit-2"></i> Update Rate
                    </button>
                </form>
                <small class="text-muted mt-2 d-block">Current Rate: <strong>₱<?php echo number_format($current_rate, 2); ?> per hour</strong></small>
            </div>
        </div>
        <?php endif; ?>

        <!-- New Boat Rental Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-header-title">➕ Create New Boat Rental</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create_rental">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="numPeople"><strong>Number of People</strong></label>
                            <input type="number" class="form-control" id="numPeople" name="num_people" min="1" max="50" placeholder="How many people will board?" required>
                            <small class="form-text text-muted">Enter number of heads to rent for this boat.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="boatId"><strong>Boat</strong></label>
                            <select class="form-control" id="boatId" name="boat_id" required>
                                <option value="">-- Select Boat --</option>
                                <?php foreach ($available_boats as $boat): ?>
                                                                                    <option value="<?php echo $boat['id']; ?>" 
                                                                                        data-boat-name="<?php echo htmlspecialchars($boat['boat_name']); ?>"
                                                                                        data-boat-type="<?php echo htmlspecialchars($boat['boat_type']); ?>"
                                                                                        data-capacity="<?php echo $boat['capacity']; ?>"
                                                                                        data-hourly-rate="<?php echo number_format(floatval($boat['rental_price'] ?? ($boat['rental_price'] ?? 0)), 2, '.', ''); ?>"
                                                                                        data-status="<?php echo $boat['status']; ?>">
                                                                <?php echo htmlspecialchars($boat['boat_name']); ?> (<?php echo htmlspecialchars($boat['boat_type']); ?>, capacity: <?php echo $boat['capacity']; ?>)
                                                            </option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Boat Details Display -->
                            <div id="boatDetails" class="alert mt-3" style="display: none; background-color:#f8f9fa;color:#212529;border:none;">
                                <small style="font-weight: 600;">📋 Boat Details:</small>
                                <div style="margin-top: 8px; font-size: 14px;">
                                    <p class="mb-2"><strong>Boat:</strong> <span id="boatNameDisplay"></span></p>
                                    <p class="mb-2"><strong>Type:</strong> <span id="boatTypeDisplay"></span></p>
                                    <p class="mb-2"><strong>Capacity:</strong> <span id="boatCapacityDisplay"></span> passengers</p>
                                    <p class="mb-0"><strong>Status:</strong> <span id="boatStatusDisplay" class="badge"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="rentalStart"><strong>Rental Start Date/Time</strong></label>
                            <input type="datetime-local" class="form-control" id="rentalStart" name="rental_start" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="hours"><strong>Hours to Rent</strong></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="hours" name="hours" placeholder="e.g., 2.5" step="0.5" min="0.5" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">hrs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes"><strong>Notes (Optional)</strong></label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Additional notes or special requests..."></textarea>
                    </div>
                    <div class="alert" style="background-color:#d1ecf1;color:#0c5460;border:none; display:flex; gap:12px; align-items:center;">
                        <div>
                            <strong>Estimated Total:</strong>
                            <div style="font-size:1.25rem; font-weight:700; margin-top:4px;"><span id="estimatedTotal">₱<?php echo number_format(0, 2); ?></span></div>
                            <small style="color:inherit;">(at ₱<span id="ratePerHour"><?php echo number_format($current_rate, 2); ?></span>/hour)</small>
                        </div>
                        <div style="border-left:1px solid rgba(0,0,0,0.06); padding-left:12px;">
                            <strong>Per Person:</strong>
                            <div style="font-size:1.1rem; font-weight:600; margin-top:4px;"><span id="perHeadEstimate">₱0.00</span></div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="feather icon-save"></i> Create Boat Rental
                    </button>
                </form>
            </div>
        </div>

        <!-- All Boat Rentals Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-header-title">📋 All Boat Rentals (Pending, Active, Completed, Cancelled)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($rentals)): ?>
                <div class="table-responsive">
                    <table id="boatRentalsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>

                                <th>Number of Person</th>
                                <th>Boat</th>
                                <th>Start Date/Time</th>
                                <th>Hours</th>
                                <th>Rate/Hour</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentals as $rental): ?>
                            <tr>
                                <td><?php echo $rental['id']; ?></td>
                              
                                <td><?php echo intval($rental['num_people'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($rental['boat_name']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($rental['rental_start'])); ?></td>
                                <td><?php echo number_format($rental['hours_rented'], 2); ?></td>
                                <td>₱<?php echo number_format($rental['hourly_rate'], 2); ?></td>
                                <td><strong>₱<?php echo number_format($rental['total_amount'], 2); ?></strong></td>
                                <td>
                                    <?php
                                    $statusClass = 'secondary';
                                    switch (strtolower($rental['status'])) {
                                        case 'active': $statusClass = 'success'; break;
                                        case 'pending': $statusClass = 'warning'; break;
                                        case 'completed': $statusClass = 'info'; break;
                                        case 'cancelled': $statusClass = 'danger'; break;
                                    }
                                    ?>
                                    <span class="badge badge-<?php echo $statusClass; ?>"><?php echo ucfirst($rental['status']); ?></span>
                                </td>
                                <td>
                                    <?php if (in_array(strtolower($rental['status']), ['pending', 'active'])): ?>
                                        <button class="btn btn-sm btn-icon btn-outline-success btn-done-rental" data-rental-id="<?php echo $rental['id']; ?>" title="Mark as Done - Complete Rental">
                                            <i class="feather icon-check-circle"></i>
                                        </button>
                                        <button class="btn btn-sm btn-icon btn-outline-danger btn-cancel-rental" data-rental-id="<?php echo $rental['id']; ?>" title="Cancel Rental">
                                            <i class="feather icon-x"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-icon btn-outline-dark btn-delete-rental" data-rental-id="<?php echo $rental['id']; ?>" title="Delete Rental">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-4">
                    <p>No active or pending boat rentals at this time.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<?php include 'partials/footer.php'; ?>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    if (document.getElementById('boatRentalsTable')) {
        $('#boatRentalsTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
            "columnDefs": [{ "orderable": false, "targets": -1 }]
        });
    }
});
</script>

<script>
// Handle boat selection and display boat details
const boatSelect = document.getElementById('boatId');
const boatDetails = document.getElementById('boatDetails');

boatSelect.addEventListener('change', function() {
    const selectedOption = boatSelect.options[boatSelect.selectedIndex];
    
    if (selectedOption.value === '') {
        // Hide details when no boat selected
        boatDetails.style.display = 'none';
    } else {
        // Display boat details
        document.getElementById('boatNameDisplay').textContent = selectedOption.getAttribute('data-boat-name');
        document.getElementById('boatTypeDisplay').textContent = selectedOption.getAttribute('data-boat-type');
        document.getElementById('boatCapacityDisplay').textContent = selectedOption.getAttribute('data-capacity');
        // update selected rate display
        const hourlyRate = parseFloat(selectedOption.getAttribute('data-hourly-rate')) || currentRate;
        document.getElementById('ratePerHour').textContent = hourlyRate.toFixed(2);
        
        const status = selectedOption.getAttribute('data-status');
        const statusBadge = document.getElementById('boatStatusDisplay');
        statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        statusBadge.className = 'badge badge-' + (status === 'available' ? 'success' : 'warning');
        
        boatDetails.style.display = 'block';
        boatDetails.style.display = 'block';
    }
    // recalc estimates when changing selection
    updateEstimate();
});

// Calculate estimated total on hours change
const hoursInput = document.getElementById('hours');
const estimatedTotal = document.getElementById('estimatedTotal');
const perHeadEstimate = document.getElementById('perHeadEstimate');
const currentRate = <?php echo $current_rate; ?>;
let selectedBoatRate = currentRate;
const numPeopleInput = document.getElementById('numPeople');

function updateEstimate() {
    const hours = parseFloat(hoursInput.value) || 0;
    const numPeople = parseInt(numPeopleInput.value) || 0;
    const rateToUse = selectedBoatRate || currentRate;
    const total = numPeople * hours * rateToUse;
    const perHead = (numPeople > 0) ? (hours * rateToUse) : 0;
    estimatedTotal.textContent = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    perHeadEstimate.textContent = '₱' + perHead.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

hoursInput.addEventListener('input', updateEstimate);
hoursInput.addEventListener('change', updateEstimate);
numPeopleInput.addEventListener('input', updateEstimate);
numPeopleInput.addEventListener('change', updateEstimate);

// Update selectedBoatRate when boat selection changes
boatSelect.addEventListener('change', function() {
    const opt = boatSelect.options[boatSelect.selectedIndex];
    selectedBoatRate = parseFloat(opt.getAttribute('data-hourly-rate')) || currentRate;
    document.getElementById('ratePerHour').textContent = selectedBoatRate.toFixed(2);
    updateEstimate();
});

// Set minimum datetime to current time
const now = new Date();
now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
document.getElementById('rentalStart').min = now.toISOString().slice(0, 16);
</script>
<script>
// AJAX mark as done rental handler
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-done-rental');
    if (!btn) return;
    const rentalId = btn.getAttribute('data-rental-id');
    if (!rentalId) return;
    if (!confirm('Mark this boat rental as completed? The amount will be recorded in the dashboard revenue.')) return;

    fetch('handlers/boat_rental_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'complete_rental', rental_id: rentalId })
    }).then(r => r.json()).then(j => {
        if (j && j.success) {
            // reload to reflect changes
            window.location.reload();
        } else {
            alert('Failed to complete rental: ' + (j.message || 'Unknown error'));
        }
    }).catch(err => {
        alert('Request failed: ' + err.message);
    });
});
</script>
<script>
// AJAX cancel rental handler
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-cancel-rental');
    if (!btn) return;
    const rentalId = btn.getAttribute('data-rental-id');
    if (!rentalId) return;
    if (!confirm('Are you sure you want to cancel this rental?')) return;

    fetch('handlers/boat_rental_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'cancel_rental', rental_id: rentalId })
    }).then(r => r.json()).then(j => {
        if (j && j.success) {
            window.location.reload();
        } else {
            alert('Failed to cancel rental: ' + (j.message || 'Unknown error'));
        }
    }).catch(err => {
        alert('Request failed: ' + err.message);
    });
});
</script>
<script>
// AJAX delete rental handler
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-rental');
    if (!btn) return;
    const rentalId = btn.getAttribute('data-rental-id');
    if (!rentalId) return;
    if (!confirm('This will permanently delete the rental record. Continue?')) return;

    fetch('handlers/boat_rental_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'delete_rental', rental_id: rentalId })
    }).then(r => r.json()).then(j => {
        if (j && j.success) {
            window.location.reload();
        } else {
            alert('Failed to delete rental: ' + (j.message || 'Unknown error'));
        }
    }).catch(err => {
        alert('Request failed: ' + err.message);
    });
});
</script>
