<?php
require 'config/db.php';
$result = $conn->query('DESCRIBE activity_logs');
echo "Columns in activity_logs table:\n";
while($col = $result->fetch_assoc()) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
?>
