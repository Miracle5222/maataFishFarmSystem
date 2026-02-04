<?php
$url = 'http://localhost/maataFishFarmSystem/customer_id_verification.php';
$html = file_get_contents($url);

// Find pendingTable
if (preg_match('/<table[^>]*id=["\']pendingTable["\'][^>]*>.*?<\/table>/is', $html, $tableMatch)) {
    $tableHtml = $tableMatch[0];
    
    // Count <th> in thead
    preg_match_all('/<th\b/i', $tableHtml, $ths);
    $thCount = count($ths[0]);
    echo "Header <th> count: $thCount\n";
    
    // For each <tr> in tbody, count <td>
    if (preg_match('/<tbody[^>]*>(.*?)<\/tbody>/is', $tableHtml, $tbodyMatch)) {
        $tbody = $tbodyMatch[1];
        $rows = preg_split('/<tr\b[^>]*>/i', $tbody);
        
        $rownum = 0;
        foreach ($rows as $rowContent) {
            if (empty(trim($rowContent))) continue;
            $rownum++;
            
            preg_match_all('/<td\b[^>]*>/i', $rowContent, $tds);
            $tdCount = count($tds[0]);
            
            if ($tdCount !== $thCount) {
                echo "Row $rownum: $tdCount <td> (MISMATCH! expected $thCount)\n";
                // Show first 200 chars
                $preview = htmlspecialchars(substr($rowContent, 0, 200));
                echo "  Preview: $preview\n";
            } else {
                echo "Row $rownum: $tdCount <td> (OK)\n";
            }
        }
    } else {
        echo "No tbody found\n";
    }
} else {
    echo "pendingTable not found in HTML\n";
}
?>