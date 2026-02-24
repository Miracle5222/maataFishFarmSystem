<?php
require 'config/db.php';

// Check and add updated_at column if it doesn't exist
$checkColumn = $conn->query("SHOW COLUMNS FROM customers LIKE 'updated_at'");

if ($checkColumn && $checkColumn->num_rows == 0) {
    echo "<h3>Adding updated_at column...</h3>";
    if ($conn->query("ALTER TABLE customers ADD COLUMN updated_at TIMESTAMP NULL AFTER government_id_verified")) {
        echo "<div style='background:#d4edda; padding:15px; border-radius:5px; margin:20px 0;'>";
        echo "✅ <strong>Success!</strong> The 'updated_at' column has been added to the customers table.<br>";
        echo "Now when customers update their ID image, the system will track when they resubmitted it.<br>";
        echo "The admin verification page will automatically show 'Resubmission' status for updated IDs.";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da; padding:15px; border-radius:5px; margin:20px 0;'>";
        echo "❌ <strong>Error:</strong> " . $conn->error;
        echo "</div>";
    }
} else {
    echo "<div style='background:#cfe2ff; padding:15px; border-radius:5px; margin:20px 0;'>";
    echo "ℹ️ The 'updated_at' column already exists in your customers table.<br>";
    echo "System is ready to track ID resubmissions.";
    echo "</div>";
}

echo "<br><hr><br>";
echo "<h4>Current Status:</h4>";

// Show customers with ID images
$result = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, created_at, updated_at, government_id_verified FROM customers WHERE government_id_image IS NOT NULL ORDER BY id DESC LIMIT 10");

if ($result && $result->num_rows > 0) {
    echo "<table style='border-collapse:collapse; width:100%; margin:20px 0;'>";
    echo "<tr style='background:#f8f9fa;'>";
    echo "<th style='border:1px solid #ddd; padding:10px;'>Customer</th>";
    echo "<th style='border:1px solid #ddd; padding:10px;'>Status</th>";
    echo "<th style='border:1px solid #ddd; padding:10px;'>Created</th>";
    echo "<th style='border:1px solid #ddd; padding:10px;'>Updated</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status = '';
        if ($row['government_id_verified'] == 1) {
            $status = '<span style="background:#d4edda; padding:3px 8px; border-radius:3px;">✓ Verified</span>';
        } elseif (!empty($row['updated_at']) && $row['updated_at'] !== $row['created_at']) {
            $status = '<span style="background:#fff3cd; padding:3px 8px; border-radius:3px;">↻ Resubmission</span>';
        } else {
            $status = '<span style="background:#cfe2ff; padding:3px 8px; border-radius:3px;">⏳ Pending</span>';
        }
        
        echo "<tr>";
        echo "<td style='border:1px solid #ddd; padding:10px;'>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td style='border:1px solid #ddd; padding:10px;'>" . $status . "</td>";
        echo "<td style='border:1px solid #ddd; padding:10px; font-size:12px;'>" . $row['created_at'] . "</td>";
        echo "<td style='border:1px solid #ddd; padding:10px; font-size:12px;'>" . ($row['updated_at'] ?: '—') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<br><br>";
echo "<a href='customer_id_verification.php' style='display:inline-block; padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>← Back to Customer Verification</a>";
?>
