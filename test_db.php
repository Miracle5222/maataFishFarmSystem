<?php
require 'config/db.php';
echo "Database connected successfully\n";
$result = $conn->query('SELECT 1');
if ($result) {
    echo "Query executed successfully\n";
} else {
    echo "Query failed: " . $conn->error . "\n";
}
?>