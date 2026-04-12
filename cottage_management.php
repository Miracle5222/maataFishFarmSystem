<?php
include 'config/db.php';
include 'auth_admin.php';
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Check and update cottages table schema if needed
$result = $conn->query("SHOW COLUMNS FROM cottages LIKE 'available_date'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE cottages ADD COLUMN available_date DATE NOT NULL DEFAULT '2026-01-22'");
}

$result = $conn->query("SHOW COLUMNS FROM cottages LIKE 'available_time_start'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE cottages ADD COLUMN available_time_start TIME NOT NULL DEFAULT '09:00:00'");
}

$result = $conn->query("SHOW COLUMNS FROM cottages LIKE 'available_time_end'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE cottages ADD COLUMN available_time_end TIME NOT NULL DEFAULT '17:00:00'");
}

include 'partials/head.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">

    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Cottage Management</h4>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item"><a href="#">Reservation</a></li>
                <li class="breadcrumb-item active">Cottage Management</li>
            </ol>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success text-primary alert-dismissible fade show" role="alert">
                <i class="feather text-primary icon-check-circle"></i> Operation completed successfully
                <button type="button" class="close text-primary" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="feather icon-alert-circle"></i> <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Add Cottage Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-toggle="modal" data-target="#addCottageModal">
                <i class="feather icon-plus"></i> Add New Cottage
            </button>
        </div>

        <!-- Cottages List -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-header-title">Cottages</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="cottagesTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Cottage #</th>
                            <th>Price</th>
                            <th>Available Date</th>
                            <th>Available Time</th>
                            <th>Images</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cottages_query = "SELECT c.*, COUNT(ci.id) as image_count FROM cottages c LEFT JOIN cottage_images ci ON c.id = ci.cottage_id GROUP BY c.id ORDER BY c.cottage_number ASC";
                        $cottages_result = $conn->query($cottages_query);
                        
                        // Fetch all availability slots for all cottages
                        $availability_query = "SELECT cottage_id, available_time_start, available_time_end FROM cottage_availability ORDER BY cottage_id, available_time_start, available_time_end";
                        $availability_result = $conn->query($availability_query);
                        
                        $cottage_slots = [];
                        while ($slot = $availability_result->fetch_assoc()) {
                            $cid = $slot['cottage_id'];
                            $key = $slot['available_time_start'] . '-' . $slot['available_time_end'];
                            if (!isset($cottage_slots[$cid])) {
                                $cottage_slots[$cid] = [];
                            }
                            if (!isset($cottage_slots[$cid][$key])) {
                                $cottage_slots[$cid][$key] = [
                                    'start' => $slot['available_time_start'],
                                    'end' => $slot['available_time_end']
                                ];
                            }
                        }

                        if ($cottages_result && $cottages_result->num_rows > 0) {
                            while ($cottage = $cottages_result->fetch_assoc()) {
                                $status_badge = $cottage['status'] === 'available' ?
                                    '<span class="badge badge-success">Available</span>' :
                                    '<span class="badge badge-danger">Unavailable</span>';

                                echo "
                                    <tr>
                                        <td><strong>" . htmlspecialchars($cottage['cottage_number']) . "</strong></td>
                                        <td>₱" . number_format($cottage['price'], 2) . "</td>
                                        <td>" . (isset($cottage['available_date_from']) && isset($cottage['available_date_to']) && $cottage['available_date_from'] && $cottage['available_date_to'] ? $cottage['available_date_from'] . ' to ' . $cottage['available_date_to'] : (isset($cottage['available_date']) ? $cottage['available_date'] : 'Not set')) . "</td>
                                        <td>";
                                
                                // Display all time slots for this cottage
                                if (isset($cottage_slots[$cottage['id']]) && !empty($cottage_slots[$cottage['id']])) {
                                    $slot_strings = [];
                                    foreach ($cottage_slots[$cottage['id']] as $slot) {
                                        $slot_strings[] = date('g:iA', strtotime($slot['start'])) . ' - ' . date('g:iA', strtotime($slot['end']));
                                    }
                                    echo implode('<br>', $slot_strings);
                                } else {
                                    echo 'Not set';
                                }
                                
                                echo "</td>
                                        <td>{$cottage['image_count']} image(s)</td>
                                        <td>{$status_badge}</td>
                                        <td>
                                            <div class=\"dropdown\">
                                                <button class=\"btn btn-sm btn-primary dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
                                                    Actions
                                                </button>
                                                <div class=\"dropdown-menu dropdown-menu-right\">
                                                
                                                    <button type=\"button\" class=\"dropdown-item edit-cottage-btn\" data-id=\"{$cottage['id']}\" data-number=\"" . htmlspecialchars($cottage['cottage_number']) . "\" data-price=\"{$cottage['price']}\" data-date-from=\"" . htmlspecialchars($cottage['available_date_from'] ?? '') . "\" data-date-to=\"" . htmlspecialchars($cottage['available_date_to'] ?? '') . "\" data-time-start=\"" . htmlspecialchars($cottage['available_time_start'] ?? '') . "\" data-time-end=\"" . htmlspecialchars($cottage['available_time_end'] ?? '') . "\" data-status=\"{$cottage['status']}\"><i class=\"feather icon-edit\"></i> Edit</button>
                                                    <a href=\"handlers/cottage_handler.php?action=toggle_status&id={$cottage['id']}\" class=\"dropdown-item\" onclick=\"return confirm('Toggle status?')\"><i class=\"feather icon-refresh-cw\"></i> Toggle Status</a>
                                                    <div class=\"dropdown-divider\"></div>
                                                    " . ($isAdmin ? "<a href=\"handlers/cottage_handler.php?action=delete&id={$cottage['id']}\" class=\"dropdown-item text-danger\" onclick=\"return confirm('Delete this cottage?')\"><i class=\"feather icon-trash\"></i> Delete</a>" : "") . "
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td class='text-center text-muted py-4'>No cottages found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cottage Reservations -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-header-title">Cottage Reservations</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="reservationsTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Reservation #</th>
                            <th>Customer Name</th>
                            <th>Contact</th>
                            <th>Guests</th>
                            <th>Cottage #</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $reservations_query = "SELECT r.*, c.first_name, c.last_name, c.email, c.phone, ct.cottage_number
                                               FROM reservations r
                                               LEFT JOIN customers c ON r.customer_id = c.id
                                               LEFT JOIN cottages ct ON r.cottage_id = ct.id
                                               WHERE r.reservation_type = 'cottage'
                                               ORDER BY r.reservation_date DESC, r.reservation_time DESC";

                        $reservations_result = $conn->query($reservations_query);

                        if ($reservations_result && $reservations_result->num_rows > 0) {
                            while ($reservation = $reservations_result->fetch_assoc()) {
                                $customer_name = $reservation['first_name'] . ' ' . $reservation['last_name'];
                                $formatted_time = date('g:iA', strtotime($reservation['reservation_time']));
                                $cottage_number = htmlspecialchars($reservation['cottage_number'] ?? 'N/A');
                                $status_badge = '';

                                switch ($reservation['status']) {
                                    case 'pending':
                                        $status_badge = '<span class="badge badge-warning">Pending</span>';
                                        break;
                                    case 'confirmed':
                                        $status_badge = '<span class="badge badge-success">Confirmed</span>';
                                        break;
                                    case 'completed':
                                        $status_badge = '<span class="badge badge-info">Completed</span>';
                                        break;
                                    case 'cancelled':
                                        $status_badge = '<span class="badge badge-danger">Cancelled</span>';
                                        break;
                                    default:
                                        $status_badge = '<span class="badge badge-secondary">Unknown</span>';
                                }

                                echo "
                                    <tr>
                                        <td><strong>{$reservation['reservation_number']}</strong></td>
                                        <td>{$customer_name}</td>
                                        <td>
                                            <small>
                                                <div>{$reservation['contact_email']}</div>
                                                <div>{$reservation['contact_phone']}</div>
                                            </small>
                                        </td>
                                        <td>{$reservation['num_guests']}</td>
                                        <td>{$cottage_number}</td>
                                        <td>{$reservation['reservation_date']}</td>
                                        <td>{$formatted_time}</td>
                                        <td>{$status_badge}</td>
                                        <td>
                                            <div class=\"dropdown\">
                                                <button class=\"btn btn-sm btn-primary dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
                                                    Actions
                                                </button>
                                                <div class=\"dropdown-menu dropdown-menu-right\">
                                                    <button type=\"button\" class=\"dropdown-item\" onclick=\"viewReservation('{$customer_name}', '{$reservation['contact_email']}', '{$reservation['contact_phone']}', {$reservation['num_guests']}, '{$reservation['reservation_date']}', '{$formatted_time}', '{$reservation['special_requests']}')\" data-toggle=\"modal\" data-target=\"#detailsModal\"><i class=\"feather icon-eye\"></i> View</button>
                                                    <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=confirmed\" class=\"dropdown-item\" onclick=\"return confirm('Approve this reservation?')\"><i class=\"feather icon-check\"></i> Approved</a>
                                                  
                                                    <div class=\"dropdown-divider\"></div>
                                                    <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=cancelled\" class=\"dropdown-item text-danger\" onclick=\"return confirm('Disapprove this reservation?')\"><i class=\"feather icon-x\"></i> Disapproved</a>                                                    <div class=\"dropdown-divider\"></div>
                                                    <a href=\"handlers/reservation_update_handler.php?action=delete&id={$reservation['id']}\" class=\"dropdown-item text-danger\" onclick=\"return confirm('Delete this reservation?')\"><i class=\"feather icon-trash\"></i> Delete</a>                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td class='text-center text-muted py-4'>No cottage reservations found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Add Cottage Modal -->
<div class="modal fade" id="addCottageModal" tabindex="-1" role="dialog" aria-labelledby="addCottageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addCottageForm" action="handlers/cottage_handler.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCottageModalLabel">Add New Cottage</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="cottage_number">Cottage Number *</label>
                        <input type="text" class="form-control" id="cottage_number" name="cottage_number" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price (₱) *</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                    </div>
                    <!-- Availability Configuration -->
                    <div class="form-group">
                        <label>Availability Configuration *</label>
                        
                        <!-- Date Range Selection -->
                        <div class="mb-3">
                            <label for="available_date">Select Date Range *</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="date_from" class="form-text text-muted small">From Date</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="date_to" class="form-text text-muted small">To Date</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" required>
                                </div>
                            </div>
                        </div>

                        <!-- Time Slots -->
                        <div class="mb-3">
                            <label>Time Slots *</label>
                            <div id="availabilitySlots">
                                <!-- Time slot rows will be added here -->
                                <div class="availability-slot row mb-2" data-slot-index="1">
                                    <div class="col-md-5">
                                        <input type="time" class="form-control slot-start-time" name="start_times[]" required>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="time" class="form-control slot-end-time" name="end_times[]" required>
                                    </div>
                                    <div class="col-md-2 text-right">
                                        <button type="button" class="btn btn-sm btn-danger remove-slot" style="display: none;">
                                            <i class="feather icon-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addSlotBtn">
                                <i class="feather icon-plus"></i> Add Another Time Slot
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="images">Images</label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                        <small class="form-text text-muted">Select multiple images for the cottage</small>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Cottage</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Cottage Modal -->
<div class="modal fade" id="viewCottageModal" tabindex="-1" role="dialog" aria-labelledby="viewCottageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewCottageModalLabel">Cottage Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewCottageBody">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Cottage Modal -->
<div class="modal fade" id="editCottageModal" tabindex="-1" role="dialog" aria-labelledby="editCottageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="editCottageForm" action="handlers/cottage_handler.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCottageModalLabel">Edit Cottage</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_cottage_number">Cottage Number *</label>
                                <input type="text" class="form-control" id="edit_cottage_number" name="cottage_number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_price">Price (₱) *</label>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <!-- Availability Configuration -->
                    <div class="form-group">
                        <label>Availability Configuration *</label>
                        
                        <!-- Date Range Selection -->
                        <div class="mb-3">
                            <label for="available_date">Select Date Range *</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="edit_date_from" class="form-text text-muted small">From Date</label>
                                    <input type="date" class="form-control" id="edit_date_from" name="date_from" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_date_to" class="form-text text-muted small">To Date</label>
                                    <input type="date" class="form-control" id="edit_date_to" name="date_to" required>
                                </div>
                            </div>
                        </div>

                        <!-- Time Slots -->
                        <div class="mb-3">
                            <label>Time Slots *</label>
                            <div id="editAvailabilitySlots">
                                <!-- Time slot rows will be added here -->
                                <div class="availability-slot row mb-2" data-slot-index="1">
                                    <div class="col-md-5">
                                        <input type="time" class="form-control slot-start-time" name="start_times[]" required>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="time" class="form-control slot-end-time" name="end_times[]" required>
                                    </div>
                                    <div class="col-md-2 text-right">
                                        <button type="button" class="btn btn-sm btn-danger remove-slot" style="display: none;">
                                            <i class="feather icon-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="editAddSlotBtn">
                                <i class="feather icon-plus"></i> Add Another Time Slot
                            </button>
                        </div>
                    </div>

                    <div id="editCottageCurrentImages" class="form-group"></div>

                    <div class="form-group">
                        <label for="edit_images">Images</label>
                        <input type="file" class="form-control" id="edit_images" name="images[]" multiple accept="image/*">
                        <small class="form-text text-muted">Select multiple images to add to the cottage</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select class="form-control" id="edit_status" name="status">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Cottage</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Reservation Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailsBody">
                <!-- Details will be populated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script>
    // Pre-loaded time slots for all cottages from database
    const cottageTimeSlots = <?php echo json_encode($cottage_slots); ?>;
    
    console.log('Cottage time slots loaded:', cottageTimeSlots);
    
    $(document).ready(function() {
        $('#cottagesTable').DataTable({
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [
                {
                    targets: 6,
                    orderable: false,
                    searchable: false
                }
            ],
            lengthMenu: [[10, 25, 50], [10, 25, 50]],
            dom: 'lfrtip'
        });

        $('#reservationsTable').DataTable({
            pageLength: 10,
            order: [[4, 'desc']],
            columnDefs: [
                {
                    targets: 8,
                    orderable: false,
                    searchable: false
                }
            ],
            lengthMenu: [[10, 25, 50], [10, 25, 50]],
            dom: 'lfrtip'
        });

        // Time Slot Management for Add Cottage Modal
        let slotCounter = 1;

        // Add new time slot
        $('#addSlotBtn').on('click', function() {
            slotCounter++;
            const newSlot = `
                <div class="availability-slot row mb-2" data-slot-index="${slotCounter}">
                    <div class="col-md-5">
                        <input type="time" class="form-control slot-start-time" name="start_times[]" required>
                    </div>
                    <div class="col-md-5">
                        <input type="time" class="form-control slot-end-time" name="end_times[]" required>
                    </div>
                    <div class="col-md-2 text-right">
                        <button type="button" class="btn btn-sm btn-danger remove-slot">
                            <i class="feather icon-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            $('#availabilitySlots').append(newSlot);
            updateRemoveButtonVisibility();
        });

        // Remove time slot
        $(document).on('click', '.remove-slot', function(e) {
            e.preventDefault();
            $(this).closest('.availability-slot').remove();
            updateRemoveButtonVisibility();
        });

        // Update remove button visibility
        function updateRemoveButtonVisibility() {
            const slots = $('#availabilitySlots .availability-slot');
            slots.each(function() {
                const removeBtn = $(this).find('.remove-slot');
                if (slots.length > 1) {
                    removeBtn.show();
                } else {
                    removeBtn.hide();
                }
            });
        }

        // Initialize remove button visibility on page load
        updateRemoveButtonVisibility();

        // Handle Add Cottage Form Submission
        $('#addCottageForm').on('submit', function(e) {
            e.preventDefault();
            
            const dateFrom = $('#date_from').val();
            const dateTo = $('#date_to').val();
            
            if (!dateFrom || !dateTo) {
                alert('Please select a date range');
                return false;
            }
            
            if (new Date(dateFrom) > new Date(dateTo)) {
                alert('From date must be before To date');
                return false;
            }
            
            // Check if at least one time slot is filled
            const startTimes = $('.slot-start-time').map(function() { return $(this).val(); }).get();
            const endTimes = $('.slot-end-time').map(function() { return $(this).val(); }).get();
            
            let hasValidSlot = false;
            for (let i = 0; i < startTimes.length; i++) {
                if (startTimes[i] && endTimes[i]) {
                    if (startTimes[i] >= endTimes[i]) {
                        const start = new Date('1970-01-01T' + startTimes[i]).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
                        const end = new Date('1970-01-01T' + endTimes[i]).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
                        alert('Slot ' + (i + 1) + ' error: Start time (' + start + ') must be before End time (' + end + ')');
                        return false;
                    }
                    hasValidSlot = true;
                }
            }
            
            if (!hasValidSlot) {
                alert('Please add at least one complete time slot (both start and end time)');
                return false;
            }
            
            // Submit the form
            this.submit();
        });

        // Reset modal when closed
        $('#addCottageModal').on('hidden.bs.modal', function() {
            $('#addCottageForm')[0].reset();
            // Reset to single slot
            $('#availabilitySlots').html(`
                <div class="availability-slot row mb-2" data-slot-index="1">
                    <div class="col-md-5">
                        <input type="time" class="form-control slot-start-time" name="start_times[]" required>
                    </div>
                    <div class="col-md-5">
                        <input type="time" class="form-control slot-end-time" name="end_times[]" required>
                    </div>
                    <div class="col-md-2 text-right">
                        <button type="button" class="btn btn-sm btn-danger remove-slot" style="display: none;">
                            <i class="feather icon-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            slotCounter = 1;
            updateRemoveButtonVisibility();
        });

        // Reset edit modal when closed
        $('#editCottageModal').on('hidden.bs.modal', function() {
            $('#editCottageForm')[0].reset();
            // Clear images preview
            $('#editCottageCurrentImages').html('');
            // Reset to single slot
            $('#editAvailabilitySlots').html(`
                <div class="availability-slot row mb-2" data-slot-index="1">
                    <div class="col-md-5">
                        <input type="time" class="form-control slot-start-time" name="start_times[]" required>
                    </div>
                    <div class="col-md-5">
                        <input type="time" class="form-control slot-end-time" name="end_times[]" required>
                    </div>
                    <div class="col-md-2 text-right">
                        <button type="button" class="btn btn-sm btn-danger remove-slot" style="display: none;">
                            <i class="feather icon-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            editSlotCounter = 1;
            updateEditRemoveButtonVisibility();
        });

        // Handle view cottage button click
        $(document).on('click', '.view-cottage-btn', function() {
            var id = $(this).data('id');
            
            $.ajax({
                url: 'handlers/cottage_handler.php?action=get&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    let html = '<div class="container-fluid">';
                    
                    // Basic Information
                    html += '<div class="row mb-4">';
                    html += '<div class="col-md-6">';
                    html += '<h6 class="text-muted">Cottage Number</h6>';
                    html += '<p class="h5">' + htmlEscape(data.cottage_number) + '</p>';
                    html += '</div>';
                    html += '<div class="col-md-6">';
                    html += '<h6 class="text-muted">Price</h6>';
                    html += '<p class="h5">₱' + parseFloat(data.price).toLocaleString('en-PH', {minimumFractionDigits: 2}) + '</p>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Status
                    html += '<div class="row mb-4">';
                    html += '<div class="col-md-6">';
                    html += '<h6 class="text-muted">Status</h6>';
                    let statusBadge = data.status === 'available' ? '<span class="badge badge-success">Available</span>' : '<span class="badge badge-danger">Unavailable</span>';
                    html += '<p>' + statusBadge + '</p>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Availability Schedule
                    html += '<div class="row mb-4">';
                    html += '<div class="col-12">';
                    html += '<h6 class="text-muted mb-3">Availability Schedule</h6>';
                    
                    if (data.availability && data.availability.length > 0) {
                        // Helper function to format time to 12-hour format
                        function formatTime12(timeStr) {
                            const [hours, minutes] = timeStr.substring(0, 5).split(':');
                            const hour = parseInt(hours);
                            const ampm = hour >= 12 ? 'PM' : 'AM';
                            const hour12 = hour % 12 || 12;
                            return hour12 + ':' + minutes + ' ' + ampm;
                        }
                        
                        html += '<div class="table-responsive">';
                        html += '<table class="table table-sm table-bordered">';
                        html += '<thead class="thead-light"><tr><th>Date</th><th>Time</th><th>Status</th></tr></thead>';
                        html += '<tbody>';
                        
                        data.availability.forEach(function(slot, index) {
                            let slotStatus = slot.status || 'available';
                            let statusBadge = slotStatus === 'available' ? '<span class="badge badge-success">Available</span>' : 
                                            slotStatus === 'booked' ? '<span class="badge badge-warning">Booked</span>' : 
                                            '<span class="badge badge-danger">Unavailable</span>';
                            
                            html += '<tr>';
                            html += '<td>' + slot.available_date + '</td>';
                            html += '<td>' + formatTime12(slot.available_time_start) + ' - ' + formatTime12(slot.available_time_end) + '</td>';
                            html += '<td>' + statusBadge + '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        html += '</div>';
                    } else {
                        html += '<p class="text-muted">No availability slots configured</p>';
                    }
                    
                    html += '</div></div>';
                    
                    // Images
                    if (data.images && data.images.length > 0) {
                        html += '<div class="row mb-4">';
                        html += '<div class="col-12">';
                        html += '<h6 class="text-muted mb-3">Images</h6>';
                        html += '<div class="row">';
                        
                        data.images.forEach(function(image) {
                            html += '<div class="col-md-3 mb-3">';
                            html += '<img src="assets/img/cottages/' + htmlEscape(image) + '" class="img-fluid rounded" alt="Cottage image">';
                            html += '</div>';
                        });
                        
                        html += '</div></div></div>';
                    }
                    
                    html += '</div>';
                    
                    $('#viewCottageBody').html(html);
                },
                error: function() {
                    $('#viewCottageBody').html('<p class="text-danger">Error loading cottage details</p>');
                }
            });
        });

        // Handle edit cottage button click
        $(document).on('click', '.edit-cottage-btn', function() {
            var id = $(this).data('id');
            editCottage(id);
        });
    });

    let editSlotCounter = 1;

    function editCottage(id) {
        // Reset the slot counter when opening edit modal
        editSlotCounter = 1;
        
        // Get the button that was clicked
        var $btn = $('.edit-cottage-btn[data-id="' + id + '"]');
        
        // Show the modal
        $('#editCottageModal').modal('show');
        
        // Populate basic information from button data attributes
        var cottageNumber = $btn.data('number');
        var price = $btn.data('price');
        var dateFrom = $btn.data('date-from');
        var dateTo = $btn.data('date-to');
        var status = $btn.data('status');
        
        // Log all values to see what we're getting
        console.log('Button data attributes:');
        console.log('  ID:', id);
        console.log('  cottageNumber:', cottageNumber);
        console.log('  price:', price);
        console.log('  dateFrom:', dateFrom);
        console.log('  dateTo:', dateTo);
        console.log('  status:', status);
        
        // Set form values immediately
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_cottage_number').value = cottageNumber || '';
        document.getElementById('edit_price').value = price || '';
        document.getElementById('edit_status').value = status || 'available';
        
        // Set dates - ensure they're not empty strings before setting
        if (dateFrom) {
            document.getElementById('edit_date_from').value = dateFrom;
            console.log('Set edit_date_from to:', dateFrom);
        } else {
            console.log('dateFrom is empty/null');
        }
        
        if (dateTo) {
            document.getElementById('edit_date_to').value = dateTo;
            console.log('Set edit_date_to to:', dateTo);
        } else {
            console.log('dateTo is empty/null');
        }
        
        console.log('Form populated with basic info - ID: ' + id + ', Number: ' + cottageNumber + ', Price: ' + price);
        
        // Clear the slots container
        document.getElementById('editAvailabilitySlots').innerHTML = '';
        
        // Load time slots from pre-loaded data
        console.log('Looking for time slots for cottage ID: ' + id);
        if (cottageTimeSlots[id]) {
            console.log('Found ' + Object.keys(cottageTimeSlots[id]).length + ' time slots');
            const slots = cottageTimeSlots[id];
            const slotsContainer = document.getElementById('editAvailabilitySlots');
            let slotIndex = 0;
            
            // Add all slots
            for (let key in slots) {
                slotIndex++;
                const slot = slots[key];
                console.log('Adding slot ' + slotIndex + ': ' + slot.start + ' to ' + slot.end);
                
                const newSlot = document.createElement('div');
                newSlot.className = 'availability-slot row mb-2';
                newSlot.setAttribute('data-slot-index', slotIndex);
                newSlot.innerHTML = `
                    <div class="col-md-5">
                        <input type="time" class="form-control slot-start-time" name="start_times[]" value="${slot.start}" required>
                    </div>
                    <div class="col-md-5">
                        <input type="time" class="form-control slot-end-time" name="end_times[]" value="${slot.end}" required>
                    </div>
                    <div class="col-md-2 text-right">
                        <button type="button" class="btn btn-sm btn-danger remove-slot" style="display: none;">
                            <i class="feather icon-trash"></i>
                        </button>
                    </div>
                `;
                slotsContainer.appendChild(newSlot);
            }
            
            // Update the slot counter to match the number of loaded slots
            editSlotCounter = slotIndex;
            
            console.log('Total slots now in DOM: ' + slotsContainer.querySelectorAll('.availability-slot').length);
            updateEditRemoveButtonVisibility();
        } else {
            console.log('No time slots found for cottage ' + id + ', adding default slot');
            // Add a default empty slot if no slots exist
            const slotsContainer = document.getElementById('editAvailabilitySlots');
            const defaultSlot = document.createElement('div');
            defaultSlot.className = 'availability-slot row mb-2';
            defaultSlot.setAttribute('data-slot-index', '1');
            defaultSlot.innerHTML = `
                <div class="col-md-5">
                    <input type="time" class="form-control slot-start-time" name="start_times[]" required>
                </div>
                <div class="col-md-5">
                    <input type="time" class="form-control slot-end-time" name="end_times[]" required>
                </div>
                <div class="col-md-2 text-right">
                    <button type="button" class="btn btn-sm btn-danger remove-slot" style="display: none;">
                        <i class="feather icon-trash"></i>
                    </button>
                </div>
            `;
            slotsContainer.appendChild(defaultSlot);
            editSlotCounter = 1;
        }
    }

    function updateEditRemoveButtonVisibility() {
        const slots = $('#editAvailabilitySlots .availability-slot');
        slots.each(function() {
            const removeBtn = $(this).find('.remove-slot');
            if (slots.length > 1) {
                removeBtn.show();
            } else {
                removeBtn.hide();
            }
        });
    }

    // Handle edit form slot management
    $(document).on('click', '#editAddSlotBtn', function() {
        editSlotCounter++;
        const newSlot = `
            <div class="availability-slot row mb-2" data-slot-index="${editSlotCounter}">
                <div class="col-md-5">
                    <input type="time" class="form-control slot-start-time" name="start_times[]" required>
                </div>
                <div class="col-md-5">
                    <input type="time" class="form-control slot-end-time" name="end_times[]" required>
                </div>
                <div class="col-md-2 text-right">
                    <button type="button" class="btn btn-sm btn-danger remove-slot">
                        <i class="feather icon-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#editAvailabilitySlots').append(newSlot);
        updateEditRemoveButtonVisibility();
    });

    // Handle edit form submission
    $('#editCottageForm').on('submit', function(e) {
        e.preventDefault();
        
        const dateFrom = $('#edit_date_from').val();
        const dateTo = $('#edit_date_to').val();
        
        if (!dateFrom || !dateTo) {
            alert('Please select a date range');
            return false;
        }
        
        if (new Date(dateFrom) > new Date(dateTo)) {
            alert('From date must be before To date');
            return false;
        }
        
        // Check if at least one time slot is filled
        const editForm = document.getElementById('editCottageForm');
        const startTimes = $(editForm).find('.slot-start-time').map(function() { return $(this).val(); }).get();
        const endTimes = $(editForm).find('.slot-end-time').map(function() { return $(this).val(); }).get();
        
        let hasValidSlot = false;
        for (let i = 0; i < startTimes.length; i++) {
            if (startTimes[i] && endTimes[i]) {
                if (startTimes[i] >= endTimes[i]) {
                    const start = new Date('1970-01-01T' + startTimes[i]).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
                    const end = new Date('1970-01-01T' + endTimes[i]).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
                    alert('Slot ' + (i + 1) + ' error: Start time (' + start + ') must be before End time (' + end + ')');
                    return false;
                }
                hasValidSlot = true;
            }
        }
        
        if (!hasValidSlot) {
            alert('Please add at least one complete time slot (both start and end time)');
            return false;
        }
        
        // Submit the form
        this.submit();
    });

    function viewReservation(name, email, phone, guests, date, time, requests) {
        const detailsBody = document.getElementById('detailsBody');
        detailsBody.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Customer Name:</strong> ${name}</p>
                    <p><strong>Email:</strong> ${email}</p>
                    <p><strong>Phone:</strong> ${phone}</p>
                    <p><strong>Reservation Type:</strong> <span class="badge badge-primary">Cottage</span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Number of Guests:</strong> ${guests}</p>
                    <p><strong>Date:</strong> ${date}</p>
                    <p><strong>Time:</strong> ${time}</p>
                </div>
            </div>
            <hr>
            <p><strong>Special Requests:</strong></p>
            <p>${requests || 'No special requests'}</p>
        `;
    }

    // Helper function to escape HTML
    function htmlEscape(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
</script>