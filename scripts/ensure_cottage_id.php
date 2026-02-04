<?php
// scripts/ensure_cottage_id.php
require __DIR__ . '/../config/db.php';

echo "Checking reservations table for cottage_id...\n";
$res = $conn->query("SHOW COLUMNS FROM reservations LIKE 'cottage_id'");
if ($res === false) {
    echo "SHOW COLUMNS failed: " . $conn->error . "\n";
    exit(1);
}

if ($res->num_rows == 0) {
    echo "cottage_id not found — adding column...\n";
    $alter = $conn->query("ALTER TABLE reservations ADD COLUMN cottage_id INT NULL AFTER contact_email");
    if ($alter) {
        echo "ALTER succeeded: cottage_id added.\n";
    } else {
        echo "ALTER failed: " . $conn->error . "\n";
        exit(1);
    }
} else {
    echo "cottage_id already exists.\n";
}

echo "Current reservations columns:\n";
$res2 = $conn->query('DESCRIBE reservations');
if ($res2 === false) {
    echo "DESCRIBE failed: " . $conn->error . "\n";
    exit(1);
}
while ($row = $res2->fetch_assoc()) {
    echo $row['Field'] . "\t" . $row['Type'] . "\n";
}

echo "Done.\n";

?>