<?php
require 'config/db.php';

echo "<h3>Checking Customers Table Schema</h3>";

// Check if updated_at column exists
$result = $conn->query("SHOW COLUMNS FROM customers LIKE 'updated_at'");

if ($result->num_rows == 0) {
    echo "❌ <strong>updated_at column does NOT exist</strong><br><br>";
    
    echo "<strong>Adding updated_at column...</strong><br>";
    $alter_query = "ALTER TABLE customers ADD COLUMN updated_at TIMESTAMP NULL";
    if ($conn->query($alter_query)) {
        echo "✅ Successfully added 'updated_at' column<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
} else {
    echo "✅ <strong>updated_at column EXISTS</strong><br>";
}

// Show all columns
echo "<br><h4>All Columns in customers table:</h4>";
$result = $conn->query("SHOW COLUMNS FROM customers");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?: 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h4>Sample customer records:</h4>";
$sample = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, created_at, updated_at, government_id_verified, government_id_image FROM customers WHERE government_id_image IS NOT NULL LIMIT 5");

if ($sample && $sample->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Created At</th><th>Updated At</th><th>Verified</th><th>Image</th></tr>";
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . ($row['created_at'] ?: 'NULL') . "</td>";
        echo "<td>" . ($row['updated_at'] ?: 'NULL') . "</td>";
        echo "<td>" . $row['government_id_verified'] . "</td>";
        echo "<td>" . htmlspecialchars($row['government_id_image']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No customers with images found.";
}

echo "<br><br><a href='customer_id_verification.php' class='btn btn-primary'>← Back to Customer ID Verification</a>";
?>
