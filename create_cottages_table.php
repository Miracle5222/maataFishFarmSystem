<?php
require 'config/db.php';

$sql = 'CREATE TABLE IF NOT EXISTS cottages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cottage_number VARCHAR(50) NOT NULL UNIQUE,
    price DECIMAL(10,2) NOT NULL,
    status ENUM("available", "unavailable") DEFAULT "available",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)';

echo "Creating cottages table...\n";
$result = $conn->query($sql);
if ($result) {
    echo "Cottages table created successfully\n";
} else {
    echo "Error creating cottages table: " . $conn->error . "\n";
}
?>