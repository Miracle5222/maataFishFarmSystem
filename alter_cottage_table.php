<?php
include 'config/db.php';

echo "<h3>Adding date range columns to cottages table...</h3>";

// Check and add available_date_from
$check_from = $conn->query("SHOW COLUMNS FROM cottages LIKE 'available_date_from'");
if ($check_from->num_rows == 0) {
    echo "Adding available_date_from column...<br>";
    if ($conn->query("ALTER TABLE cottages ADD COLUMN available_date_from DATE AFTER available_date")) {
        echo "✓ available_date_from added successfully<br>";
    } else {
        echo "✗ Error adding available_date_from: " . $conn->error . "<br>";
    }
} else {
    echo "• available_date_from already exists<br>";
}

// Check and add available_date_to
$check_to = $conn->query("SHOW COLUMNS FROM cottages LIKE 'available_date_to'");
if ($check_to->num_rows == 0) {
    echo "Adding available_date_to column...<br>";
    if ($conn->query("ALTER TABLE cottages ADD COLUMN available_date_to DATE AFTER available_date_from")) {
        echo "✓ available_date_to added successfully<br>";
    } else {
        echo "✗ Error adding available_date_to: " . $conn->error . "<br>";
    }
} else {
    echo "• available_date_to already exists<br>";
}

echo "<hr>";
echo "<h3>Updated Cottages Table Structure:</h3>";
$result = $conn->query("DESCRIBE cottages");

if ($result) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}
?>
