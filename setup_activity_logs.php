<?php
require 'config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('admin', 'staff', 'customer') DEFAULT 'admin',
    activity_type ENUM('CREATE', 'EDIT', 'DELETE', 'RESTOCK', 'APPROVE', 'REJECT', 'VIEW') DEFAULT 'EDIT',
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    entity_name VARCHAR(255),
    description TEXT,
    old_values JSON,
    new_values JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    INDEX idx_timestamp (timestamp DESC),
    INDEX idx_user_type (user_type),
    INDEX idx_activity_type (activity_type),
    INDEX idx_entity_type (entity_type)
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ activity_logs table created or already exists<br>";
} else {
    echo "✗ Error creating table: " . $conn->error . "<br>";
}

// Insert sample data for testing
$sample_data = [
    [1, 'admin', 'CREATE', 'product', 1, 'Fish Food', 'Created product: Fish Food - ₱500/kg'],
    [1, 'admin', 'EDIT', 'product', 1, 'Fish Food', 'Updated product: Fish Food - ₱550/kg'],
    [2, 'staff', 'DELETE', 'expense', 5, 'Feed Purchase', 'Deleted expense: Feed Purchase - ₱2000'],
    [1, 'admin', 'CREATE', 'fish_species', 3, 'Tilapia', 'Created fish species: Tilapia - ₱300/kg'],
];

foreach ($sample_data as $data) {
    $user_id = $data[0];
    $user_type = $data[1];
    $activity_type = $data[2];
    $entity_type = $data[3];
    $entity_id = $data[4];
    $entity_name = $data[5];
    $description = $data[6];
    
    $check = $conn->query("SELECT 1 FROM activity_logs WHERE description = '$description' LIMIT 1");
    if ($check && $check->num_rows == 0) {
        $insert_sql = "INSERT INTO activity_logs (user_id, user_type, activity_type, entity_type, entity_id, entity_name, description, timestamp) 
                      VALUES ($user_id, '$user_type', '$activity_type', '$entity_type', $entity_id, '$entity_name', '$description', NOW())";
        if ($conn->query($insert_sql) === TRUE) {
            echo "✓ Sample data inserted<br>";
        } else {
            echo "Note: Sample data may already exist<br>";
        }
    }
}

echo "✓ Setup complete. You can now view <a href='activity_logs.php'>Activity Logs</a>";
$conn->close();
?>
