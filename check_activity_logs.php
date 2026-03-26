<?php
$conn = new mysqli('localhost', 'root', '', 'maata');

echo "Activity Logs Count by Type:\n";
$result = $conn->query('SELECT record_type, COUNT(*) as cnt FROM activity_logs GROUP BY record_type');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  • {$row['record_type']}: {$row['cnt']}\n";
    }
}

echo "\nCottage Reservations with Activity Logs:\n";
$result = $conn->query('
    SELECT r.id, r.reservation_number, r.status, a.user_type, a.action
    FROM reservations r
    LEFT JOIN activity_logs a ON a.record_id = r.id AND a.record_type = "reservation"
    WHERE r.reservation_type = "cottage"
    ORDER BY r.id DESC
    LIMIT 10
');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  • Res {$row['id']}: {$row['reservation_number']} | Status: {$row['status']} | Log: " . ($row['user_type'] ? $row['user_type'] : 'NO LOG') . "\n";
    }
} else {
    echo "  • No cottages found\n";
}

echo "\n✓ Check complete\n";
$conn->close();
?>
