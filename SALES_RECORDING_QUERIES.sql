# Sales Recording Verification Queries

## Copy-Paste Ready Queries for Testing

### 1. Verify All Services Are Logging

```sql
-- Show count of recorded transactions by service
SELECT 
    CASE entity_type
        WHEN 'orders' THEN '1. Online Orders (Fish + Menu)'
        WHEN 'fish_order' THEN '3. Walk-In Fish Orders'
        WHEN 'menu_order' THEN '4. Direct Menu Orders'
        WHEN 'reservation' THEN '5&6. Cottage Bookings'
        WHEN 'boat_rentals' THEN '7. Boat Rentals'
        WHEN 'entrance_fee' THEN '8. Entrance Fees'
        ELSE entity_type
    END as SERVICE,
    COUNT(*) as TRANSACTIONS,
    MAX(timestamp) as LAST_TRANSACTION
FROM activity_logs
WHERE activity_type = 'CREATE'
GROUP BY entity_type
ORDER BY entity_type;
```

### 2. Most Recent Transactions from Each Service

```sql
-- Last transaction recorded from each service type
SELECT 
    entity_type,
    id,
    entity_id,
    user_type,
    description,
    timestamp
FROM activity_logs
WHERE activity_type = 'CREATE'
AND (
    entity_type IN ('orders', 'fish_order', 'menu_order', 'reservation', 'boat_rentals', 'entrance_fee')
)
ORDER BY timestamp DESC
LIMIT 10;
```

### 3. Verify Online Orders Are Logged (THE FIX)

```sql
-- This should show online customer orders being logged
SELECT 
    al.id,
    al.entity_id as order_id,
    al.entity_name as order_number,
    al.user_type,
    al.description,
    al.new_values,
    al.timestamp
FROM activity_logs al
WHERE al.entity_type = 'orders'
AND al.activity_type = 'CREATE'
AND al.user_type = 'customer'
ORDER BY al.timestamp DESC
LIMIT 10;
```

### 4. Revenue by Service Type (Today)

```sql
-- Daily revenue breakdown by service
SELECT 
    CASE entity_type
        WHEN 'orders' THEN 'Online Orders'
        WHEN 'fish_order' THEN 'Walk-In Fish'
        WHEN 'menu_order' THEN 'Direct Menu'
        WHEN 'reservation' THEN 'Cottage Bookings'
        WHEN 'boat_rentals' THEN 'Boat Rentals'
        WHEN 'entrance_fee' THEN 'Entrance Fees'
        ELSE entity_type
    END as SERVICE,
    COUNT(*) as TRANSACTIONS,
    SUM(CAST(JSON_EXTRACT(new_values, '$.total_amount') AS DECIMAL(10,2))) as TOTAL_REVENUE,
    DATE(timestamp) as DATE
FROM activity_logs
WHERE activity_type = 'CREATE'
AND DATE(timestamp) = CURDATE()
GROUP BY entity_type, DATE(timestamp)
ORDER BY DATE DESC, TOTAL_REVENUE DESC;
```

### 5. Compare Activity Logs vs Actual Tables

```sql
-- Online Orders
SELECT 'orders' as type, COUNT(*) as count FROM orders
UNION ALL
SELECT 'orders (in activity_logs)', COUNT(*) FROM activity_logs WHERE entity_type = 'orders' AND activity_type = 'CREATE'
UNION ALL
-- Fish Orders
SELECT 'fish_order', COUNT(*) FROM fish_orders
UNION ALL
SELECT 'fish_order (in activity_logs)', COUNT(*) FROM activity_logs WHERE entity_type = 'fish_order' AND activity_type = 'CREATE'
UNION ALL
-- Menu Orders
SELECT 'menu_order', COUNT(*) FROM menu_orders
UNION ALL
SELECT 'menu_order (in activity_logs)', COUNT(*) FROM activity_logs WHERE entity_type = 'menu_order' AND activity_type = 'CREATE'
UNION ALL
-- Reservations
SELECT 'reservation', COUNT(*) FROM reservations
UNION ALL
SELECT 'reservation (in activity_logs)', COUNT(*) FROM activity_logs WHERE entity_type = 'reservation' AND activity_type = 'CREATE';
```

### 6. Detailed Activity Log for Specific Time Period

```sql
-- Last 24 hours of all sales
SELECT 
    timestamp,
    CONCAT('[', entity_type, '] ', entity_id) as entity_ref,
    CONCAT(user_name, ' (', user_type, ')') as user,
    LEFT(description, 80) as action,
    CASE 
        WHEN new_values IS NOT NULL THEN 'Has Details'
        ELSE 'No Details'
    END as data_captured
FROM activity_logs
WHERE activity_type = 'CREATE'
AND entity_type IN ('orders', 'fish_order', 'menu_order', 'reservation', 'boat_rentals', 'entrance_fee')
AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY timestamp DESC;
```

### 7. Troubleshooting: Check if activity_logger.php Works

```sql
-- Show recent activity from activity_logger function
SELECT 
    entity_type,
    user_type,
    activity_type,
    COUNT(*) as count,
    MAX(timestamp) as latest
FROM activity_logs
WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY entity_type, user_type, activity_type
ORDER BY timestamp DESC;
```

### 8. Customer Order Tracking

```sql
-- All orders from a specific customer (replace CUSTOMER_ID)
SELECT 
    o.order_number,
    o.total_amount,
    o.status,
    o.created_at,
    al.description,
    al.timestamp
FROM orders o
LEFT JOIN activity_logs al ON al.entity_type = 'orders' 
    AND al.entity_id = o.id 
    AND al.activity_type = 'CREATE'
WHERE o.customer_id = 1  -- REPLACE WITH YOUR CUSTOMER ID
ORDER BY o.created_at DESC;
```

### 9. Admin Sales Summary (This Week)

```sql
-- All sales by admin this week
SELECT 
    al.user_name as admin_name,
    al.entity_type,
    COUNT(*) as transaction_count,
    SUM(CAST(JSON_EXTRACT(al.new_values, '$.total_amount') AS DECIMAL(10,2))) as total_amount,
    DATE(al.timestamp) as date
FROM activity_logs al
WHERE al.user_type = 'admin'
AND al.activity_type = 'CREATE'
AND al.entity_type IN ('fish_order', 'menu_order')
AND WEEK(al.timestamp) = WEEK(NOW())
AND YEAR(al.timestamp) = YEAR(NOW())
GROUP BY al.user_name, al.entity_type, DATE(al.timestamp)
ORDER BY DATE(al.timestamp) DESC, admin_name;
```

### 10. Total Revenue Report

```sql
-- Complete revenue breakdown (all time)
SELECT 
    'Online Orders' as SERVICE,
    COUNT(*) as TX,
    SUM(total_amount) as TOTAL_REVENUE
FROM orders
UNION ALL
SELECT 'Walk-In Fish', COUNT(*), SUM(total_amount) FROM fish_orders
UNION ALL
SELECT 'Direct Menu', COUNT(*), SUM(total_amount) FROM menu_orders
UNION ALL
SELECT 'Cottage Bookings', COUNT(*), SUM(total_amount) FROM reservations WHERE total_amount IS NOT NULL
UNION ALL
SELECT 'Boat Rentals', COUNT(*), SUM(total_amount) FROM boat_rentals
ORDER BY SERVICE;
```

---

## How to Run These Queries

### Using phpMyAdmin:
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select your database
3. Click "SQL" tab
4. Paste query
5. Click "Go"

### Using MySQL Command Line:
```bash
mysql -u root -p maata_fishfarm < query.sql
```

### Using DBeaver or MySQL Workbench:
1. Connect to database
2. Create new query
3. Paste SQL
4. Execute (Ctrl+Enter or Cmd+Enter)

---

## Expected Output After Fix

When you run Query #3 (Verify Online Orders Are Logged), you should now see:

```
| id | order_id | order_number | user_type | description | new_values | timestamp |
|-----|----------|--------------|-----------|-------------|-----------|-----------|
| 12 | 5 | ORD20260316... | customer | Online order #ORD20260316... | {"order_number":"ORD..."... | 2026-03-16 14:30:45 |
| 11 | 4 | ORD20260316... | customer | Online order #ORD20260316... | {"order_number":"ORD..."... | 2026-03-16 14:15:22 |
```

**If you see ZERO rows** = Online orders are not logging (unlikely after fix)
**If you see rows** = ✅ Online orders are properly logging!

---

## Dashboard Validation

After verifying with queries, visit the Dashboard at `/index.php` and check:

1. **Online Fish Revenue** - Should match SUM from online orders with fish items
2. **Online Menu Revenue** - Should match SUM from online orders with product items
3. **Walk-in Fish Revenue** - Should match SUM from fish_orders table
4. **Direct Menu Sales** - Should match SUM from menu_orders table
5. **Total Cottage Revenue** - Should match SUM from reservations
6. **Total Boat Revenue** - Should match SUM from boat_rentals
7. **Total Entrance Revenue** - Should match calculated from entrance_fee activity logs
8. **Combined Sales** - Should equal sum of all above

If all match ✅ = System is working correctly!

---

## Quick Sanity Check (Run This First)

```sql
-- Single query to verify all 8 services
SELECT 
    'Orders Table' as check_point, COUNT(*) as total FROM orders
UNION ALL SELECT 'Activity Logs - Orders', COUNT(*) FROM activity_logs WHERE entity_type = 'orders'
UNION ALL SELECT 'Fish Orders', COUNT(*) FROM fish_orders
UNION ALL SELECT 'Activity Logs - Fish', COUNT(*) FROM activity_logs WHERE entity_type = 'fish_order'
UNION ALL SELECT 'Menu Orders', COUNT(*) FROM menu_orders
UNION ALL SELECT 'Activity Logs - Menu', COUNT(*) FROM activity_logs WHERE entity_type = 'menu_order'
UNION ALL SELECT 'Reservations', COUNT(*) FROM reservations
UNION ALL SELECT 'Activity Logs - Reservations', COUNT(*) FROM activity_logs WHERE entity_type = 'reservation'
UNION ALL SELECT 'Boat Rentals', COUNT(*) FROM boat_rentals
UNION ALL SELECT 'Activity Logs - Boat', COUNT(*) FROM activity_logs WHERE entity_type = 'boat_rentals'
UNION ALL SELECT 'Activity Logs - Entrance Fees', COUNT(*) FROM activity_logs WHERE entity_type = 'entrance_fee';
```

This should show roughly equal counts for data tables and activity_logs entries.
