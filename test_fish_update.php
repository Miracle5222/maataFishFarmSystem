<?php
// test_fish_update.php - Test the fish update handler
require 'config/db.php';

// Simulate POST data
$_POST = [
    'id' => 1, // Change to an actual fish_id
    'name' => 'Test Fish',
    'local_name' => 'Test Local',
    'price_per_kg' => 100.00,
    'stock' => 10,
    'harvest_schedule' => 'Daily',
    'description' => 'Test description',
    'status' => 'available'
];

// Include the handler (but it will exit after processing)
ob_start();
include 'handlers/fish_update.php';
$output = ob_get_clean();

echo "Output: " . $output . "\n";
?>