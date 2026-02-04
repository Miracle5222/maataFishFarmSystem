# Staff Name Display Fix - Documentation

## Problem
Staff member names were showing as blank in the Activity Logs page when staff members made system activities. Admin names displayed correctly.

## Root Cause
The issue was likely due to staff members not having proper `user_id` values in the `staff` table. When a staff member logs an activity, the system stores their `user_id` from the `users` table. However, if the `staff` table record doesn't have a matching `user_id` value, the lookup query fails and returns no name.

## Solution Implemented

### 1. Improved Query Strategy (activity_logs.php and get_activity_details.php)
Changed from separate sequential queries to a single JOINed query that:
- **Tries staff table first**: Uses `CONCAT(first_name, last_name)` from the staff table
- **Falls back to users table**: Uses `full_name` if staff table has no data
- **Final fallback**: Uses `username` if both above are empty

**New Query:**
```sql
SELECT 
    COALESCE(NULLIF(CONCAT(TRIM(s.first_name), ' ', TRIM(s.last_name)), '  '), '') as staff_name,
    u.full_name,
    u.username
FROM users u
LEFT JOIN staff s ON s.user_id = u.id
WHERE u.id = ?
LIMIT 1
```

This ensures:
- Works even if staff table has NULL/empty name fields
- Works if staff record doesn't exist
- Works if staff record has NULL user_id (fallback to users table)
- Multiple sources of name data (staff first/last, users full_name, username)

### 2. Migration Script (migrate_staff_user_id.php)
Created a one-time migration script that:
- Finds all staff records with NULL or 0 `user_id`
- Matches them to users records by email address
- Updates the staff table with correct `user_id` values

**How to run:**
1. Navigate to: `http://localhost/maataFishFarmSystem/migrate_staff_user_id.php`
2. Script will display:
   - List of staff records with missing user_id
   - Migration progress
   - Summary of updates
   - Final status of all staff records

### 3. Debug Script (debug_staff_names.php)
Created for troubleshooting:
- Shows staff table data and structure
- Shows users table data
- Tests lookup queries
- Displays recent staff activities in activity logs

**How to use:**
1. Navigate to: `http://localhost/maataFishFarmSystem/debug_staff_names.php`
2. Review tables and test results

## Files Modified

1. **activity_logs.php** (Lines 165-210)
   - Updated staff/admin name lookup logic
   - Changed to use JOINed query
   - Added better fallback chain

2. **handlers/get_activity_details.php** (Lines 41-91)
   - Same improvements as activity_logs.php
   - For the activity detail modal popup

## How Staff Names Are Resolved (New Logic)

For **admin** and **staff** user types:
1. Look up user in `users` table by `user_id`
2. LEFT JOIN with `staff` table to get staff name fields
3. Priority order for display:
   - Staff table name (if non-empty)
   - Users table full_name (if non-empty)  
   - Users table username (fallback)

For **customer** user types:
1. Look up in `customers` table by `user_id` (customer id)
2. Use concatenated first_name and last_name

## Testing Steps

1. **Run migration script** (if needed):
   - Go to migrate_staff_user_id.php
   - Verify all staff have user_id populated

2. **Test admin activity**:
   - Log in as admin
   - Make any change (edit fish, add product, etc.)
   - Go to Activity Logs
   - Verify admin name displays correctly

3. **Test staff activity**:
   - Log in as staff member
   - Make any change
   - Go to Activity Logs (as admin)
   - Verify staff member's actual name displays (not blank)

4. **Test activity modal**:
   - Click "View Details" on any activity
   - Verify person name displays in modal

## Expected Results

### Before Fix
- Admin updates: Shows "Admin"
- Staff updates: Shows blank or nothing

### After Fix
- Admin updates: Shows admin's full name (or username if name not set)
- Staff updates: Shows staff member's first and last name from staff table, or full_name from users table

## Additional Notes

- The system now handles multiple edge cases:
  - Staff names with NULL first_name or last_name
  - Staff records without user_id set
  - Users without matching staff records
  - Empty or whitespace-only names

- No database schema changes required
- Backward compatible with existing data
- Graceful fallback chain ensures something always displays
