<?php
include 'config/db.php';

echo "<h3>Cottages Table Structure:</h3>";
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

echo "<hr>";
echo "<h3>Sample Cottage Records:</h3>";
$cottage_result = $conn->query("SELECT id, cottage_number, price, available_date, available_date_from, available_date_to FROM cottages LIMIT 5");

if ($cottage_result && $cottage_result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Number</th><th>Price</th><th>Available Date</th><th>Date From</th><th>Date To</th></tr>";
    while ($row = $cottage_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['cottage_number']) . "</td>";
        echo "<td>₱" . number_format($row['price'], 2) . "</td>";
        echo "<td>" . $row['available_date'] . "</td>";
        echo "<td>" . ($row['available_date_from'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['available_date_to'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No cottages found.";
}
?>
