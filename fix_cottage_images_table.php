<?php
require 'config/db.php';

// Check if cottage_images table exists and has correct structure
$result = $conn->query("SHOW TABLES LIKE 'cottage_images'");
if ($result->num_rows > 0) {
    echo "cottage_images table exists\n";

    // Check columns
    $result = $conn->query("DESCRIBE cottage_images");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    $required_columns = ['id', 'cottage_id', 'filename', 'is_main', 'created_at'];
    $missing_columns = array_diff($required_columns, $columns);

    if (!empty($missing_columns)) {
        echo "Missing columns: " . implode(', ', $missing_columns) . "\n";
        echo "Dropping and recreating table...\n";

        $conn->query("DROP TABLE cottage_images");

        $create_sql = "CREATE TABLE cottage_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cottage_id INT NOT NULL,
            filename VARCHAR(500) NOT NULL,
            is_main TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        if ($conn->query($create_sql)) {
            echo "Table recreated successfully\n";
        } else {
            echo "Error recreating table: " . $conn->error . "\n";
        }
    } else {
        echo "Table structure is correct\n";
    }
} else {
    echo "cottage_images table does not exist, creating...\n";

    $create_sql = "CREATE TABLE cottage_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cottage_id INT NOT NULL,
        filename VARCHAR(500) NOT NULL,
        is_main TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if ($conn->query($create_sql)) {
        echo "Table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}
?>