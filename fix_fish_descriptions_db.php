<?php
// fix_fish_descriptions_db.php - Set all '0' descriptions in fish_species to NULL
require __DIR__ . '/config/db.php';

$sql = "UPDATE fish_species SET description = NULL WHERE description = '0' OR description = 0";
if ($conn->query($sql)) {
    echo "Fixed fish_species descriptions: set all '0' to NULL.";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>