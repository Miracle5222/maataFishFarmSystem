<?php
// fix_fish_descriptions.php - Fix fish descriptions that are '0' to empty
require __DIR__ . '/auth_admin.php';
require __DIR__ . '/config/db.php';

echo "<h2>Fixing Fish Descriptions</h2>";

// Update descriptions that are '0' to NULL
$stmt = $conn->prepare("UPDATE fish_species SET description = NULL WHERE description = '0'");
if ($stmt) {
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    echo "<p>Updated $affected fish species descriptions from '0' to NULL.</p>";
} else {
    echo "<p>Error preparing statement: " . $conn->error . "</p>";
}

echo "<p><a href='products_fish.php'>Back to Fish Products</a></p>";
?>