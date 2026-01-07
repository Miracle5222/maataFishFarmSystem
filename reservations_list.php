<?php
include 'config/db.php';
include 'auth_admin.php';
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
                        // Fetch all reservations - filtering done by DataTables
                        $query = "SELECT r.*, c.first_name, c.last_name, c.email, c.phone
                                  FROM reservations r
                                  LEFT JOIN customers c ON r.customer_id = c.id
                                  ORDER BY r.reservation_date DESC, r.reservation_time DESC";

                        $result = $conn->query($query);

                        if ($result && $result->num_rows > 0) {
                            while ($reservation = $result->fetch_assoc()) {
                                $customer_name = $reservation['first_name'] . ' ' . $reservation['last_name'];
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
                                            <td>{$reservation['reservation_time']}</td>
                                            <td>{$status_badge}</td>
                                            <td>
                                                <div class=\"dropdown\">
                                                    <button class=\"btn btn-sm btn-primary dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
                                                        Actions
                                                    </button>
                                                    <div class=\"dropdown-menu dropdown-menu-right\">
                                                        <button type=\"button\" class=\"dropdown-item\" onclick=\"viewReservation('{$customer_name}', '{$reservation['contact_email']}', '{$reservation['contact_phone']}', '{$reservation['reservation_type']}', {$reservation['num_guests']}, '{$reservation['reservation_date']}', '{$reservation['reservation_time']}', '{$reservation['special_requests']}')\" data-toggle=\"modal\" data-target=\"#detailsModal\"><i class=\"feather icon-eye\"></i> View</button>
                                                        <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=confirmed\" class=\"dropdown-item\" onclick=\"return confirm('Confirm this reservation?')\"><i class=\"feather icon-check\"></i> Confirm</a>
                                                        <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=completed\" class=\"dropdown-item\" onclick=\"return confirm('Mark as completed?')\"><i class=\"feather icon-check-circle\"></i> Done</a>
                                                        <div class=\"dropdown-divider\"></div>
                                                        <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=cancelled\" class=\"dropdown-item text-danger\" onclick=\"return confirm('Cancel this reservation?')\"><i class=\"feather icon-x\"></i> Cancel</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr
                                ";
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center text-muted py-4'>No reservations found</td></tr>";
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
    });

    function viewReservation(name, email, phone, type, guests, date, time, requests) {
        const detailsBody = document.getElementById('detailsBody');
        detailsBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Customer Name:</strong> ${name}</p>
                            <p><strong>Email:</strong> ${email}</p>
                            <p><strong>Phone:</strong> ${phone}</p>
                            <p><strong>Reservation Type:</strong> <span class="badge badge-primary">${type}</span></p>
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
</script>