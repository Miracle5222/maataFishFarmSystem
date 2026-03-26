<?php
require 'config/db.php';

echo "Backfilling is_manual flag for existing cottage reservations...\n";
echo str_repeat("=", 60) . "\n";

// Check how many reservations need to be updated
$check_query = "SELECT COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 0";
$check_result = $conn->query($check_query);
$check_row = $check_result->fetch_assoc();
$pending_count = $check_row['cnt'] ?? 0;

if ($pending_count == 0) {
    echo "✓ All cottage reservations already have is_manual flag set.\n";
} else {
    echo "Found {$pending_count} cottage reservations to backfill.\n";
    echo "Setting all existing cottage reservations to is_manual = 1 (walk-in/manual)...\n\n";
    
    // Update all cottage reservations to is_manual = 1
    $update_query = "UPDATE reservations SET is_manual = 1 WHERE reservation_type = 'cottage' AND is_manual = 0";
    
    if ($conn->query($update_query)) {
        $affected = $conn->affected_rows;
        echo "✓ Updated {$affected} cottage reservations.\n";
        
        // Show breakdown
        echo "\nBreakdown:\n";
        $online_check = $conn->query("SELECT COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 0");
        $online_count = $online_check->fetch_assoc()['cnt'] ?? 0;
        
        $walkin_check = $conn->query("SELECT COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 1");
        $walkin_count = $walkin_check->fetch_assoc()['cnt'] ?? 0;
        
        echo "  Online Cottage Reservations (is_manual = 0): {$online_count}\n";
        echo "  Walk-In/Manual Cottage Reservations (is_manual = 1): {$walkin_count}\n";
    } else {
        echo "✗ Error updating reservations: " . $conn->error . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Note: If any of the existing reservations actually came from online bookings,\n";
echo "you'll need to manually update their is_manual flag to 0.\n";

$conn->close();
?>
