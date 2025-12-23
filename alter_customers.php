<?php
require 'config/db.php';

$sql = "ALTER TABLE customers ADD COLUMN customer_type ENUM('online_customer', 'diner') DEFAULT 'online_customer' AFTER municipality";

if ($conn->query($sql)) {
    echo "ALTER TABLE executed successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
