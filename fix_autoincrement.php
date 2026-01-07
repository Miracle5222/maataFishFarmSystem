<?php
require 'config/db.php';

echo "Fixing customers table AUTO_INCREMENT with foreign key constraints...<br><br>";

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS=0");
echo "✓ Disabled foreign key checks<br>";

// Find and delete any rows with id=0
$check = $conn->query("SELECT id FROM customers WHERE id = 0");
if ($check && $check->num_rows > 0) {
    $conn->query("DELETE FROM customers WHERE id = 0");
    echo "✓ Deleted rows with id=0<br>";
}

// Get the max id
$result = $conn->query("SELECT MAX(id) as max_id FROM customers");
$row = $result->fetch_assoc();
$max_id = $row['max_id'] ? intval($row['max_id']) + 1 : 1;

// Alter the table to set AUTO_INCREMENT
if ($conn->query("ALTER TABLE `customers` AUTO_INCREMENT = $max_id")) {
    echo "✓ Set AUTO_INCREMENT to $max_id<br>";
} else {
    echo "✗ Error setting AUTO_INCREMENT: " . $conn->error . "<br>";
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");
echo "✓ Re-enabled foreign key checks<br>";

echo "<br><strong>Fix complete!</strong> You can now register.<br>";
echo '<a href="client/register.php">Go to Registration</a>';
?>
