<?php
/**
 * Fix carts table quantity column to support decimal values
 * Changes from INT(11) to DECIMAL(10,2) to support 0.1 - 999.9 kg
 */
$root = __DIR__;
require $root . '/config/db.php';

echo "Migrating carts table quantity column from INT to DECIMAL...\n";

// Check current column type
$check = $conn->query("DESCRIBE carts");
if ($check) {
    while ($row = $check->fetch_assoc()) {
        if ($row['Field'] === 'quantity') {
            echo "Current quantity column type: " . $row['Type'] . "\n";
            if (strpos($row['Type'], 'int') !== false) {
                echo "❌ Currently INTEGER - needs to be DECIMAL\n";
            } else if (strpos($row['Type'], 'DECIMAL') !== false) {
                echo "✓ Already DECIMAL - no migration needed\n";
                exit;
            }
        }
    }
}

// Perform migration
echo "\nExecuting migration...\n";
$sql = "ALTER TABLE carts MODIFY COLUMN quantity DECIMAL(10,2) NOT NULL DEFAULT 1";

if ($conn->query($sql)) {
    echo "✓ Successfully migrated quantity column to DECIMAL(10,2)\n";
    
    // Verify
    $verify = $conn->query("DESCRIBE carts");
    while ($row = $verify->fetch_assoc()) {
        if ($row['Field'] === 'quantity') {
            echo "✓ Verified: quantity is now " . $row['Type'] . "\n";
        }
    }
    echo "\n✓ Migration complete! Decimal quantities (0.1, 0.3, etc.) will now be stored correctly.\n";
} else {
    echo "❌ Migration failed: " . $conn->error . "\n";
    exit(1);
}

$conn->close();
?>
