<?php
// handlers/expenses_fetch.php - returns JSON row for given id
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','manager','admin'])) {
    echo json_encode(['success'=>false,'error'=>'Access denied']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid id']); exit; }

$stmt = $conn->prepare('SELECT id, amount, currency, transaction_date, description, category, subcategory, payment_method, vendor, location, status, receipt_available, receipt_image_path, notes FROM expenses WHERE id = ? LIMIT 1');
if (!$stmt) { echo json_encode(['success'=>false,'error'=>'DB prepare error']); exit; }
$stmt->bind_param('i',$id);
if (!$stmt->execute()) { echo json_encode(['success'=>false,'error'=>'DB execute error']); $stmt->close(); exit; }

if (method_exists($stmt,'get_result')){
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
} else {
    $stmt->bind_result($f_id, $f_amount, $f_currency, $f_transaction_date, $f_description, $f_category, $f_subcategory, $f_payment_method, $f_vendor, $f_location, $f_status, $f_receipt_available, $f_receipt_image_path, $f_notes);
    $row = $stmt->fetch() ? [
        'id'=>$f_id,'amount'=>$f_amount,'currency'=>$f_currency,'transaction_date'=>$f_transaction_date,'description'=>$f_description,'category'=>$f_category,'subcategory'=>$f_subcategory,'payment_method'=>$f_payment_method,'vendor'=>$f_vendor,'location'=>$f_location,'status'=>$f_status,'receipt_available'=>$f_receipt_available,'receipt_image_path'=>$f_receipt_image_path,'notes'=>$f_notes
    ] : null;
}
$stmt->close();
if (!$row) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }

// cast some types
$row['id'] = (int)$row['id'];
$row['amount'] = (float)$row['amount'];
$row['receipt_available'] = !empty($row['receipt_available']) ? 1 : 0;

echo json_encode(['success'=>true,'row'=>$row]);
