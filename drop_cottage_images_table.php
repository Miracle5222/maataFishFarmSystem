<?php
require 'config/db.php';
$result = $conn->query('DROP TABLE IF EXISTS cottage_images');
if ($result) {
    echo "Table dropped successfully\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>