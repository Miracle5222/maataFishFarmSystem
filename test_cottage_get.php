<?php
include 'config/db.php';

// Test with cottage ID 22 (Cottage Number 4)
$id = 22;

// Get cottage details
$stmt = $conn->prepare('SELECT c.*, COUNT(ci.id) as image_count FROM cottages c LEFT JOIN cottage_images ci ON c.id = ci.cottage_id WHERE c.id = ? GROUP BY c.id');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$cottage = $result->fetch_assoc();
$stmt->close();

echo "<h3>Cottage Data:</h3>";
if ($cottage) {
    echo "<pre>";
    print_r($cottage);
    echo "</pre>";
} else {
    echo "Cottage not found";
    exit;
}

// Get availability records
$avail_stmt = $conn->prepare('SELECT * FROM cottage_availability WHERE cottage_id = ? ORDER BY available_date ASC, available_time_start ASC');
$avail_stmt->bind_param('i', $id);
$avail_stmt->execute();
$avail_result = $avail_stmt->get_result();

echo "<h3>Availability Records:</h3>";
echo "Count: " . $avail_result->num_rows . "<br>";
while ($row = $avail_result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}
$avail_stmt->close();

// Get images
$img_stmt = $conn->prepare('SELECT filename FROM cottage_images WHERE cottage_id = ?');
$img_stmt->bind_param('i', $id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();

echo "<h3>Images:</h3>";
echo "Count: " . $img_result->num_rows . "<br>";
while ($row = $img_result->fetch_assoc()) {
    echo $row['filename'] . "<br>";
}
$img_stmt->close();

echo "<h3>JSON Output:</h3>";
$cottage['availability'] = [];
$cottage['images'] = [];
echo "<pre>";
echo json_encode($cottage, JSON_PRETTY_PRINT);
echo "</pre>";
?>
