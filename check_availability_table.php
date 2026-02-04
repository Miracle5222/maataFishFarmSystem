<?php
include 'config/db.php';

echo "Cottage Availability Table Structure:<br>";
$result = $conn->query('DESCRIBE cottage_availability');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . '<br>';
}

echo "<hr>";

// Check if status column exists
$check = $conn->query("SHOW COLUMNS FROM cottage_availability LIKE 'status'");
if ($check->num_rows == 0) {
    echo "Adding status column to cottage_availability...<br>";
    if ($conn->query("ALTER TABLE cottage_availability ADD COLUMN status ENUM('available', 'booked') DEFAULT 'available'")) {
        echo "✓ Status column added<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
} else {
    echo "Status column already exists<br>";
}

echo "<hr>";
echo "Updated Structure:<br>";
$result = $conn->query('DESCRIBE cottage_availability');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . '<br>';
}
?>
