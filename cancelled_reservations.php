<?php
include 'config/db.php';
include 'partials/head.php';
?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">

    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Cancelled Reservations</h4>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item active">Cancelled Reservations</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header with-elements">
                <h5 class="card-header-title">Cancelled Reservations</h5>
                <div class="card-header-elements ml-md-auto">
                    <a href="reservations_list.php" class="btn btn-primary btn-sm">
                        <i class="feather icon-arrow-left"></i> Back to All Reservations
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
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
                        $query = "SELECT r.*, c.first_name, c.last_name, c.email, c.phone 
                                 FROM reservations r
                                 LEFT JOIN customers c ON r.customer_id = c.id
                                 WHERE r.status = 'cancelled'
                                 ORDER BY r.reservation_date DESC, r.reservation_time DESC
                                 LIMIT 100";

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
                                                <button type=\"button\" class=\"btn btn-sm btn-info\" onclick=\"viewReservation('{$customer_name}', '{$reservation['contact_email']}', '{$reservation['contact_phone']}', '{$reservation['reservation_type']}', {$reservation['num_guests']}, '{$reservation['reservation_date']}', '{$reservation['reservation_time']}', '{$reservation['special_requests']}')\" data-toggle=\"modal\" data-target=\"#detailsModal\">View Details</button>
                                            </td>
                                        </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center text-muted py-4'>No cancelled reservations found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <!-- [ content ] End -->

</div>
<!-- [ Layout content ] End -->
</div>
<!-- [ Layout wrapper ] End -->
</div>
<!-- [ Page wrapper ] End -->

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

<script>
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

<?php include 'partials/footer.php'; ?>