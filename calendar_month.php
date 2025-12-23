<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Calendar — Monthly Bookings View</h4>
        
        <?php
        require __DIR__ . '/config/db.php';
        
        // Get current month and year
        $current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
        $current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        
        // Validate month/year
        if ($current_month < 1) $current_month = 12;
        if ($current_month > 12) $current_month = 1;
        
        $display_month = str_pad($current_month, 2, '0', STR_PAD_LEFT);
        $month_name = date('F', mktime(0, 0, 0, $current_month, 1));
        
        // Get all reservations for the current month
        $reservations = [];
        $query = 'SELECT r.id, r.reservation_number, r.reservation_date, r.reservation_time, r.reservation_type, r.num_guests, r.status, c.first_name, c.last_name, c.email FROM reservations r JOIN customers c ON r.customer_id = c.id WHERE YEAR(r.reservation_date) = ? AND MONTH(r.reservation_date) = ? ORDER BY r.reservation_date, r.reservation_time';
        
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('ii', $current_year, $current_month);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $date = $row['reservation_date'];
                if (!isset($reservations[$date])) {
                    $reservations[$date] = [];
                }
                $reservations[$date][] = $row;
            }
            $stmt->close();
        }
        
        // Calculate calendar days
        $first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
        $last_day = date('t', $first_day);
        $start_weekday = date('w', $first_day);
        ?>
        
        <div class="card mt-3">
            <div class="card-header with-elements">
                <h5 class="card-header-title"><?php echo $month_name; ?> <?php echo $current_year; ?></h5>
                <div class="card-header-elements d-flex gap-2 ml-4 mb-2" style="gap: 8px;">
                    <a href="?month=<?php echo $current_month == 1 ? 12 : $current_month - 1; ?>&year=<?php echo $current_month == 1 ? $current_year - 1 : $current_year; ?>" class="btn btn-sm btn-outline-primary" style="white-space: nowrap;">
                        <i class="feather icon-chevron-left"></i> Previous
                    </a>
                    <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-sm btn-outline-secondary" style="white-space: nowrap;">Today</a>
                    <a href="?month=<?php echo $current_month == 12 ? 1 : $current_month + 1; ?>&year=<?php echo $current_month == 12 ? $current_year + 1 : $current_year; ?>" class="btn btn-sm btn-outline-primary" style="white-space: nowrap;">
                        Next <i class="feather icon-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="calendar-table table mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th class="text-center">Sun</th>
                                <th class="text-center">Mon</th>
                                <th class="text-center">Tue</th>
                                <th class="text-center">Wed</th>
                                <th class="text-center">Thu</th>
                                <th class="text-center">Fri</th>
                                <th class="text-center">Sat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                // Print empty cells for days before month starts
                                for ($i = 0; $i < $start_weekday; $i++) {
                                    echo '<td class="bg-light"></td>';
                                }
                                
                                // Print calendar days
                                $current_cell = $start_weekday;
                                for ($day = 1; $day <= $last_day; $day++) {
                                    $date_str = sprintf("%04d-%02d-%02d", $current_year, $current_month, $day);
                                    $has_reservations = isset($reservations[$date_str]);
                                    $is_today = ($date_str === date('Y-m-d'));
                                    
                                    echo '<td class="calendar-day ' . ($is_today ? 'today' : '') . ' ' . ($has_reservations ? 'has-bookings' : '') . '" data-date="' . $date_str . '">';
                                    echo '<div class="day-header">' . $day . '</div>';
                                    
                                    if ($has_reservations) {
                                        echo '<div class="reservations">';
                                        $count = 0;
                                        foreach ($reservations[$date_str] as $res) {
                                            if ($count < 3) {
                                                $status_color = '';
                                                switch ($res['status']) {
                                                    case 'confirmed':
                                                        $status_color = '#4caf50';
                                                        break;
                                                    case 'pending':
                                                        $status_color = '#ff9800';
                                                        break;
                                                    case 'cancelled':
                                                        $status_color = '#f44336';
                                                        break;
                                                    default:
                                                        $status_color = '#2196f3';
                                                }
                                                
                                                echo '<div class="reservation-badge" style="background-color: ' . $status_color . ';" title="' . htmlspecialchars($res['first_name'] . ' ' . $res['last_name']) . '" onclick="viewReservation(event, ' . $res['id'] . ')">';
                                                echo htmlspecialchars(substr($res['first_name'], 0, 1) . substr($res['last_name'], 0, 1));
                                                echo '</div>';
                                                $count++;
                                            }
                                        }
                                        if (count($reservations[$date_str]) > 3) {
                                            echo '<div class="reservation-count">+' . (count($reservations[$date_str]) - 3) . '</div>';
                                        }
                                        echo '</div>';
                                    }
                                    
                                    echo '</td>';
                                    
                                    $current_cell++;
                                    if ($current_cell % 7 == 0 && $day < $last_day) {
                                        echo '</tr><tr>';
                                    }
                                }
                                
                                // Fill remaining cells
                                while ($current_cell % 7 != 0) {
                                    echo '<td class="bg-light"></td>';
                                    $current_cell++;
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Reservation Status Legend</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <span class="badge" style="background-color: #4caf50; width: 20px; height: 20px; display: inline-block;"></span>
                                        <strong>Confirmed</strong> - Reservation is confirmed
                                    </li>
                                    <li class="mb-2">
                                        <span class="badge" style="background-color: #ff9800; width: 20px; height: 20px; display: inline-block;"></span>
                                        <strong>Pending</strong> - Awaiting confirmation
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <span class="badge" style="background-color: #f44336; width: 20px; height: 20px; display: inline-block;"></span>
                                        <strong>Cancelled</strong> - Reservation cancelled
                                    </li>
                                    <li class="mb-2">
                                        <span class="badge" style="background-color: #2196f3; width: 20px; height: 20px; display: inline-block;"></span>
                                        <strong>Completed</strong> - Reservation completed
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<!-- Reservation Details Modal -->
<div class="modal fade" id="reservationModal" tabindex="-1" role="dialog" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reservationModalLabel">Reservation Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="reservationModalBody">
                <p class="text-muted">Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="#" id="viewReservationLink" class="btn btn-primary">View Full Details</a>
            </div>
        </div>
    </div>
</div>

<style>
    .calendar-table {
        width: 100%;
    }
    
    .calendar-table td {
        height: 120px;
        vertical-align: top;
        padding: 10px;
        border: 1px solid #e3f2fd;
        position: relative;
    }
    
    .calendar-day {
        background-color: #fafafa;
        transition: background-color 0.3s ease;
    }
    
    .calendar-day:hover {
        background-color: #f5f5f5;
        cursor: pointer;
    }
    
    .calendar-day.today {
        background-color: #e3f2fd;
        border: 2px solid #2196f3;
    }
    
    .calendar-day.has-bookings {
        background-color: #fffde7;
    }
    
    .day-header {
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 8px;
        color: #333;
    }
    
    .reservations {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 8px;
    }
    
    .reservation-badge {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 10px;
        font-weight: bold;
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .reservation-badge:hover {
        transform: scale(1.2);
    }
    
    .reservation-count {
        font-size: 11px;
        color: #666;
        font-weight: bold;
    }
    
    .calendar-table thead th {
        font-weight: 600;
        color: #666;
    }
    
    /* Navigation buttons styling */
    .card-header-elements {
        display: flex;
        align-items: center;
        gap: 8px !important;
    }
    
    .card-header-elements .btn {
        margin: 0 !important;
        padding: 6px 12px !important;
    }
</style>

<script>
// Fetch and display reservation details
function viewReservation(event, reservationId) {
    event.stopPropagation();
    
    fetch('handlers/get_reservation_details.php?id=' + reservationId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const res = data.reservation;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">Reservation Information</h6>
                            <dl class="row">
                                <dt class="col-sm-5">Reservation #:</dt>
                                <dd class="col-sm-7"><strong>${escapeHtml(res.reservation_number)}</strong></dd>
                                
                                <dt class="col-sm-5">Date:</dt>
                                <dd class="col-sm-7">${formatDate(res.reservation_date)}</dd>
                                
                                <dt class="col-sm-5">Time:</dt>
                                <dd class="col-sm-7">${res.reservation_time}</dd>
                                
                                <dt class="col-sm-5">Type:</dt>
                                <dd class="col-sm-7"><span class="badge badge-info">${escapeHtml(res.reservation_type)}</span></dd>
                                
                                <dt class="col-sm-5">Guests:</dt>
                                <dd class="col-sm-7">${res.num_guests}</dd>
                                
                                <dt class="col-sm-5">Status:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge" style="background-color: ${getStatusColor(res.status)}; color: white;">
                                        ${escapeHtml(res.status)}
                                    </span>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">Customer Information</h6>
                            <dl class="row">
                                <dt class="col-sm-5">Name:</dt>
                                <dd class="col-sm-7">${escapeHtml(res.first_name + ' ' + res.last_name)}</dd>
                                
                                <dt class="col-sm-5">Email:</dt>
                                <dd class="col-sm-7"><a href="mailto:${escapeHtml(res.contact_email)}">${escapeHtml(res.contact_email)}</a></dd>
                                
                                <dt class="col-sm-5">Phone:</dt>
                                <dd class="col-sm-7">${escapeHtml(res.contact_phone)}</dd>
                            </dl>
                        </div>
                    </div>
                    ${res.special_requests ? `
                        <div class="mt-3">
                            <h6 class="font-weight-bold">Special Requests</h6>
                            <p>${escapeHtml(res.special_requests)}</p>
                        </div>
                    ` : ''}
                `;
                
                document.getElementById('reservationModalBody').innerHTML = html;
                document.getElementById('viewReservationLink').href = 'reservations_list.php?id=' + res.id;
                
                $('#reservationModal').modal('show');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('reservationModalBody').innerHTML = '<p class="text-danger">Error loading reservation details.</p>';
        });
}

function formatDate(dateStr) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString(undefined, options);
}

function getStatusColor(status) {
    switch(status) {
        case 'confirmed': return '#4caf50';
        case 'pending': return '#ff9800';
        case 'cancelled': return '#f44336';
        case 'completed': return '#2196f3';
        default: return '#999';
    }
}

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
</script>

<?php include 'partials/footer.php'; ?>
