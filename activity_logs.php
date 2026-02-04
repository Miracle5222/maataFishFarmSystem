<?php
require_once 'config/db.php';
include 'auth_admin.php';
include 'partials/head.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<style>
    .dataTables_wrapper { padding: 15px 0; }
    .dataTables_filter { margin-bottom: 15px; }
    .dataTables_info { padding-top: 15px; }
    .print-header, .print-footer {
        display: none;
    }
    @media print {
        .dataTables_filter,
        .dataTables_paginate,
        .dataTables_info,
        .dataTables_length,
        .breadcrumb,
        .print-btn,
        .btn,
        .navbar,
        .sidenav {
            display: none !important;
        }
        .card {
            box-shadow: none;
            border: none;
        }
        .table { margin: 0; }
        body { background: white; }
        .layout-content { margin: 0; padding: 0; }
        .container-fluid { padding: 0; }
        h4 { display: block; margin: 20px 0 10px 0; font-size: 18px; }
        .table-responsive { overflow: visible; }
        .table td, .table th { padding: 8px 5px; font-size: 12px; }
        .print-header, .print-footer {
            display: block !important;
        }
    }
</style>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="print-header" style="text-align: center; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                <img src="assets/img/maataLogo.png" alt="Maata Logo" style="height: 60px; margin-right: 15px;">
                <div>
                    <h2 style="margin: 0; color: #27ae60;">Maata Fish Farm</h2>
                    <p style="margin: 5px 0; font-size: 14px;">Quality Aquaculture Products</p>
                </div>
            </div>
            <div style="font-size: 12px; color: #666;">
                <p><strong>Contact:</strong> 09661337498 | <strong>Address:</strong> New Basak, Dumingag, Zamboanga del Sur</p>
                <p><strong>Email:</strong> admin@gmail.com | <strong>Website:</strong> https://maatafishfarm.gt.tc/</p>
            </div>
            <hr style="border: 1px solid #27ae60; margin: 15px 0;">
            <h3 style="margin: 10px 0; color: #27ae60;">Activity Logs</h3>
        </div>

        <div class="print-btn" style="margin-bottom: 15px;">
            <button class="btn btn-primary" onclick="window.print()"><i class="feather icon-printer"></i> Print</button>
        </div>
      
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item"><a href="reports_transactions.php">Reports</a></li>
                <li class="breadcrumb-item active">Activity Logs</li>
            </ol>
        </div>



        <!-- Activity Logs Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover" id="activityTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Timestamp</th>
                            <th>Person Involved</th>
                            <th>What Changed</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = "WHERE 1=1";
                        $params = [];
                        $types = "";
                        
                        // Apply filters from URL parameters
                        if (!empty($_GET['activity_type'])) {
                            $where .= " AND al.activity_type = ?";
                            $params[] = $_GET['activity_type'];
                            $types .= "s";
                        }
                        
                        if (!empty($_GET['entity_type'])) {
                            $where .= " AND al.entity_type = ?";
                            $params[] = $_GET['entity_type'];
                            $types .= "s";
                        }
                        
                        if (!empty($_GET['user_type'])) {
                            $where .= " AND al.user_type = ?";
                            $params[] = $_GET['user_type'];
                            $types .= "s";
                        }
                        
                        if (!empty($_GET['date'])) {
                            $where .= " AND DATE(al.timestamp) = ?";
                            $params[] = $_GET['date'];
                            $types .= "s";
                        }
                        
                        // Simple query without subselects
                        $query = "SELECT 
                                    al.id, 
                                    al.user_id, 
                                    al.user_type, 
                                    al.user_name,
                                    al.activity_type, 
                                    al.entity_type, 
                                    al.entity_name, 
                                    al.description, 
                                    al.timestamp
                                 FROM activity_logs al
                                 $where
                                 ORDER BY al.timestamp DESC 
                                 LIMIT 1000";
                        
                        $result = null;
                        if (!empty($params)) {
                            $stmt = $conn->prepare($query);
                            if ($stmt) {
                                $stmt->bind_param($types, ...$params);
                                $stmt->execute();
                                $result = $stmt->get_result();
                            }
                        } else {
                            $result = $conn->query($query);
                        }
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $badge_color = 'secondary';
                                switch($row['activity_type']) {
                                    case 'EDIT': $badge_color = 'info'; break;
                                    case 'DELETE': $badge_color = 'danger'; break;
                                    case 'CREATE': $badge_color = 'success'; break;
                                    case 'RESTOCK': $badge_color = 'warning'; break;
                                    case 'APPROVE': $badge_color = 'success'; break;
                                    case 'REJECT': $badge_color = 'danger'; break;
                                }
                                
                                // Get user name from stored column, fallback to lookup
                                $person_name = $row['user_name'] ?: ('User #' . $row['user_id']);
                                
                                // Fallback: if user_name is empty, try to get it from database
                                if (empty($row['user_name'])) {
                                    if ($row['user_type'] === 'admin' || $row['user_type'] === 'staff') {
                                        // Try staff table first
                                        $user_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM staff WHERE user_id = ? LIMIT 1");
                                        if ($user_stmt) {
                                            $user_stmt->bind_param('i', $row['user_id']);
                                            $user_stmt->execute();
                                            $user_result = $user_stmt->get_result();
                                            if ($user_row = $user_result->fetch_assoc()) {
                                                $person_name = $user_row['name'];
                                            }
                                            $user_stmt->close();
                                        }
                                        // If not found in staff, try users table
                                        if ($person_name === 'User #' . $row['user_id']) {
                                            $user_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id = ? LIMIT 1");
                                            if ($user_stmt) {
                                                $user_stmt->bind_param('i', $row['user_id']);
                                                $user_stmt->execute();
                                                $user_result = $user_stmt->get_result();
                                                if ($user_row = $user_result->fetch_assoc()) {
                                                    $person_name = $user_row['name'];
                                                }
                                                $user_stmt->close();
                                            }
                                        }
                                    } elseif ($row['user_type'] === 'customer') {
                                        $cust_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM customers WHERE id = ? LIMIT 1");
                                        if ($cust_stmt) {
                                            $cust_stmt->bind_param('i', $row['user_id']);
                                            $cust_stmt->execute();
                                            $cust_result = $cust_stmt->get_result();
                                            if ($cust_row = $cust_result->fetch_assoc()) {
                                                $person_name = $cust_row['name'];
                                            }
                                            $cust_stmt->close();
                                        }
                                    }
                                }
                                
                                // Format person display - show only name if found, otherwise show role
                                if (!empty($person_name) && $person_name !== 'User #' . $row['user_id']) {
                                    $person = htmlspecialchars($person_name);
                                } else {
                                    $person = htmlspecialchars(ucfirst($row['user_type']));
                                }
                                $what_changed = htmlspecialchars(ucfirst(str_replace('_', ' ', $row['entity_type'])) . ': ' . $row['entity_name']);
                                // Show full description (no truncation)
                                $description = htmlspecialchars($row['description']);
                                $timestamp = date('M d, Y g:ia', strtotime($row['timestamp']));
                                
                                $id = intval($row['id']);

                                echo "
                                    <tr>
                                        <td><small>$timestamp</small></td>
                                        <td><small>$person</small></td>
                                        <td><small>$what_changed</small></td>
                                        <td><small>$description</small></td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center text-muted py-4'>No activity logs found</td></tr>";
                        }
                        
                        // Check if we have results for filtering/export
                        if (!$result || $result->num_rows === 0) {
                            // Reset query to count total without filters
                            $total_count = $conn->query("SELECT COUNT(*) as cnt FROM activity_logs")->fetch_assoc()['cnt'];
                            if ($total_count > 0) {
                                echo "<tr><td colspan='4' class='text-center text-muted py-2' style='font-size: 12px;'>Total records in system: $total_count</td></tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->

    <?php include 'partials/footer.php'; ?>

<!-- Activity Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailsContent">
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#activityTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
        "language": {
            "search": "Search logs:",
            "lengthMenu": "Show _MENU_ entries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ logs"
        }
    });
});

function showDetails(logId) {
    fetch('handlers/get_activity_details.php?id=' + logId)
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error, status = ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                document.getElementById('detailsContent').innerHTML = '<p class="text-danger"><strong>Error:</strong> ' + escapeHtml(data.error) + '</p>';
                $('#detailsModal').modal('show');
                return;
            }
            
            // Format person display: show only name if available, otherwise capitalized role
            let personDisplay = data.person_name ? `${data.person_name}` : (data.user_type ? (data.user_type.charAt(0).toUpperCase() + data.user_type.slice(1)) : 'User');
            
            let html = `
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Activity Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Activity Type:</strong></p>
                                <p class="text-muted"><span class="badge badge-primary">${escapeHtml(data.activity_type)}</span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Timestamp:</strong></p>
                                <p class="text-muted">${escapeHtml(data.timestamp)}</p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Performed By:</strong></p>
                                <p class="text-muted">${escapeHtml(personDisplay)}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>IP Address:</strong></p>
                                <p class="text-muted">${escapeHtml(data.ip_address || 'N/A')}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Entity Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Entity Type:</strong></p>
                                <p class="text-muted">${escapeHtml(data.entity_type)}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Entity Name:</strong></p>
                                <p class="text-muted">${escapeHtml(data.entity_name)}</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1"><strong>Entity ID:</strong></p>
                            <p class="text-muted">${escapeHtml(data.entity_id)}</p>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Change Details</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-3"><strong>Description:</strong></p>
                        <p class="text-muted">${escapeHtml(data.description)}</p>
                    </div>
                </div>
            `;
            
            if (data.old_values && Object.keys(data.old_values).length > 0) {
                html += `<div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Previous Values</h6>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0" style="background-color: #f8f9fa; padding: 10px; border-radius: 4px;">${escapeHtml(JSON.stringify(data.old_values, null, 2))}</pre>
                    </div>
                </div>`;
            }
            
            if (data.new_values && Object.keys(data.new_values).length > 0) {
                html += `<div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">New Values</h6>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0" style="background-color: #f8f9fa; padding: 10px; border-radius: 4px;">${escapeHtml(JSON.stringify(data.new_values, null, 2))}</pre>
                    </div>
                </div>`;
            }
            
            document.getElementById('detailsContent').innerHTML = html;
            $('#detailsModal').modal('show');
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('detailsContent').innerHTML = '<p class="text-danger"><strong>Error loading details:</strong> ' + escapeHtml(error.message) + '</p>';
            $('#detailsModal').modal('show');
        });
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to capitalize first letter
function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>
