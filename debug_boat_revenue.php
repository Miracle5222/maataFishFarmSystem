<?php
require 'config/db.php';
echo 'Checking boat_rentals table:\n';
$stmt = $conn->query('SELECT id, status, boat_name, total_amount, created_at FROM boat_rentals ORDER BY id DESC');
while ($row = $stmt->fetch_assoc()) {
    echo 'ID ' . $row['id'] . ': ' . $row['status'] . ' - ' . $row['boat_name'] . ' - ₱' . number_format($row['total_amount'], 2) . ' - ' . $row['created_at'] . '\n';
}

echo '\nChecking dashboard boat rental calculation:\n';
$stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM boat_rentals WHERE status = "completed"');
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
echo 'Completed rentals: Count=' . $res['cnt'] . ', Total=₱' . number_format($res['total'], 2) . '\n';

echo '\nChecking all boat rentals by status:\n';
$stmt = $conn->query('SELECT status, COUNT(*) as cnt, SUM(total_amount) as total FROM boat_rentals GROUP BY status');
while ($row = $stmt->fetch_assoc()) {
    echo $row['status'] . ': Count=' . $row['cnt'] . ', Total=₱' . number_format($row['total'], 2) . '\n';
}
?>