<?php
$conn = new mysqli('localhost','root','','maata');
if ($conn->connect_error) {
    die('connect error: ' . $conn->connect_error);
}
$sql1 = "INSERT INTO reservations (reservation_number, customer_id, reservation_type, num_guests, reservation_date, reservation_time, special_requests, status, contact_phone, contact_email, cottage_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW(), NOW())";
$p1 = @$conn->prepare($sql1);
echo "SQL1 prepare: ";
if ($p1) echo "OK\n"; else echo "FAIL - " . $conn->error . "\n";

$sql2 = "INSERT INTO reservations (reservation_number, customer_id, reservation_type, num_guests, reservation_date, reservation_time, special_requests, status, contact_phone, contact_email, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())";
$p2 = @$conn->prepare($sql2);
echo "SQL2 prepare: ";
if ($p2) echo "OK\n"; else echo "FAIL - " . $conn->error . "\n";

$conn->close();
?>