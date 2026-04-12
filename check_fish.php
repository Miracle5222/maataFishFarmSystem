<?php
require 'config/db.php';
$result = $conn->query('SELECT fish_id, name FROM fish_species');
while($row = $result->fetch_assoc()) {
    echo $row['fish_id'] . ' ' . $row['name'] . "\n";
}
?>