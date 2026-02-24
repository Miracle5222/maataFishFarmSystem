<?php
require 'config/db.php';

// Check if updated_at column exists
$result = $conn->query("SHOW COLUMNS FROM customers LIKE 'updated_at'");
if ($result->num_rows == 0) {
    // Add updated_at column
    $alter_query = "ALTER TABLE customers ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL";
    if ($conn->query($alter_query)) {
        echo "✅ Successfully added 'updated_at' column to customers table<br>";
    } else {
        echo "❌ Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Column 'updated_at' already exists in customers table<br>";
}

echo "<br><a href='customer_id_verification.php'>← Back to Customer ID Verification</a>";
?>
