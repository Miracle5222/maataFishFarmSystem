<?php
require 'config/db.php';

echo "Fish Order Revenue Report - Online vs Walk-In\n";
echo str_repeat("=", 70) . "\n\n";

// Online fish orders (from orders table with is_manual = 0)
$online_query = "
    SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt 
    FROM orders 
    WHERE is_manual = 0
";
$online_result = $conn->query($online_query);
$online_row = $online_result->fetch_assoc();
$online_revenue = (float)($online_row['total'] ?? 0);
$online_count = (int)($online_row['cnt'] ?? 0);

// Walk-in fish orders (from fish_orders table)
$walkin_query = "
    SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt 
    FROM fish_orders 
    WHERE status = 'paid'
";
$walkin_result = $conn->query($walkin_query);
$walkin_row = $walkin_result->fetch_assoc();
$walkin_revenue = (float)($walkin_row['total'] ?? 0);
$walkin_count = (int)($walkin_row['cnt'] ?? 0);

$total_revenue = $online_revenue + $walkin_revenue;
$total_count = $online_count + $walkin_count;

echo "Online Fish Orders:\n";
echo "  Total: ₱" . number_format($online_revenue, 2) . "\n";
echo "  Count: {$online_count} orders\n\n";

echo "Walk-In Fish Orders:\n";
echo "  Total: ₱" . number_format($walkin_revenue, 2) . "\n";
echo "  Count: {$walkin_count} orders\n\n";

echo str_repeat("-", 70) . "\n";
echo "Total Fish Revenue: ₱" . number_format($total_revenue, 2) . "\n";
echo "Total Orders: {$total_count}\n";

$conn->close();
?>
