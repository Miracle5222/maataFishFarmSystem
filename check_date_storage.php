<?php
include 'config/db.php';

echo "<h3>Cottages Table Structure:</h3>";
$result = $conn->query('DESCRIBE cottages');
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

echo "<hr>";
echo "<h3>Sample Cottage Data (all columns):</h3>";

$result = $conn->query("SELECT * FROM cottages ORDER BY id DESC LIMIT 5");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    
    // Get column names
    $row = $result->fetch_assoc();
    echo "<tr>";
    foreach ($row as $key => $value) {
        echo "<th>" . htmlspecialchars($key) . "</th>";
    }
    echo "</tr>";
    
    // Print first row
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
    
    // Print remaining rows
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No cottages found";
}
?>
