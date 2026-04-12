<?php
require 'config/db.php';

$query = "
    SELECT * FROM (
        -- Online order items from orders
        SELECT 
            CONCAT('FO-', oi.id) as transaction_id,
            o.order_number as reference_number,
            CONCAT(c.first_name, ' ', c.last_name) as customer_name,
            COALESCE(f.name, p.name) as item_name,
            oi.quantity,
            oi.unit_price,
            oi.subtotal,
            o.created_at as transaction_date,
            o.status,
            CASE WHEN f.fish_id IS NOT NULL THEN 'fish_order' ELSE 'menu_order' END as transaction_type,
            'online' as source
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN customers c ON o.customer_id = c.id
        LEFT JOIN fish_species f ON oi.product_id = f.fish_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.status IN ('paid', 'completed')
        
        UNION ALL
        
        -- Walk-in fish orders
        SELECT 
            CONCAT('WF-', fo.id) as transaction_id,
            fo.order_number as reference_number,
            COALESCE(fo.customer_name, 'Walk-in Customer') as customer_name,
            'Walk-in Fish Order' as item_name,
            1 as quantity,
            fo.total_amount as unit_price,
            fo.total_amount as subtotal,
            fo.created_at as transaction_date,
            fo.status,
            'fish_order' as transaction_type,
            'walkin' as source
        FROM fish_orders fo
        WHERE fo.status = 'paid'
        
        UNION ALL
        
        -- Direct menu orders
        SELECT 
            CONCAT('MO-', moi.id) as transaction_id,
            mo.order_number as reference_number,
            'Direct/Admin Order' as customer_name,
            COALESCE(f.name, p.name) as item_name,
            moi.quantity,
            moi.unit_price,
            moi.subtotal,
            mo.created_at as transaction_date,
            mo.status,
            'menu_order' as transaction_type,
            'walkin' as source
        FROM menu_order_items moi
        JOIN menu_orders mo ON moi.menu_order_id = mo.id
        LEFT JOIN fish_species f ON (moi.item_type = 'fish' AND moi.item_id = f.fish_id)
        LEFT JOIN products p ON (moi.item_type = 'product' AND moi.item_id = p.id)
        WHERE mo.status IN ('paid', 'completed')
        
        UNION ALL
        
        -- Cottage Reservations
        SELECT 
            CONCAT('CR-', r.id) as transaction_id,
            r.reservation_number as reference_number,
            COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Walk-in Customer') as customer_name,
            CONCAT('Cottage ', cot.cottage_number) as item_name,
            1 as quantity,
            r.total_amount as unit_price,
            r.total_amount as subtotal,
            r.created_at as transaction_date,
            r.status,
            'cottage_reservation' as transaction_type,
            CASE WHEN r.is_manual = 0 THEN 'online' ELSE 'walkin' END as source
        FROM reservations r
        LEFT JOIN customers c ON r.customer_id = c.id
        JOIN cottages cot ON r.cottage_id = cot.id
        WHERE r.reservation_type = 'cottage' AND r.status = 'completed'
        
        UNION ALL
        
        -- Boat Rentals
        SELECT 
            CONCAT('BR-', br.id) as transaction_id,
            'Boat Rental' as reference_number,
            COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Walk-in Customer') as customer_name,
            CONCAT('Boat Rental - ', br.boat_name) as item_name,
            1 as quantity,
            br.total_amount as unit_price,
            br.total_amount as subtotal,
            br.created_at as transaction_date,
            br.status,
            'boat_rental' as transaction_type,
            'online' as source
        FROM boat_rentals br
        LEFT JOIN customers c ON br.customer_id = c.id
        WHERE br.status = 'completed'
    ) t
    ORDER BY t.transaction_date DESC, t.transaction_id ASC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    $count = 0;
    while ($r = $res->fetch_assoc()) {
        $count++;
        echo "ID: " . $r['transaction_id'] . " Type: " . $r['transaction_type'] . " Source: " . $r['source'] . "\n";
    }
    echo "Total transactions: $count\n";
    $stmt->close();
} else {
    echo "Query prepare failed: " . $conn->error . "\n";
}
?>