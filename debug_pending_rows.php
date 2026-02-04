<?php
require 'config/db.php';

$query = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email, phone, created_at, government_id_image 
                                 FROM customers 
                                 WHERE government_id_verified = 0 AND government_id_image IS NOT NULL
                                 ORDER BY created_at DESC";
$result = $conn->query($query);

if (!$result) {
    echo "Query error: " . $conn->error . "\n";
    exit;
}

echo "Found rows: " . $result->num_rows . "\n\n";

$rownum = 0;
while ($row = $result->fetch_assoc()) {
    $rownum++;
    $name = htmlspecialchars($row['name']);
    $email = htmlspecialchars($row['email']);
    $phone = htmlspecialchars($row['phone']);
    $date = date('M d, Y', strtotime($row['created_at']));
    $image = htmlspecialchars($row['government_id_image']);

    $html = "<tr>\n";
    $html .= "<td><strong>$name</strong></td>\n";
    $html .= "<td>$email</td>\n";
    $html .= "<td>$phone</td>\n";
    $html .= "<td>$date</td>\n";
    $html .= "<td><button class='btn btn-sm btn-info view-id-btn' data-id='".$row['id']."' data-image='$image'>View</button></td>\n";
    $form = "<form method='POST' action='handlers/customer_id_verification.php' style='display:inline;'>";
    $form .= "<input type='hidden' name='customer_id' value='".$row['id']."'>";
    $form .= "<button type='submit' name='action' value='approve' class='btn btn-sm btn-success' onclick=\"return confirm('Verify this customer\\'s ID?')\">Verify</button>";
    $form .= "<button type='submit' name='action' value='reject' class='btn btn-sm btn-danger' onclick=\"return confirm('Reject this ID?\\n\\nThe customer will need to re-submit.')\">Reject</button>";
    $form .= "</form>";
    $html .= "<td>$form</td>\n";
    $html .= "</tr>\n";

    // Count td by parsing
    preg_match_all('/<td\b[^>]*>/i', $html, $matches);
    $tdCount = count($matches[0]);

    echo "Row $rownum TDs: $tdCount\n";
    echo "HTML:\n" . $html . "\n";
}

if ($rownum === 0) echo "No pending rows.\n";
?>