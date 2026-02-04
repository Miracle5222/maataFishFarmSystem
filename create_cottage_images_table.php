<?php
require 'config/db.php';

// Drop table if exists
$conn->query('DROP TABLE IF EXISTS cottage_images');

$sql = 'CREATE TABLE cottage_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cottage_id INT NOT NULL,
    filename VARCHAR(500) NOT NULL,
    is_main TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)';
echo "Executing: $sql\n";
$result = $conn->query($sql);
if ($result) {
    echo "Table created successfully\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>