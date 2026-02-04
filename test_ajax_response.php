<?php
// This page simulates what happens when you click Edit on Cottage 4
include 'config/db.php';

// Simulate the AJAX handler
header('Content-Type: application/json');

$id = 22; // Cottage 4

// Get cottage details
$stmt = $conn->prepare('SELECT c.*, COUNT(ci.id) as image_count FROM cottages c LEFT JOIN cottage_images ci ON c.id = ci.cottage_id WHERE c.id = ? GROUP BY c.id');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$cottage = $result->fetch_assoc();
$stmt->close();

if (!$cottage) {
    echo json_encode(['error' => 'Cottage not found']);
    exit;
}

// Get availability records
$avail_stmt = $conn->prepare('SELECT * FROM cottage_availability WHERE cottage_id = ? ORDER BY available_date ASC, available_time_start ASC');
$avail_stmt->bind_param('i', $id);
$avail_stmt->execute();
$avail_result = $avail_stmt->get_result();

$availability = [];
while ($row = $avail_result->fetch_assoc()) {
    $availability[] = $row;
}
$avail_stmt->close();

// Get images
$img_stmt = $conn->prepare('SELECT filename FROM cottage_images WHERE cottage_id = ?');
$img_stmt->bind_param('i', $id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();

$images = [];
while ($row = $img_result->fetch_assoc()) {
    $images[] = $row['filename'];
}
$img_stmt->close();

$cottage['availability'] = $availability;
$cottage['images'] = $images;

// Count unique time slots
$timeSlots = [];
foreach ($availability as $slot) {
    $key = $slot['available_time_start'] . '-' . $slot['available_time_end'];
    if (!isset($timeSlots[$key])) {
        $timeSlots[$key] = $slot;
    }
}

echo "=== AJAX Response Simulation ===\n\n";
echo "Cottage ID: " . $cottage['id'] . "\n";
echo "Cottage Number: " . $cottage['cottage_number'] . "\n";
echo "Date Range: " . $cottage['available_date_from'] . " to " . $cottage['available_date_to'] . "\n";
echo "First time slot in cottages table: " . $cottage['available_time_start'] . " to " . $cottage['available_time_end'] . "\n";
echo "\nAvailability records count: " . count($availability) . "\n";
echo "Unique time slots found: " . count($timeSlots) . "\n";
echo "\nUnique time slots:\n";
foreach ($timeSlots as $key => $slot) {
    echo "  - " . $slot['available_time_start'] . " to " . $slot['available_time_end'] . "\n";
}

echo "\n\nFull JSON response:\n";
echo json_encode($cottage, JSON_PRETTY_PRINT);
?>
