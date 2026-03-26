<?php
require 'config/db.php';

echo "Cottage Revenue Report - Separated by Source\n";
echo str_repeat("=", 70) . "\n\n";

// Online cottage revenue (from client/booking.php?type=cottage)
$online_query = "
    SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt 
    FROM reservations 
    WHERE reservation_type = 'cottage' 
    AND status = 'completed'
    AND is_manual = 0
";
$online_result = $conn->query($online_query);
$online_row = $online_result->fetch_assoc();
$online_revenue = (float)($online_row['total'] ?? 0);
$online_count = (int)($online_row['cnt'] ?? 0);

// Walk-in/manual cottage revenue (from manual_cottage_reservation.php)
$walkin_query = "
    SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt 
    FROM reservations 
    WHERE reservation_type = 'cottage' 
    AND status = 'completed'
    AND is_manual = 1
";
$walkin_result = $conn->query($walkin_query);
$walkin_row = $walkin_result->fetch_assoc();
$walkin_revenue = (float)($walkin_row['total'] ?? 0);
$walkin_count = (int)($walkin_row['cnt'] ?? 0);

$total_revenue = $online_revenue + $walkin_revenue;
$total_count = $online_count + $walkin_count;

echo "Online Cottage Revenue:\n";
echo "  Total: ₱" . number_format($online_revenue, 2) . "\n";
echo "  Count: {$online_count} reservations\n\n";

echo "Walk-In/Manual Cottage Revenue:\n";
echo "  Total: ₱" . number_format($walkin_revenue, 2) . "\n";
echo "  Count: {$walkin_count} reservations\n\n";

echo str_repeat("-", 70) . "\n";
echo "Total Cottage Revenue: ₱" . number_format($total_revenue, 2) . "\n";
echo "Total Reservations: {$total_count}\n";

$conn->close();
?>
