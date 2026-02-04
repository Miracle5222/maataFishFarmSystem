<?php
// This script simulates adding a cottage through the form to test the date range functionality
include 'config/db.php';

echo "<h3>Testing Cottage Addition with Date Ranges</h3>";

// Simulate form data
$cottage_number = "TestCottage_" . uniqid();
$price = 750.00;
$date_from = "2026-02-01";
$date_to = "2026-02-05";
$start_times = ["08:00:00", "14:00:00"];  // Two time slots
$end_times = ["12:00:00", "18:00:00"];
$status = "available";

echo "Cottage Number: $cottage_number<br>";
echo "Price: $price<br>";
echo "Date Range: $date_from to $date_to<br>";
echo "Time Slots: 08:00-12:00, 14:00-18:00<br><br>";

// Insert cottage
$stmt = $conn->prepare('INSERT INTO cottages (cottage_number, price, available_date, available_date_from, available_date_to, available_time_start, available_time_end, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

if (!$stmt) {
    echo "Prepare failed: " . $conn->error;
    exit;
}

$stmt->bind_param('sdssssss', $cottage_number, $price, $date_from, $date_from, $date_to, $start_times[0], $end_times[0], $status);

if (!$stmt->execute()) {
    echo "Execute failed: " . $stmt->error;
    exit;
}

$cottage_id = $stmt->insert_id;
$stmt->close();

echo "✓ Cottage created with ID: $cottage_id<br>";
echo "  - available_date_from: $date_from<br>";
echo "  - available_date_to: $date_to<br><br>";

// Create availability records for date range and all time slots
$current_date = new DateTime($date_from);
$end_date = new DateTime($date_to);
$end_date->modify('+1 day'); // Include the end date

echo "<h4>Creating Availability Records:</h4>";

// Insert availability records for each date and each time slot
$avail_stmt = $conn->prepare('INSERT INTO cottage_availability (cottage_id, available_date, available_time_start, available_time_end) VALUES (?, ?, ?, ?)');

$count = 0;
while ($current_date < $end_date) {
    $date_str = $current_date->format('Y-m-d');
    
    // Add availability for each time slot
    foreach ($start_times as $index => $start_time) {
        if (!empty($start_time) && !empty($end_times[$index])) {
            $avail_stmt->bind_param('isss', $cottage_id, $date_str, $start_time, $end_times[$index]);
            if ($avail_stmt->execute()) {
                echo "  ✓ $date_str {$start_time} - {$end_times[$index]}<br>";
                $count++;
            } else {
                echo "  ✗ Failed for $date_str {$start_time}: " . $avail_stmt->error . "<br>";
            }
        }
    }
    
    $current_date->modify('+1 day');
}
$avail_stmt->close();

echo "<br>Total availability records created: $count<br><br>";

// Verify the cottage
echo "<h4>Verification - Cottage Data:</h4>";
$result = $conn->query("SELECT id, cottage_number, price, available_date, available_date_from, available_date_to, available_time_start, available_time_end 
                        FROM cottages WHERE id = $cottage_id");

if ($row = $result->fetch_assoc()) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Number</th><th>Price</th><th>Available Date</th><th>Date From</th><th>Date To</th><th>Time Start</th><th>Time End</th></tr>";
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['cottage_number'] . "</td>";
    echo "<td>" . $row['price'] . "</td>";
    echo "<td>" . $row['available_date'] . "</td>";
    echo "<td><strong>" . $row['available_date_from'] . "</strong></td>";
    echo "<td><strong>" . $row['available_date_to'] . "</strong></td>";
    echo "<td>" . $row['available_time_start'] . "</td>";
    echo "<td>" . $row['available_time_end'] . "</td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "Error fetching cottage data";
}

// Verify availability records
echo "<br><h4>Verification - Availability Records:</h4>";
$result = $conn->query("SELECT available_date, available_time_start, available_time_end, status FROM cottage_availability 
                        WHERE cottage_id = $cottage_id 
                        ORDER BY available_date, available_time_start");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Date</th><th>Start Time</th><th>End Time</th><th>Status</th></tr>";
$record_count = 0;
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['available_date'] . "</td>";
    echo "<td>" . $row['available_time_start'] . "</td>";
    echo "<td>" . $row['available_time_end'] . "</td>";
    echo "<td>" . ($row['status'] ?? 'available') . "</td>";
    echo "</tr>";
    $record_count++;
}
echo "</table>";
echo "<br>Total records: $record_count";
?>
