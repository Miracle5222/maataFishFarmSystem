<?php
require 'config/db.php';

// Check if cottages table exists
$result = $conn->query('SHOW TABLES LIKE "cottages"');
if ($result->num_rows > 0) {
    echo "cottages table exists\n";
    
    // Check cottage_images table
    $result2 = $conn->query('SHOW TABLES LIKE "cottage_images"');
    if ($result2->num_rows > 0) {
        echo "cottage_images table exists\n";
        
        // Show structure
        $result3 = $conn->query('DESCRIBE cottage_images');
        if ($result3) {
            echo "cottage_images structure:\n";
            while ($row = $result3->fetch_assoc()) {
                echo $row['Field'] . ' - ' . $row['Type'] . "\n";
            }
        }
    } else {
        echo "cottage_images table does not exist\n";
    }
} else {
    echo "cottages table does not exist\n";
}
?>