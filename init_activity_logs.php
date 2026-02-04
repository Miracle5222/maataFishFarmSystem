<?php
require 'config/db.php';

echo "<h2>Creating Activity Logs Table</h2>";

// Create the table
$create_table = "CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `user_type` ENUM('admin', 'staff', 'customer') DEFAULT 'admin',
    `activity_type` ENUM('CREATE', 'EDIT', 'DELETE', 'RESTOCK', 'APPROVE', 'REJECT', 'VIEW') DEFAULT 'EDIT',
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT,
    `entity_name` VARCHAR(255),
    `description` TEXT,
    `old_values` JSON,
    `new_values` JSON,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45),
    INDEX `idx_timestamp` (`timestamp` DESC),
    INDEX `idx_user_type` (`user_type`),
    INDEX `idx_activity_type` (`activity_type`),
    INDEX `idx_entity_type` (`entity_type`)
)";

if ($conn->query($create_table) === TRUE) {
    echo "<p>✓ Table created or already exists</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
    exit;
}

// Check current user for sample data
$admin_user = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$admin_id = 1;
if ($admin_user && $admin_user->num_rows > 0) {
    $row = $admin_user->fetch_assoc();
    $admin_id = $row['id'];
}

$staff_user = $conn->query("SELECT id FROM users WHERE role = 'staff' LIMIT 1");
$staff_id = 2;
if ($staff_user && $staff_user->num_rows > 0) {
    $row = $staff_user->fetch_assoc();
    $staff_id = $row['id'];
}

// Clear existing sample data and add fresh data
$conn->query("TRUNCATE TABLE activity_logs");

// Insert sample data
$sample_data = [
    [$admin_id, 'admin', 'CREATE', 'product', 1, 'Premium Fish Feed', 'Created product: Premium Fish Feed - ₱500/kg', date('Y-m-d H:i:s')],
    [$admin_id, 'admin', 'CREATE', 'fish_species', 1, 'Tilapia', 'Created fish species: Tilapia - ₱300/kg', date('Y-m-d H:i:s', time()-3600)],
    [$staff_id, 'staff', 'EDIT', 'product', 1, 'Premium Fish Feed', 'Updated product: Premium Fish Feed - ₱550/kg', date('Y-m-d H:i:s', time()-7200)],
    [$staff_id, 'staff', 'CREATE', 'expense', 1, 'Feed Purchase', 'Created expense: Feed Purchase - labor (₱5000)', date('Y-m-d H:i:s', time()-10800)],
    [$admin_id, 'admin', 'DELETE', 'expense', 1, 'Feed Purchase', 'Deleted expense: Feed Purchase - ₱5000', date('Y-m-d H:i:s', time()-14400)],
    [$admin_id, 'admin', 'APPROVE', 'customer_id_verification', 1, 'John Doe', 'Approved government ID for: John Doe', date('Y-m-d H:i:s', time()-18000)],
    [$staff_id, 'staff', 'RESTOCK', 'product', 2, 'Fish Pellets', 'Restocked: Fish Pellets - 50 kg', date('Y-m-d H:i:s', time()-21600)],
    [$admin_id, 'admin', 'CREATE', 'staff', 5, 'Maria Santos', 'Created staff member: Maria Santos - Position: Manager - Role: manager', date('Y-m-d H:i:s', time()-25200)],
];

foreach ($sample_data as $data) {
    $user_id = $data[0];
    $user_type = $data[1];
    $activity_type = $data[2];
    $entity_type = $data[3];
    $entity_id = $data[4];
    $entity_name = $conn->real_escape_string($data[5]);
    $description = $conn->real_escape_string($data[6]);
    $timestamp = $data[7];
    
    $insert_sql = "INSERT INTO activity_logs (user_id, user_type, activity_type, entity_type, entity_id, entity_name, description, timestamp) 
                  VALUES ($user_id, '$user_type', '$activity_type', '$entity_type', $entity_id, '$entity_name', '$description', '$timestamp')";
    
    if ($conn->query($insert_sql) === TRUE) {
        echo "<p>✓ Inserted: " . $description . "</p>";
    } else {
        echo "<p>✗ Error inserting: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<h3>Verification</h3>";
$count = $conn->query("SELECT COUNT(*) as cnt FROM activity_logs");
if ($count) {
    $row = $count->fetch_assoc();
    echo "<p><strong>Total records in activity_logs: " . $row['cnt'] . "</strong></p>";
}

// Show sample
echo "<h3>Sample Data:</h3>";
$result = $conn->query("SELECT user_id, user_type, activity_type, entity_type, entity_name, timestamp FROM activity_logs ORDER BY timestamp DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>User</th><th>Type</th><th>Activity</th><th>Entity</th><th>Name</th><th>Timestamp</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['user_type'] . "</td>";
        echo "<td>" . $row['activity_type'] . "</td>";
        echo "<td>" . $row['entity_type'] . "</td>";
        echo "<td>" . $row['entity_name'] . "</td>";
        echo "<td>" . $row['timestamp'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><a href='activity_logs.php'>View Activity Logs →</a></p>";
echo "<p><a href='activity_logs.php?user_type=staff&date=" . date('Y-m-d') . "'>View Staff Activities for Today →</a></p>";

$conn->close();
?>
