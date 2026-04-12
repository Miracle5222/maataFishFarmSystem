<?php
require 'config/db.php';

echo 'Orders with status paid: ';
$result = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'paid'");
echo $result->fetch_assoc()['cnt'] . "\n";

echo 'Fish orders with status paid: ';
$result = $conn->query("SELECT COUNT(*) as cnt FROM fish_orders WHERE status = 'paid'");
echo $result->fetch_assoc()['cnt'] . "\n";

echo 'Menu orders with status paid: ';
$result = $conn->query("SELECT COUNT(*) as cnt FROM menu_orders WHERE status = 'paid'");
echo $result->fetch_assoc()['cnt'] . "\n";

echo 'Reservations with status completed: ';
$result = $conn->query("SELECT COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND status = 'completed'");
echo $result->fetch_assoc()['cnt'] . "\n";

echo 'Boat rentals with status completed: ';
$result = $conn->query("SELECT COUNT(*) as cnt FROM boat_rentals WHERE status = 'completed'");
echo $result->fetch_assoc()['cnt'] . "\n";

echo 'Activity logs for entrance_fee: ';
$result = $conn->query("SELECT COUNT(*) as cnt FROM activity_logs WHERE entity_type = 'entrance_fee' AND activity_type = 'CREATE'");
echo $result->fetch_assoc()['cnt'] . "\n";

// Check order_items
echo 'Order items count: ';
$result = $conn->query("SELECT COUNT(*) as cnt FROM order_items");
echo $result->fetch_assoc()['cnt'] . "\n";

// Check menu_order_items
echo 'Menu order items count: ';
$result = $conn->query("SELECT COUNT(*) as cnt FROM menu_order_items");
echo $result->fetch_assoc()['cnt'] . "\n";
?>