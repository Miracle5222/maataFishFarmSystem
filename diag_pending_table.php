<?php
// Diagnostic: check pendingTable header vs each row cell count
$url = 'http://localhost/maataFishFarmSystem/customer_id_verification.php';
$html = @file_get_contents($url);
if ($html === false) {
    echo "Failed to fetch page: $url\n";
    exit(1);
}
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();
$xpath = new DOMXPath($dom);
$tables = $xpath->query("//table[@id='pendingTable']");
if ($tables->length === 0) {
    echo "pendingTable not found\n";
    exit(0);
}
$table = $tables->item(0);
$ths = $xpath->query('.//thead//th', $table);
$thCount = $ths->length;
echo "Header th count: $thCount\n";
$rows = $xpath->query('.//tbody//tr', $table);
$bad = 0;
foreach ($rows as $i => $row) {
    $tds = $xpath->query('.//td', $row);
    $tdCount = $tds->length;
    if ($tdCount !== $thCount) {
        $bad++;
        echo "Row " . ($i+1) . " has $tdCount tds (expected $thCount)\n";
        // print row HTML
        $inner = '';
        foreach ($row->childNodes as $child) {
            $inner .= $dom->saveHTML($child);
        }
        echo "Row HTML: \n" . trim($inner) . "\n\n";
    }
}
if ($bad === 0) echo "All rows match header count\n";
else echo "$bad rows mismatch header count\n";
?>