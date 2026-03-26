<?php
session_start();

include 'db.php';

// Get all completed cottage reservations to recalculate amounts
$query = "SELECT r.id, c.id as cottage_id, c.price, r.total_amount FROM reservations r 
          JOIN cottages c ON r.cottage_id = c.id 
          WHERE r.status = 'completed' AND r.type = 'cottage' AND r.total_amount > 0 
          ORDER BY r.id";

$result = $conn->query($query);
$updated_count = 0;
$total_old = 0;
$total_new = 0;

if ($result && $result->num_rows > 0) {
    echo "<h3>Correcting Cottage Reservation Amounts (Flat Rate per Stay)</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Reservation ID</th><th>Cottage ID</th><th>Old Amount</th><th>New Amount (Flat Rate)</th><th>Status</th></tr>";

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $cottage_price = floatval($row['price']);
        $old_amount = floatval($row['total_amount']);
        $new_amount = $cottage_price; // Flat rate, no multiplier

        $total_old += $old_amount;
        $total_new += $new_amount;

        // Update the reservation with corrected amount
        $update = "UPDATE reservations SET total_amount = ? WHERE id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param('di', $new_amount, $id);

        if ($stmt->execute()) {
            echo "<tr>";
            echo "<td>{$id}</td>";
            echo "<td>{$row['cottage_id']}</td>";
            echo "<td>₱" . number_format($old_amount, 2) . "</td>";
            echo "<td>₱" . number_format($new_amount, 2) . "</td>";
            echo "<td style='color: green;'><strong>✓ Updated</strong></td>";
            echo "</tr>";
            $updated_count++;
        } else {
            echo "<tr>";
            echo "<td>{$id}</td>";
            echo "<td>{$row['cottage_id']}</td>";
            echo "<td>₱" . number_format($old_amount, 2) . "</td>";
            echo "<td>₱" . number_format($new_amount, 2) . "</td>";
            echo "<td style='color: red;'><strong>✗ Failed</strong></td>";
            echo "</tr>";
        }
        $stmt->close();
    }

    echo "</table>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Reservations Updated: {$updated_count}</li>";
    echo "<li>Total Old Amount: ₱" . number_format($total_old, 2) . "</li>";
    echo "<li>Total New Amount: ₱" . number_format($total_new, 2) . "</li>";
    echo "<li>Total Reduction: ₱" . number_format($total_old - $total_new, 2) . "</li>";
    echo "</ul>";
} else {
    echo "<p>No completed cottage reservations found to update.</p>";
}

$conn->close();
?>
