<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "maata";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if total_amount column exists
$result = $conn->query("SHOW COLUMNS FROM reservations WHERE Field = 'total_amount'");
if ($result && $result->num_rows > 0) {
    echo "✓ Column total_amount already exists in reservations table\n";
} else {
    echo "Adding total_amount column to reservations table...\n";
    if ($conn->query("ALTER TABLE reservations ADD COLUMN total_amount DECIMAL(10, 2) DEFAULT 0") === TRUE) {
        echo "✓ Successfully added total_amount column\n";
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
}

// Verify the column was added
$result = $conn->query("SHOW COLUMNS FROM reservations WHERE Field = 'total_amount'");
if ($result && $result->num_rows > 0) {
    $column = $result->fetch_assoc();
    echo "✓ Column verification successful: " . $column['Field'] . " (" . $column['Type'] . ")\n";
} else {
    echo "✗ Column verification failed\n";
}

$conn->close();
?>
