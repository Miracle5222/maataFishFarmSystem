<?php
include 'auth_admin.php';

// Check authorization - admin, staff, and manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    header('Location: index.php');
    exit;
}

require 'config/db.php';

// Fixed entrance fee
$ENTRANCE_FEE = 50;

// Handle form submission
$message = '';
$message_type = '';
$submission_data = null;

// Check if this is a redirect after successful deletion
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = '✅ Entrance fee record deleted successfully!';
    $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_guests') {
    $num_guests = intval($_POST['num_guests'] ?? 0);
    
    if ($num_guests < 1) {
        $message = 'Please enter at least 1 guest';
        $message_type = 'error';
    } else {
        // Collect guest names and phone numbers
        $guests = [];
        $valid = true;
        
        for ($i = 1; $i <= $num_guests; $i++) {
            $first_name = trim($_POST["guest_first_{$i}"] ?? '');
            $last_name = trim($_POST["guest_last_{$i}"] ?? '');
            $phone = trim($_POST["guest_phone_{$i}"] ?? '');
            
            if (empty($first_name) || empty($last_name) || empty($phone)) {
                $message = "Please enter first name, last name, and phone number for all guests";
                $message_type = 'error';
                $valid = false;
                break;
            }
            
            $guests[] = [
                'first_name' => htmlspecialchars($first_name),
                'last_name' => htmlspecialchars($last_name),
                'phone' => htmlspecialchars($phone)
            ];
        }
        
        if ($valid) {
            $total_amount = $num_guests * $ENTRANCE_FEE;
            
            // Insert guests into customers table
            $insert_errors = false;
            $inserted_customers = [];
            
            foreach ($guests as $guest) {
                $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, phone, customer_type) VALUES (?, ?, ?, 'diner')");
                if ($stmt) {
                    $stmt->bind_param('sss', $guest['first_name'], $guest['last_name'], $guest['phone']);
                    if ($stmt->execute()) {
                        $inserted_customers[] = [
                            'id' => $conn->insert_id,
                            'first_name' => $guest['first_name'],
                            'last_name' => $guest['last_name'],
                            'phone' => $guest['phone']
                        ];
                    } else {
                        $insert_errors = true;
                    }
                    $stmt->close();
                } else {
                    $insert_errors = true;
                }
            }
            
            if (!$insert_errors) {
                // Log activity
                require 'handlers/activity_logger.php';
                $guest_list = implode(', ', array_map(function($g) { return $g['first_name'] . ' ' . $g['last_name']; }, $guests));
                $description = "Recorded entrance fee for {$num_guests} guest(s): {$guest_list}. Total: ₱" . number_format($total_amount, 2);
                
                $logged = logActivity(
                    $conn,
                    $_SESSION['user_id'],
                    $_SESSION['role'],
                    'CREATE',
                    'entrance_fee',
                    0,
                    'Entrance Fee Collection',
                    $description,
                    null,
                    ['num_guests' => $num_guests, 'fee_per_guest' => $ENTRANCE_FEE, 'total' => $total_amount, 'guests' => $guests]
                );
                
                if ($logged) {
                    $message = 'Entrance fee recorded and guests added to customer database!';
                    $message_type = 'success';
                    
                    // Store submission data for display
                    $submission_data = [
                        'num_guests' => $num_guests,
                        'guests' => $guests,
                        'fee_per_guest' => $ENTRANCE_FEE,
                        'total_amount' => $total_amount,
                        'timestamp' => date('M d, Y g:ia')
                    ];
                } else {
                    $message = 'Guests added but activity logging failed';
                    $message_type = 'error';
                }
            } else {
                $message = 'Failed to insert guests into customer database';
                $message_type = 'error';
            }
        }
    }
}

// Handle delete entrance fee record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_record') {
    $record_id = intval($_POST['record_id'] ?? 0);
    
    if ($record_id > 0) {
        // Delete the activity log record
        $delete_stmt = $conn->prepare("DELETE FROM activity_logs WHERE id = ? AND entity_type = 'entrance_fee'");
        if ($delete_stmt) {
            $delete_stmt->bind_param('i', $record_id);
            if ($delete_stmt->execute()) {
                $message = '✅ Entrance fee record deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to delete the record';
                $message_type = 'error';
            }
            $delete_stmt->close();
        } else {
            $message = 'Database error occurred';
            $message_type = 'error';
        }
        
        // Redirect to prevent form resubmission
        header('Location: entrance_fee.php?success=1');
        exit;
    } else {
        $message = 'Invalid record ID';
        $message_type = 'error';
    }
}

// Get all entrance fee collection records from activity logs
$records = [];
$stmt = $conn->prepare("SELECT id, user_name, new_values, timestamp FROM activity_logs WHERE entity_type = 'entrance_fee' AND activity_type = 'CREATE' ORDER BY timestamp DESC LIMIT 100");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $new_values = json_decode($row['new_values'], true);
        $records[] = [
            'id' => $row['id'],
            'user_name' => $row['user_name'],
            'num_guests' => $new_values['num_guests'] ?? 0,
            'fee_per_guest' => $new_values['fee_per_guest'] ?? $ENTRANCE_FEE,
            'total' => $new_values['total'] ?? 0,
            'timestamp' => $row['timestamp']
        ];
    }
    $stmt->close();
}



include 'partials/head.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Entrance Fee Management</h4>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item active">Entrance Fee</li>
            </ol>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo ($message_type === 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert" style="<?php echo ($message_type === 'success') ? 'background-color:#28a745;color:white;border:none;' : 'background-color:#dc3545;color:white;border:none;'; ?>">
                <i class="feather icon-<?php echo ($message_type === 'success') ? 'check-circle' : 'alert-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color:white; opacity:0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Entrance Fee Input Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-header-title">Record Entrance Fee</h5>
                <p class="text-muted mb-0">Fixed Entrance Fee: <strong>₱<?php echo $ENTRANCE_FEE; ?>.00</strong> per guest</p>
            </div>
            <div class="card-body">
                <form id="entranceFeeForm" method="POST">
                    <input type="hidden" name="action" value="record_guests">
                    
                    <!-- Step 1: Enter Number of Guests -->
                    <div class="form-group">
                        <label for="numGuests"><strong>Number of Guests</strong></label>
                        <div class="input-group input-group-lg">
                            <input type="number" class="form-control" id="numGuests" name="num_guests" placeholder="Enter number of guests (optional)" min="1" max="500" value="">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" id="generateFieldsBtn">
                                    <i class="feather icon-plus"></i> Generate Fields
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Enter the number of guests and click "Generate Fields" to add name inputs</small>
                    </div>

                    <!-- Guest Input Fields (Generated Dynamically) -->
                    <div id="guestFieldsContainer" style="display:none; margin-top:30px;">
                        <h6 class="font-weight-bold mb-3">Enter Guest Information</h6>
                        <div id="guestFieldsList" class="form-row" style="gap:15px;">
                            <!-- Guest fields will be generated here -->
                        </div>

                        <!-- Total Calculation -->
                        <div class="alert alert-info mt-4" style="background-color:#cfe2ff;color:#084298;border-color:#b6d4fe;">
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Total Guests</p>
                                    <h4 class="mb-0"><span id="totalGuestsDisplay">0</span> guests</h4>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Total Entrance Fee</p>
                                    <h4 class="mb-0" style="color:#28a745;"><strong>₱<span id="totalFeeDisplay">0.00</span></strong></h4>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="feather icon-save"></i> Record Entrance Fee
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Display Recorded Result -->
        <?php if ($submission_data): ?>
        <div class="card border-success mt-4">
            <div class="card-header" style="background-color:#28a745;color:white;">
                <h5 class="card-header-title mb-0"><i class="feather icon-check-circle"></i> Entrance Fee Recorded Successfully</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4 text-center border-right">
                        <p class="text-muted mb-1">Total Guests</p>
                        <h3 class="text-primary"><?php echo $submission_data['num_guests']; ?></h3>
                    </div>
                    <div class="col-md-4 text-center border-right">
                        <p class="text-muted mb-1">Fee Per Guest</p>
                        <h3 class="text-info">₱<?php echo number_format($submission_data['fee_per_guest'], 2); ?></h3>
                    </div>
                    <div class="col-md-4 text-center">
                        <p class="text-muted mb-1">Total Amount</p>
                        <h3 class="text-success"><strong>₱<?php echo number_format($submission_data['total_amount'], 2); ?></strong></h3>
                    </div>
                </div>

                <h6 class="font-weight-bold mb-3">Guest List</h6>
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Phone Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submission_data['guests'] as $idx => $guest): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo $guest['first_name']; ?></td>
                            <td><?php echo $guest['last_name']; ?></td>
                            <td><?php echo $guest['phone']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="text-muted text-center mt-3">
                    <small>Recorded on: <?php echo $submission_data['timestamp']; ?></small>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Guests Added from Entrance Fee System Table -->
        <div class="card mt-4 mb-4">
            <div class="card-header">
                <h5 class="card-header-title mb-0"><i class="feather icon-users"></i> Guests Added from Entrance Fee System</h5>
            </div>
            <div class="table-responsive">
                <table id="entranceFeeGuestsTable" class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Date Time Added</th>
                            <th class="text-right">Guest Number</th>
                            <th class="text-right">Fee/Guest (₱)</th>
                            <th class="text-right">Total Amount (₱)</th>
                            <th>Recorded By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php 
                            $recordsWithGuests = array_filter($records, function($record) {
                                return $record['num_guests'] > 0;
                            });
                            ?>
                            <?php if (!empty($recordsWithGuests)): ?>
                                <?php foreach ($recordsWithGuests as $record): ?>
                                <tr>
                                    <td><?php echo (int)$record['id']; ?></td>
                                    <td><small><?php echo date('M d, Y g:ia', strtotime($record['timestamp'])); ?></small></td>
                                    <td class="text-right"><strong><?php echo $record['num_guests']; ?></strong></td>
                                    <td class="text-right">₱<?php echo number_format($record['fee_per_guest'], 2); ?></td>
                                    <td class="text-right"><strong style="color: #28a745;">₱<?php echo number_format($record['total'], 2); ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($record['user_name']); ?></small></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-icon btn-outline-info view-entrance-fee" 
                                            data-id="<?php echo (int)$record['id']; ?>"
                                            data-record-id="<?php echo (int)$record['id']; ?>"
                                            title="View Guests"><i class="feather icon-eye"></i></button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this entrance fee record and all associated customer data?');">
                                            <input type="hidden" name="action" value="delete_record">
                                            <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No entrance fee records with guests found</td>
                                </tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No entrance fee records yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


    </div>
    <!-- [ content ] End -->

    <?php include 'partials/footer.php'; ?>
</div>
<!-- [ Layout content ] End -->

<!-- View Guests Modal -->
<div id="viewGuestsModal" class="modal" tabindex="-1" role="dialog" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1050;">
    <div class="modal-dialog modal-lg" role="document" style="position:relative; margin:50px auto; max-width:600px; background:white; border-radius:4px;">
        <div class="modal-content">
            <div class="modal-header" style="padding:15px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center;">
                <h5 class="modal-title mb-0">Guests Details</h5>
                <button type="button" class="close" onclick="closeViewGuestsModal()" style="font-size:24px; border:none; background:none; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body" style="padding:20px; max-height:400px; overflow-y:auto;">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">No.</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody id="guestsTableBody">
                        <tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer" style="padding:15px; border-top:1px solid #e0e0e0;">
                <button type="button" class="btn btn-secondary" onclick="closeViewGuestsModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const ENTRANCE_FEE = <?php echo $ENTRANCE_FEE; ?>;

// Generate guest input fields based on number entered
document.getElementById('generateFieldsBtn').addEventListener('click', function() {
    const inputEl = document.getElementById('numGuests');
    let numGuests = parseInt(inputEl.value);

    // If input is empty or invalid, default to 1 (number optional)
    if (isNaN(numGuests) || numGuests < 1) {
        numGuests = 1;
    }

    if (numGuests > 500) {
        alert('Maximum 500 guests allowed');
        numGuests = 500;
    }

    // Ensure the visible input reflects the generated count
    inputEl.value = numGuests;
    
    const container = document.getElementById('guestFieldsList');
    container.innerHTML = ''; // Clear previous fields
    
    // Create input fields for each guest
    for (let i = 1; i <= numGuests; i++) {
        const guestDiv = document.createElement('div');
        guestDiv.className = 'col-md-6 col-lg-4';
        guestDiv.innerHTML = `
            <div class="card mb-3">
                <div class="card-header" style="background-color:#f8f9fa; padding:10px;">
                    <small style="font-weight:600;">Guest ${i}</small>
                </div>
                <div class="card-body" style="padding:15px;">
                    <div class="form-group mb-2">
                        <label style="font-size:13px; margin-bottom:5px;">First Name</label>
                        <input type="text" class="form-control form-control-sm" name="guest_first_${i}" placeholder="First name" required>
                    </div>
                    <div class="form-group mb-2">
                        <label style="font-size:13px; margin-bottom:5px;">Last Name</label>
                        <input type="text" class="form-control form-control-sm" name="guest_last_${i}" placeholder="Last name" required>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:13px; margin-bottom:5px;">Phone Number</label>
                        <input type="tel" class="form-control form-control-sm" name="guest_phone_${i}" placeholder="Phone number" required>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(guestDiv);
    }
    
    // Update totals
    updateTotals();
    
    // Show the fields container
    document.getElementById('guestFieldsContainer').style.display = 'block';
    
    // Scroll to guest fields
    setTimeout(() => {
        document.getElementById('guestFieldsContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
});

// Update total calculations
function updateTotals() {
    const numGuests = parseInt(document.getElementById('numGuests').value) || 0;
    const totalFee = numGuests * ENTRANCE_FEE;
    
    document.getElementById('totalGuestsDisplay').textContent = numGuests;
    document.getElementById('totalFeeDisplay').textContent = totalFee.toFixed(2);
}

// Update totals when number changes
document.getElementById('numGuests').addEventListener('change', updateTotals);
document.getElementById('numGuests').addEventListener('input', updateTotals);

// Prevent form submission if fields not generated
document.getElementById('entranceFeeForm').addEventListener('submit', function(e) {
    const numGuests = parseInt(document.getElementById('numGuests').value) || 0;
    const container = document.getElementById('guestFieldsList');
    
    if (numGuests > 0 && container.children.length === 0) {
        e.preventDefault();
        alert('Please click "Generate Fields" first to create input fields for all guests');
    }
});

// Handle view guests button clicks
document.querySelectorAll('.view-entrance-fee').forEach(button => {
    button.addEventListener('click', function() {
        const recordId = this.getAttribute('data-record-id');
        fetchAndDisplayGuests(recordId);
    });
});

// Fetch and display guests for a record
function fetchAndDisplayGuests(recordId) {
    fetch('handlers/get_entrance_fee_guests.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'record_id=' + encodeURIComponent(recordId)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error, status = ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.guests && Array.isArray(data.guests)) {
            let html = '';
            if (data.guests.length > 0) {
                data.guests.forEach((guest, idx) => {
                    html += '<tr>';
                    html += '<td>' + (idx + 1) + '</td>';
                    html += '<td>' + escapeHtml(guest.first_name || '') + '</td>';
                    html += '<td>' + escapeHtml(guest.last_name || '') + '</td>';
                    html += '<td>' + escapeHtml(guest.phone || '') + '</td>';
                    html += '</tr>';
                });
            } else {
                html = '<tr><td colspan="4" class="text-center text-muted">No guests in this record</td></tr>';
            }
            document.getElementById('guestsTableBody').innerHTML = html;
            openViewGuestsModal();
        } else {
            const errorMsg = data.message || 'Failed to load guest details';
            alert(errorMsg);
            console.error('Data error:', data);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Error loading guest details: ' + error.message);
    });
}

// Modal functions
function openViewGuestsModal() {
    document.getElementById('viewGuestsModal').style.display = 'block';
}

function closeViewGuestsModal() {
    document.getElementById('viewGuestsModal').style.display = 'none';
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('viewGuestsModal');
    if (event.target === modal) {
        closeViewGuestsModal();
    }
});
</script>


