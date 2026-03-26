<?php
include 'config/db.php';
include 'auth_admin.php';

// Function to format time to AM/PM
function formatTimeToAMPM($time) {
    if (empty($time)) return '-';
    $parts = explode(':', $time);
    $hour = (int)$parts[0];
    $minute = $parts[1] ?? '00';
    $ampm = $hour >= 12 ? 'PM' : 'AM';
    $hour12 = $hour % 12;
    if ($hour12 == 0) $hour12 = 12;
    return sprintf('%d:%s %s', $hour12, $minute, $ampm);
}

// Check and update reservations table schema if needed
$result = $conn->query("SHOW COLUMNS FROM reservations LIKE 'cottage_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE reservations ADD COLUMN cottage_id INT NULL");
}

include 'partials/head.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<style>
    /* Fix text color in cottage occupancy table */
    #cottageOccupancyTable tbody tr td {
        color: #333 !important;
        font-weight: 500;
    }
    
    #cottageOccupancyTable tbody tr.table-success td {
        color: #155724 !important;
        background-color: #d4edda !important;
    }
    
    #cottageOccupancyTable tbody tr.table-warning td {
        color: #856404 !important;
        background-color: #fff3cd !important;
    }
    
    #cottageOccupancyTable .badge {
        font-weight: 600;
    }
    
    #cottageOccupancyTable small {
        color: #555 !important;
    }
</style>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">

    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Reservations</h4>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item active">Reservations</li>
            </ol>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success text-primary alert-dismissible fade show" role="alert">
                <i class="feather text-primary icon-check-circle"></i> Reservation status updated successfully
                <button type="button" class="close text-primary" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header with-elements">
                <h5 class="card-header-title">Reservation List</h5>
                <div class="card-header-elements ml-md-auto">
                    <!-- <a href="reservation_new.php" class="btn btn-primary btn-sm">
                        <i class="feather icon-plus"></i> New Reservation
                    </a> -->
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="reservationsTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Reservation #</th>
                            <th>Customer Name</th>
                            <th>Contact</th>
                            <th>Type</th>
                            <th>Guests</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all reservations except cottage - filtering done by DataTables
                        $query = "SELECT r.*, c.first_name, c.last_name, c.email, c.phone, ct.cottage_number
                                  FROM reservations r
                                  LEFT JOIN customers c ON r.customer_id = c.id
                                  LEFT JOIN cottages ct ON r.cottage_id = ct.id
                                  WHERE r.reservation_type != 'cottage'
                                  ORDER BY r.reservation_date DESC, r.reservation_time DESC";

                        $result = $conn->query($query);

                        if ($result && $result->num_rows > 0) {
                            while ($reservation = $result->fetch_assoc()) {
                                $customer_name = $reservation['first_name'] . ' ' . $reservation['last_name'];
                                $formatted_time = formatTimeToAMPM($reservation['reservation_time']);
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
                                            <td><span class=\"badge badge-primary\">{$reservation['reservation_type']}</span></td>
                                            <td>{$reservation['num_guests']}</td>
                                            <td>{$reservation['reservation_date']}</td>
                                            <td>{$formatted_time}</td>
                                            <td>{$status_badge}</td>
                                            <td>
                                                <div class=\"dropdown\">
                                                    <button class=\"btn btn-sm btn-primary dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
                                                        Actions
                                                    </button>
                                                    <div class=\"dropdown-menu dropdown-menu-right\">
                                                        <button type=\"button\" class=\"dropdown-item\" onclick=\"viewReservation('{$customer_name}', '{$reservation['contact_email']}', '{$reservation['contact_phone']}', '{$reservation['reservation_type']}', {$reservation['num_guests']}, '{$reservation['reservation_date']}', '{$formatted_time}', '{$reservation['special_requests']}', '{$reservation['cottage_number']}')\" data-toggle=\"modal\" data-target=\"#detailsModal\"><i class=\"feather icon-eye\"></i> View</button>
                                                        <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=confirmed\" class=\"dropdown-item\" onclick=\"return confirm('Confirm this reservation?')\"><i class=\"feather icon-check\"></i> Confirm</a>
                                                        <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=completed\" class=\"dropdown-item\" onclick=\"return confirm('Mark as completed?')\"><i class=\"feather icon-check-circle\"></i> Done</a>
                                                        <div class=\"dropdown-divider\"></div>
                                                        <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=cancelled\" class=\"dropdown-item text-danger\" onclick=\"return confirm('Cancel this reservation?')\"><i class=\"feather icon-x\"></i> Cancel</a>
                                                        <a href=\"handlers/reservation_delete_handler.php?id={$reservation['id']}\" class=\"dropdown-item text-danger\" onclick=\"return confirm('Are you sure you want to permanently delete this reservation? This action cannot be undone.')\"><i class=\"feather icon-trash-2\"></i> Delete</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr
                                ";
                            }
                        } else {
                            echo "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td class='text-center text-muted py-4'>No reservations found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cottage Occupancy Section -->
        <div class="card mt-4">
            <div class="card-header with-elements">
                <h5 class="card-header-title">
                    <i class="feather icon-home"></i> Cottage Occupancy Status
                </h5>
                <div class="card-header-elements ml-md-auto">
                    <span class="text-muted small">Real-time cottage rental tracking</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="cottageOccupancyTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Cottage #</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Guests</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch ALL cottages with their current reservations (if any)
                        $cottage_query = "SELECT 
                                            ct.id,
                                            ct.cottage_number,
                                            ct.status as cottage_status,
                                            ct.price,
                                            r.id as reservation_id,
                                            r.reservation_number,
                                            r.status as reservation_status,
                                            r.reservation_date,
                                            r.reservation_time,
                                            r.num_guests,
                                            r.special_requests,
                                            c.first_name,
                                            c.last_name,
                                            c.phone,
                                            c.email
                                         FROM cottages ct
                                         LEFT JOIN reservations r ON ct.id = r.cottage_id AND r.reservation_type = 'cottage' AND r.status IN ('confirmed', 'pending')
                                         LEFT JOIN customers c ON r.customer_id = c.id
                                         ORDER BY 
                                             CASE 
                                                 WHEN r.status = 'confirmed' THEN 1
                                                 WHEN r.status = 'pending' THEN 2
                                                 ELSE 3
                                             END ASC,
                                             ct.cottage_number ASC";

                        $cottage_result = $conn->query($cottage_query);

                        if ($cottage_result && $cottage_result->num_rows > 0) {
                            while ($cottage_res = $cottage_result->fetch_assoc()) {
                                $cottage_num = $cottage_res['cottage_number'] ?? 'N/A';
                                $has_reservation = !is_null($cottage_res['reservation_id']);
                                
                                // Only set customer data if there's a reservation
                                if ($has_reservation) {
                                    $first_name = trim($cottage_res['first_name'] ?? '');
                                    $last_name = trim($cottage_res['last_name'] ?? '');
                                    $customer_name = ($first_name && $last_name) ? "$first_name $last_name" : ($first_name ?: ($last_name ?: 'Unknown'));
                                    $phone = trim($cottage_res['phone'] ?? '') ?: '-';
                                    $check_in_date = $cottage_res['reservation_date'] ?? '-';
                                    $check_in_time = formatTimeToAMPM($cottage_res['reservation_time']) ?? '-';
                                    $num_guests = $cottage_res['num_guests'] ?? '0';
                                } else {
                                    $customer_name = '-';
                                    $phone = '-';
                                    $check_in_date = '-';
                                    $check_in_time = '-';
                                    $num_guests = '0';
                                }
                                
                                // Determine status badge
                                $status_badge = '';
                                $row_class = '';
                                
                                if ($has_reservation) {
                                    switch ($cottage_res['reservation_status']) {
                                        case 'confirmed':
                                            $status_badge = '<span class="badge badge-success">Checked In</span>';
                                            $row_class = 'table-success';
                                            break;
                                        case 'pending':
                                            $status_badge = '<span class="badge badge-warning">Pending Check-In</span>';
                                            $row_class = 'table-warning';
                                            break;
                                        default:
                                            $status_badge = '<span class="badge badge-secondary">Reserved</span>';
                                    }
                                } else {
                                    $status_badge = '<span class="badge badge-secondary">Available</span>';
                                    $row_class = '';
                                }

                                echo "
                                    <tr class=\"{$row_class}\">
                                        <td><strong>Cottage {$cottage_num}</strong></td>
                                        <td>{$customer_name}</td>
                                        <td>{$phone}</td>
                                        <td>
                                            <small>
                                                <div><strong>{$check_in_date}</strong></div>
                                                <div>{$check_in_time}</div>
                                            </small>
                                        </td>
                                        <td>
                                            <small>
                                                <div><i class=\"feather icon-info\" style=\"font-size: 12px;\"></i> N/A</div>
                                            </small>
                                        </td>
                                        <td><span class=\"badge badge-secondary\">{$num_guests} guests</span></td>
                                        <td>{$status_badge}</td>
                                        <td>";
                                
                                if ($has_reservation) {
                                    echo "
                                        <div class=\"dropdown\">
                                            <button class=\"btn btn-sm btn-primary dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
                                                Actions
                                            </button>
                                            <div class=\"dropdown-menu dropdown-menu-right\">
                                                <button type=\"button\" class=\"dropdown-item\" onclick=\"viewCottageReservation('{$customer_name}', '{$cottage_res['email']}', '{$phone}', '{$cottage_num}', {$num_guests}, '{$check_in_date}', '{$check_in_time}', '{$cottage_res['special_requests']}', '{$cottage_res['reservation_status']}')\" data-toggle=\"modal\" data-target=\"#cottageDetailsModal\"><i class=\"feather icon-eye\"></i> View</button>
                                                " . ($cottage_res['reservation_status'] !== 'confirmed' ? "<a href=\"handlers/reservation_update_handler.php?id={$cottage_res['reservation_id']}&status=confirmed\" class=\"dropdown-item\" onclick=\"return confirm('Mark as checked in?')\"><i class=\"feather icon-log-in\"></i> Check In</a>" : "") . "
                                                " . ($cottage_res['reservation_status'] === 'confirmed' ? "<a href=\"handlers/reservation_update_handler.php?id={$cottage_res['reservation_id']}&status=completed\" class=\"dropdown-item\" onclick=\"return confirm('Mark as checked out?')\"><i class=\"feather icon-log-out\"></i> Check Out</a>" : "") . "
                                                <div class=\"dropdown-divider\"></div>
                                                <a href=\"handlers/reservation_update_handler.php?id={$cottage_res['reservation_id']}&status=cancelled\" class=\"dropdown-item text-danger\" onclick=\"return confirm('Cancel this reservation?')\"><i class=\"feather icon-x\"></i> Cancel</a>
                                                <a href=\"handlers/reservation_delete_handler.php?id={$cottage_res['reservation_id']}\" class=\"dropdown-item text-danger\" onclick=\"return confirm('Are you sure you want to permanently delete this reservation? This action cannot be undone.')\"><i class=\"feather icon-trash-2\"></i> Delete</a>
                                            </div>
                                        </div>
                                    ";
                                } else {
                                    echo "<span class=\"text-muted small\">No actions</span>";
                                }
                                
                                echo "
                                        </td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td colspan=\"8\" class=\"text-center text-muted py-4\">No cottages found in the system</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
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

<!-- Cottage Details Modal -->
<div class="modal fade" id="cottageDetailsModal" tabindex="-1" role="dialog" aria-labelledby="cottageDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cottageDetailsModalLabel">Cottage Occupancy Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="cottageDetailsBody">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.10.0/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.10.0/vfs_fonts.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script>
    $(document).ready(function() {
        $('#reservationsTable').DataTable({
            pageLength: 25,
            order: [[5, 'desc']],
            columnDefs: [
                {
                    targets: 8,
                    orderable: false,
                    searchable: false
                }
            ],
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: 'lfrtip'
        });

        $('#cottageOccupancyTable').DataTable({
            pageLength: 25,
            order: [[3, 'desc']],
            columnDefs: [
                {
                    targets: 7,
                    orderable: false,
                    searchable: false
                }
            ],
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: 'lfrtip'
        });
    });

    function viewReservation(name, email, phone, type, guests, date, time, requests, cottageNumber) {
        const detailsBody = document.getElementById('detailsBody');
        const cottageInfo = cottageNumber ? `<p><strong>Cottage:</strong> ${cottageNumber}</p>` : '';
        detailsBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Customer Name:</strong> ${name}</p>
                            <p><strong>Email:</strong> ${email}</p>
                            <p><strong>Phone:</strong> ${phone}</p>
                            <p><strong>Reservation Type:</strong> <span class="badge badge-primary">${type}</span></p>
                            ${cottageInfo}
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

    function viewCottageReservation(name, email, phone, cottage, guests, date, time, requests, status) {
        const detailsBody = document.getElementById('cottageDetailsBody');
        const statusBadge = status === 'confirmed' ? '<span class="badge badge-success">Checked In</span>' : 
                          status === 'pending' ? '<span class="badge badge-warning">Pending Check-In</span>' :
                          status === 'completed' ? '<span class="badge badge-info">Checked Out</span>' :
                          '<span class="badge badge-danger">Cancelled</span>';
        detailsBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Customer Name:</strong> ${name}</p>
                            <p><strong>Email:</strong> ${email || 'N/A'}</p>
                            <p><strong>Phone:</strong> ${phone}</p>
                            <p><strong>Cottage:</strong> <span class="badge badge-info">Cottage ${cottage}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Number of Guests:</strong> ${guests}</p>
                            <p><strong>Check-In Date:</strong> ${date}</p>
                            <p><strong>Check-In Time:</strong> ${time}</p>
                            <p><strong>Status:</strong> ${statusBadge}</p>
                        </div>
                    </div>
                    <hr>
                    <p><strong>Special Requests/Notes:</strong></p>
                    <p>${requests || 'No special requests'}</p>
                `;
    }
</script>