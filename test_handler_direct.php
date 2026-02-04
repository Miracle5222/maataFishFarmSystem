<?php
// Direct test without needing to be logged in
// This simulates what the browser should receive when it calls the AJAX handler

$_GET['action'] = 'get';
$_GET['id'] = 22;

// Skip auth for testing
include 'config/db.php';

// Manually run the handler logic
$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => 'Invalid cottage ID']);
    exit;
}

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

echo "Test Results for Cottage 22:\n";
echo "==============================\n\n";

echo "1. Database Query Results:\n";
echo "   - Cottage Found: " . ($cottage ? "YES" : "NO") . "\n";
echo "   - Cottage ID: " . $cottage['id'] . "\n";
echo "   - Cottage Number: " . $cottage['cottage_number'] . "\n";
echo "   - Availability Records: " . count($availability) . "\n\n";

echo "2. Unique Time Slots:\n";
$timeSlots = [];
foreach ($availability as $slot) {
    $key = $slot['available_time_start'] . '-' . $slot['available_time_end'];
    if (!isset($timeSlots[$key])) {
        $timeSlots[$key] = true;
        echo "   - " . $slot['available_time_start'] . " to " . $slot['available_time_end'] . "\n";
    }
}
echo "   Total Unique: " . count($timeSlots) . "\n\n";

echo "3. JSON Response Preview:\n";
$cottage['availability'] = $availability;
$response = json_encode($cottage);
echo "   Valid JSON: " . (json_last_error() === JSON_ERROR_NONE ? "YES" : "NO") . "\n";
echo "   Response Length: " . strlen($response) . " bytes\n";
echo "   First 200 chars: " . substr($response, 0, 200) . "\n\n";

echo "4. JavaScript Simulation:\n";
echo "   Parsing JSON in JavaScript...\n";

// Simulate what JavaScript would do
$js_code = "
var data = " . $response . ";
console.log('Availability records:', data.availability.length);
var timeSlots = {};
data.availability.forEach(function(slot) {
    var key = slot.available_time_start + '-' + slot.available_time_end;
    if (!timeSlots[key]) {
        timeSlots[key] = {
            start: slot.available_time_start,
            end: slot.available_time_end
        };
    }
});
console.log('Unique time slots found:', Object.keys(timeSlots).length);
console.log('Time slot keys:', Object.keys(timeSlots));
";

echo "   This code should log:\n";
echo "   - Availability records: " . count($availability) . "\n";
echo "   - Unique time slots found: " . count($timeSlots) . "\n";
echo "   - Time slot keys: " . implode(", ", array_keys($timeSlots)) . "\n";
?>
