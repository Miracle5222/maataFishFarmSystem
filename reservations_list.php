<?php
include 'config/db.php';
include 'auth_admin.php';
include 'partials/head.php';
?>
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

        <!-- Filter / Search form -->
        <form method="get" class="form-inline mb-3">
            <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="Search reservation #, name or email" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
            <select name="status" class="form-control form-control-sm mr-2">
                <option value="">All statuses</option>
                <option value="pending" <?php if (($_GET['status'] ?? '') === 'pending') echo 'selected'; ?>>Pending</option>
                <option value="confirmed" <?php if (($_GET['status'] ?? '') === 'confirmed') echo 'selected'; ?>>Confirmed</option>
                <option value="completed" <?php if (($_GET['status'] ?? '') === 'completed') echo 'selected'; ?>>Completed</option>
                <option value="cancelled" <?php if (($_GET['status'] ?? '') === 'cancelled') echo 'selected'; ?>>Cancelled</option>
            </select>
            <input type="date" name="from" class="form-control form-control-sm mr-2" value="<?php echo htmlspecialchars($_GET['from'] ?? ''); ?>">
            <input type="date" name="to" class="form-control form-control-sm mr-2" value="<?php echo htmlspecialchars($_GET['to'] ?? ''); ?>">
            <button class="btn btn-primary btn-sm" type="submit">Filter</button>
            <a href="reservations_list.php" class="btn btn-secondary btn-sm ml-2">Reset</a>
        </form>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="feather icon-check-circle"></i> Reservation status updated successfully
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
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
                        // Build dynamic WHERE clause from filters
                        $q = trim($_GET['q'] ?? '');
                        $status_filter = trim($_GET['status'] ?? '');
                        $from = trim($_GET['from'] ?? '');
                        $to = trim($_GET['to'] ?? '');

                        $where = [];
                        if ($q !== '') {
                            $q_esc = $conn->real_escape_string($q);
                            $where[] = "(r.reservation_number LIKE '%{$q_esc}%' OR c.first_name LIKE '%{$q_esc}%' OR c.last_name LIKE '%{$q_esc}%' OR c.email LIKE '%{$q_esc}%')";
                        }
                        $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
                        if ($status_filter !== '' && in_array($status_filter, $valid_statuses)) {
                            $where[] = "r.status = '" . $conn->real_escape_string($status_filter) . "'";
                        }
                        if ($from !== '') {
                            $from_esc = $conn->real_escape_string($from);
                            $where[] = "r.reservation_date >= '" . $from_esc . "'";
                        }
                        if ($to !== '') {
                            $to_esc = $conn->real_escape_string($to);
                            $where[] = "r.reservation_date <= '" . $to_esc . "'";
                        }

                        $where_sql = '';
                        if (!empty($where)) {
                            $where_sql = 'WHERE ' . implode(' AND ', $where);
                        }

                        $query = "SELECT r.*, c.first_name, c.last_name, c.email, c.phone
                                  FROM reservations r
                                  LEFT JOIN customers c ON r.customer_id = c.id
                                  {$where_sql}
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
                                                <div class=\"btn-group btn-group-sm\" role=\"group\">
                                                    <button type=\"button\" class=\"btn btn-info\" onclick=\"viewReservation('{$customer_name}', '{$reservation['contact_email']}', '{$reservation['contact_phone']}', '{$reservation['reservation_type']}', {$reservation['num_guests']}, '{$reservation['reservation_date']}', '{$reservation['reservation_time']}', '{$reservation['special_requests']}')\" data-toggle=\"modal\" data-target=\"#detailsModal\">View</button>
                                                    <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=confirmed\" class=\"btn btn-success\" onclick=\"return confirm('Confirm this reservation?')\">Confirm</a>
                                                    <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=completed\" class=\"btn btn-info\" onclick=\"return confirm('Mark as completed?')\">Done</a>
                                                    <a href=\"handlers/reservation_update_handler.php?id={$reservation['id']}&status=cancelled\" class=\"btn btn-danger\" onclick=\"return confirm('Cancel this reservation?')\">Cancel</a>
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