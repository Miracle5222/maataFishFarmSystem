<?php
$conn = new mysqli('localhost', 'root', '', 'maata');

echo "Cottage Reservation Data:\n";
$result = $conn->query('
    SELECT id, reservation_number, reservation_type, status, total_amount 
    FROM reservations 
    WHERE reservation_type = "cottage"
    ORDER BY id DESC
    LIMIT 5
');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  ID {$row['id']}: {$row['reservation_number']} | Type={$row['reservation_type']} | Status={$row['status']} | Amount={$row['total_amount']}\n";
    }
} else {
    echo "  No results\n";
}

$conn->close();
?>
