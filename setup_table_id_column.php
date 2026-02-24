<?php
/**
 * Quick Migration: Add table_id to reservations table
 */
session_start();
include 'config/db.php';

$messages = [];

// Check if table_id column exists in reservations
$result = $conn->query("SHOW COLUMNS FROM reservations LIKE 'table_id'");

if ($result && $result->num_rows > 0) {
    $messages[] = ["type" => "info", "text" => "✓ Column 'table_id' already exists in reservations table"];
} else {
    // Add the table_id column
    $sql = "ALTER TABLE reservations ADD COLUMN table_id INT NULL AFTER cottage_id, ADD INDEX idx_table_id (table_id), ADD CONSTRAINT fk_table_id FOREIGN KEY (table_id) REFERENCES availability_tables(id) ON DELETE SET NULL";
    
    if ($conn->query($sql) === TRUE) {
        $messages[] = ["type" => "success", "text" => "✓ Column 'table_id' added to reservations table successfully!"];
    } else {
        // If foreign key fails, try without it
        $sql2 = "ALTER TABLE reservations ADD COLUMN table_id INT NULL AFTER cottage_id, ADD INDEX idx_table_id (table_id)";
        if ($conn->query($sql2) === TRUE) {
            $messages[] = ["type" => "success", "text" => "✓ Column 'table_id' added to reservations table (without foreign key)"];
        } else {
            $messages[] = ["type" => "error", "text" => "✗ Error: " . $conn->error];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .message { padding: 12px; border-radius: 4px; margin-bottom: 10px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        h2 { color: #27ae60; }
        a { color: #27ae60; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Database Migration</h2>
        <?php foreach ($messages as $msg): ?>
            <div class="message <?php echo $msg['type']; ?>">
                <?php echo $msg['text']; ?>
            </div>
        <?php endforeach; ?>
        <hr>
        <p><a href="availability_set.php">← Go to Table Availability</a></p>
    </div>
</body>
</html>
<?php $conn->close(); ?>
