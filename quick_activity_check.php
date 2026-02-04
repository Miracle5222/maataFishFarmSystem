<?php
/**
 * Quick Activity Logging Health Check
 * Run this to verify everything is configured correctly
 */

session_start();
require 'config/db.php';

$checks_passed = 0;
$checks_total = 0;

echo "<h1>🔍 Activity Logging - Quick Health Check</h1>";
echo "<hr>";

// Check 1: Table exists
echo "<h2>Check 1: Database Table</h2>";
$checks_total++;
$check1 = $conn->query("DESCRIBE activity_logs");
if ($check1 && $check1->num_rows > 0) {
    echo "✓ activity_logs table exists with " . $check1->num_rows . " columns<br>";
    $checks_passed++;
    
    // Check for required columns
    $columns = [];
    while ($col = $check1->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
    $required = ['id', 'user_id', 'user_type', 'user_name', 'activity_type', 'entity_type', 'entity_id', 'entity_name', 'timestamp'];
    $missing = array_diff($required, $columns);
    
    if (empty($missing)) {
        echo "✓ All required columns present<br>";
    } else {
        echo "✗ Missing columns: " . implode(', ', $missing) . "<br>";
    }
} else {
    echo "✗ activity_logs table NOT FOUND<br>";
}

// Check 2: Test data exists
echo "<h2>Check 2: Historical Data</h2>";
$checks_total++;
$count = $conn->query("SELECT COUNT(*) as cnt FROM activity_logs");
$row = $count->fetch_assoc();
$total_records = $row['cnt'];

if ($total_records > 0) {
    echo "✓ Table contains $total_records records<br>";
    $checks_passed++;
    
    $recent = $conn->query("SELECT timestamp FROM activity_logs ORDER BY timestamp DESC LIMIT 1");
    if ($r = $recent->fetch_assoc()) {
        $time = date('M d, Y g:ia', strtotime($r['timestamp']));
        echo "  Most recent: $time<br>";
    }
} else {
    echo "✗ Table is empty - no records found<br>";
}

// Check 3: activity_logger.php exists and is valid
echo "<h2>Check 3: Handler File</h2>";
$checks_total++;
if (file_exists('handlers/activity_logger.php')) {
    $content = file_get_contents('handlers/activity_logger.php');
    
    if (strpos($content, 'function logActivity') !== false) {
        echo "✓ activity_logger.php exists with logActivity function<br>";
        
        // Check for correct bind_param
        if (strpos($content, "'isssssisss'") !== false) {
            echo "✓ bind_param type string is CORRECT (isssssisss)<br>";
            $checks_passed++;
        } else if (strpos($content, "'isisssssss'") !== false) {
            echo "✗ bind_param type string is WRONG (isisssssss) - should be isssssisss<br>";
        } else {
            echo "⚠ Could not verify bind_param type string<br>";
        }
    } else {
        echo "✗ logActivity function NOT found<br>";
    }
} else {
    echo "✗ activity_logger.php NOT FOUND<br>";
}

// Check 4: Activity_logs.php queries user_name
echo "<h2>Check 4: Display File</h2>";
$checks_total++;
if (file_exists('activity_logs.php')) {
    $content = file_get_contents('activity_logs.php');
    if (strpos($content, 'al.user_name') !== false) {
        echo "✓ activity_logs.php queries user_name column<br>";
        $checks_passed++;
    } else {
        echo "✗ activity_logs.php does NOT query user_name<br>";
    }
} else {
    echo "✗ activity_logs.php NOT FOUND<br>";
}

// Check 5: Sample handlers are configured
echo "<h2>Check 5: Handler Configuration</h2>";
$checks_total++;
$sample_handlers = [
    'handlers/product_update.php',
    'handlers/fish_update.php',
    'handlers/staff_add_handler.php'
];

$all_good = true;
foreach ($sample_handlers as $handler) {
    if (file_exists($handler)) {
        $content = file_get_contents($handler);
        $has_logger = strpos($content, 'activity_logger.php') !== false;
        $has_call = strpos($content, 'logActivity(') !== false;
        
        if ($has_logger && $has_call) {
            echo "✓ $handler is configured<br>";
        } else {
            echo "✗ $handler is NOT properly configured<br>";
            $all_good = false;
        }
    } else {
        echo "✗ $handler NOT FOUND<br>";
        $all_good = false;
    }
}

if ($all_good) {
    $checks_passed++;
}

// Summary
echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>Checks Passed: $checks_passed / $checks_total</strong></p>";

if ($checks_passed === $checks_total) {
    echo "<p style='color: green; font-size: 18px;'><strong>✓ ALL CHECKS PASSED - SYSTEM READY</strong></p>";
    echo "<p>The activity logging system is properly configured. Try:</p>";
    echo "<ol>";
    echo "<li>Edit a product or fish species</li>";
    echo "<li>Go to Activity Logs page</li>";
    echo "<li>Your activity should appear with your name</li>";
    echo "</ol>";
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>✗ SOME CHECKS FAILED</strong></p>";
    echo "<p>Please review the failures above and check the ACTIVITY_LOGGING_FIX_REPORT.md for details.</p>";
}

echo "<hr>";
echo "<p><a href='test_activity_logging.php'>Run Full Test →</a></p>";

?>
