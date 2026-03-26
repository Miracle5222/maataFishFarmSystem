<?php
$conn = new mysqli('localhost', 'root', '', 'maata');

echo "Reservation Types:\n";
$result = $conn->query('SELECT DISTINCT reservation_type, COUNT(*) as cnt FROM reservations GROUP BY reservation_type');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  • {$row['reservation_type']}: {$row['cnt']}\n";
    }
} else {
    echo "  • No reservations\n";
}

echo "\nSample Reservations:\n";
$result = $conn->query('SELECT id, reservation_number, reservation_type, status FROM reservations LIMIT 10');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  • ID {$row['id']}: {$row['reservation_number']} | Type: " . ($row['reservation_type'] ?: 'NULL') . " | Status: {$row['status']}\n";
    }
}

echo "\nActivity Logs Sample:\n";
$result = $conn->query('SELECT record_id, record_type, user_type, action FROM activity_logs LIMIT 10');
if ($result) {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        echo "  • Record {$row['record_id']}: Record Type: {$row['record_type']} | User: {$row['user_type']} | Action: {$row['action']}\n";
        $count++;
    }
    if ($count === 0) echo "  • No activity logs\n";
}

$conn->close();
?>
