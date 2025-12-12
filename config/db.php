<?php
// Database configuration - adjust credentials as needed
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'maata';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    // In production, avoid echoing sensitive info
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

?>
