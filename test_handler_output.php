<?php
// Simulate the AJAX request to test what the handler returns
$_GET['action'] = 'get';
$_GET['id'] = '22';

// Change to handlers directory to mimic the request
chdir('handlers');
ob_start();

// Include and execute the handler
include 'cottage_handler.php';

$output = ob_get_clean();
echo $output;
?>
