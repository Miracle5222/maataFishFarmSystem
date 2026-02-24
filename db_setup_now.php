<?php
/**
 * Direct Database Setup - Creates availability_tables immediately
 */
include 'config/db.php';

$message = '';
$status = 'error';

try {
    // Check if table exists
    $checkResult = $conn->query("SHOW TABLES LIKE 'availability_tables'");
    
    if ($checkResult && $checkResult->num_rows > 0) {
        $message = "✓ Table 'availability_tables' already exists!";
        $status = 'success';
    } else {
        // Create the table
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
            $message = "✓ Table 'availability_tables' created successfully!";
            $status = 'success';
        } else {
            $message = "✗ Error creating table: " . $conn->error;
            $status = 'error';
        }
    }
    
    // Also add table_id column to reservations if it doesn't exist
    $checkCol = $conn->query("SHOW COLUMNS FROM reservations LIKE 'table_id'");
    
    if (!$checkCol || $checkCol->num_rows === 0) {
        $alterSql = "ALTER TABLE reservations ADD COLUMN table_id INT NULL AFTER cottage_id";
        if ($conn->query($alterSql) === TRUE) {
            $message .= "<br>✓ Column 'table_id' added to reservations table!";
        } else {
            // If it fails, it's okay - column might already exist
            if (strpos($conn->error, 'Duplicate column') === false) {
                $message .= "<br>⚠ Could not add table_id column: " . $conn->error;
            }
        }
    }
    
} catch (Exception $e) {
    $message = "✗ Exception: " . $e->getMessage();
    $status = 'error';
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            text-align: center;
        }
        h1 { color: #27ae60; margin-top: 0; }
        .message {
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 16px;
            line-height: 1.6;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .buttons {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        a, button {
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #27ae60;
            color: white;
        }
        .btn-primary:hover {
            background: #229954;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .info {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            color: #0c5460;
            margin-top: 20px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍽️ Database Setup</h1>
        
        <div class="message <?php echo $status; ?>">
            <?php echo $message; ?>
        </div>

        <div class="info">
            <strong>What was done:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Created/verified <code>availability_tables</code> table</li>
                <li>Added <code>table_id</code> column to reservations</li>
                <li>System is now ready for table availability</li>
            </ul>
        </div>

        <div class="buttons">
            <a href="availability_set.php" class="btn-primary">Create Table</a>
            <a href="availability_check.php" class="btn-primary">View Tables</a>
            <a href="setup_table_availability.php" class="btn-secondary">Setup Guide</a>
        </div>
    </div>
</body>
</html>
