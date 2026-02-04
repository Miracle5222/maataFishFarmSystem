<?php
include 'config/db.php';

// Add 2 more test time slots for Cottage 4 to simulate what should happen after editing
$id = 22;

// First, let's manually add some additional time slots to the database
$additional_slots = [
    ['14:00:00', '16:00:00'],
    ['16:00:00', '18:00:00']
];

$date_from = '2026-01-28';
$date_to = '2026-01-31';

// Create all dates in range
$current_date = new DateTime($date_from);
$end_date = new DateTime($date_to);
$end_date->modify('+1 day');

$dates = [];
while ($current_date < $end_date) {
    $dates[] = $current_date->format('Y-m-d');
    $current_date->modify('+1 day');
}

// Insert additional slots for each date
$stmt = $conn->prepare('INSERT INTO cottage_availability (cottage_id, available_date, available_time_start, available_time_end) VALUES (?, ?, ?, ?)');

foreach ($dates as $date) {
    foreach ($additional_slots as $slot) {
        $start = $slot[0];
        $end = $slot[1];
        $stmt->bind_param('isss', $id, $date, $start, $end);
        $stmt->execute();
    }
}
$stmt->close();

echo "Added additional time slots to Cottage 4\n";

// Now verify
$result = $conn->query("SELECT DISTINCT available_time_start, available_time_end FROM cottage_availability WHERE cottage_id = 22 ORDER BY available_time_start");

echo "\nNow Cottage 4 has these time slots:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['available_time_start'] . " to " . $row['available_time_end'] . "\n";
}
?>
