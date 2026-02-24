<?php
/**
 * Quick Migration: Create availability_tables
 */
session_start();
include 'config/db.php';

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'availability_tables'");

if ($result && $result->num_rows > 0) {
    $msg = "✓ Table 'availability_tables' already exists";
    $status = "success";
} else {
    // Create table
    $sql = "CREATE TABLE availability_tables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_name VARCHAR(100) NOT NULL UNIQUE,
        capacity INT NOT NULL DEFAULT 1,
        notes TEXT,
        status ENUM('available', 'not available') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        $msg = "✓ Table 'availability_tables' created successfully!";
        $status = "success";
    } else {
        $msg = "✗ Error: " . $conn->error;
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; }
        .error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px; }
        h2 { margin-top: 0; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Database Setup</h2>
        <div class="<?php echo $status; ?>">
            <?php echo $msg; ?>
        </div>
        <hr>
        <p><a href="availability_set.php">← Go to Table Availability</a></p>
    </div>
</body>
</html>
<?php $conn->close(); ?>
