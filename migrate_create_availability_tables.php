<?php
/**
 * Migration: Create availability_tables table
 * 
 * This migration creates a table to store dining table information with their capacity
 * and availability settings.
 */

include 'config/db.php';

try {
    // Check if table already exists
    $result = $conn->query("SHOW TABLES LIKE 'availability_tables'");
    
    if ($result && $result->num_rows > 0) {
        echo "<div style='padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin: 20px;'>";
        echo "<strong>ℹ Note:</strong> The 'availability_tables' table already exists. Migration skipped.";
        echo "</div>";
    } else {
        // Create the table
        $create_sql = "
        CREATE TABLE IF NOT EXISTS availability_tables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100) NOT NULL UNIQUE,
            capacity INT NOT NULL DEFAULT 1,
            notes TEXT,
            status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($conn->query($create_sql) === TRUE) {
            echo "<div style='padding: 20px; background: #d4edda; border: 1px solid #28a745; border-radius: 4px; margin: 20px;'>";
            echo "<strong>✓ Success:</strong> 'availability_tables' table created successfully!<br>";
            echo "Columns: id, table_name, capacity, notes, status, created_at, updated_at";
            echo "</div>";
        } else {
            echo "<div style='padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px;'>";
            echo "<strong>✗ Error:</strong> " . htmlspecialchars($conn->error);
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px;'>";
    echo "<strong>✗ Exception:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Migration: Create Availability Tables</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Migration: Availability Tables</h1>
        <p>This migration script creates the <code>availability_tables</code> table for storing dining table information.</p>
        
        <hr>
        
        <h2>Table Structure</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">Column</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">Type</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px; border: 1px solid #dee2e6;"><code>id</code></td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">INT AUTO_INCREMENT</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">Primary key</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #dee2e6;"><code>table_name</code></td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">VARCHAR(100) UNIQUE NOT NULL</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">Unique table name/identifier</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #dee2e6;"><code>capacity</code></td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">INT (1-100)</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">Maximum guest capacity</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #dee2e6;"><code>notes</code></td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">TEXT</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">Optional notes about the table</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #dee2e6;"><code>status</code></td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">ENUM('active', 'inactive', 'maintenance')</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">Table status</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #dee2e6;"><code>created_at</code></td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">TIMESTAMP</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">Record creation time</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #dee2e6;"><code>updated_at</code></td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">TIMESTAMP</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">Last update time</td>
                </tr>
            </tbody>
        </table>
        
        <hr>
        
        <p>If the table wasn't created, you can manually run this SQL:</p>
        <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow: auto;">
CREATE TABLE availability_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 1,
    notes TEXT,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        </pre>
    </div>
</body>
</html>
