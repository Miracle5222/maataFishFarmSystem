<?php
require 'config/db.php';
$result = $conn->query('SHOW TABLES LIKE "cottage_images"');
if ($result->num_rows > 0) {
    echo "Table exists\n";
} else {
    echo "Table does not exist\n";
}
?>