# Table Availability System Setup Guide

## Overview
This new table availability system allows administrators to create and manage dining tables with their capacities and availability status.

## Components Created

### 1. Database Migration
**File:** `migrate_create_availability_tables.php`

Run this migration to create the `availability_tables` table:

```bash
# Navigate to the project directory and access the migration script via browser or CLI
```

**Table Structure:**
- `id` - Primary key (auto-increment)
- `table_name` - Unique table identifier (e.g., "Table 1", "VIP Section")
- `capacity` - Maximum guest capacity (1-100)
- `notes` - Optional notes or special details
- `status` - Status: active, inactive, or maintenance
- `created_at` - Record creation timestamp
- `updated_at` - Last update timestamp

### 2. Pages Created/Updated

#### availability_set.php
**Purpose:** Create new dining tables

**Form Fields:**
- Table Name (required) - e.g., "Table 1", "VIP Table", "Counter"
- Capacity (required) - Number between 1-100
- Notes (optional) - Any special details about the table

**Features:**
- Clean, Bootstrap-styled form
- Input validation (table name uniqueness, capacity range)
- Success/error messages
- Link to view all tables
- Help sidebar with workflow info

#### availability_check.php
**Purpose:** View, edit, and delete tables

**Features:**
- Table list showing: Name, Capacity, Status, Notes, Creation Date
- Edit modal to update capacity, status, and notes
- Delete button with confirmation
- Status badge (Active/Inactive/Maintenance)
- Activity count badge

### 3. Handlers

#### handlers/availability_set_handler.php
- Processes table creation form
- Validates input data
- Checks for duplicate table names
- Logs activity
- Returns success/error messages

#### handlers/availability_update.php
- Updates table capacity, status, and notes
- Validates input
- Logs activity
- Redirects with success/error message

#### handlers/availability_delete.php
- Deletes a table
- Logs activity
- Prevents orphaned records
- Redirects with confirmation message

## Setup Instructions

### Step 1: Run the Migration
1. Navigate to: `http://localhost/maataFishFarmSystem/migrate_create_availability_tables.php`
2. The script will create the `availability_tables` table if it doesn't exist
3. You should see a success message

### Step 2: Test Table Creation
1. Go to: `http://localhost/maataFishFarmSystem/availability_set.php`
2. Fill in the form:
   - Table Name: "Table 1"
   - Capacity: 4
   - Notes: Optional
3. Click "Create Table"
4. You should see a success message

### Step 3: View Created Tables
1. Go to: `http://localhost/maataFishFarmSystem/availability_check.php`
2. You should see the created table(s) in the list
3. You can:
   - Click edit button to update capacity/status
   - Click delete button to remove a table

## Usage Examples

### Creating Different Table Types
```
Table 1      - Capacity: 4
Table 2      - Capacity: 4
Table 3      - Capacity: 6
VIP Section  - Capacity: 8
Counter      - Capacity: 2
```

### Table Status
- **Active** - Table is available for bookings
- **Inactive** - Table is disabled (won't show in bookings)
- **Maintenance** - Table is under maintenance

## Features
✅ Create multiple tables with different capacities
✅ Edit table details anytime
✅ Update table status (active/inactive/maintenance)
✅ Add notes for special table info
✅ Activity logging for audit trail
✅ Status badges for quick overview
✅ Responsive Bootstrap design

## Database Query Examples

### Get all active tables
```sql
SELECT * FROM availability_tables WHERE status = 'active' ORDER BY created_at DESC;
```

### Get a specific table
```sql
SELECT * FROM availability_tables WHERE table_name = 'Table 1';
```

### Count total capacity
```sql
SELECT SUM(capacity) as total_capacity FROM availability_tables WHERE status = 'active';
```

### Update table status
```sql
UPDATE availability_tables SET status = 'inactive' WHERE table_name = 'Table 1';
```

## Next Steps
After setting up tables, you can:
1. Connect this to your customer booking system
2. Display available tables in the booking form
3. Validate bookings against table capacity
4. Track table reservations

## Troubleshooting

### Migration fails
- Check database permissions
- Ensure database connection is working
- Review error message for specific issues

### Cannot create table
- Ensure table name is unique
- Capacity should be between 1-100
- Check auth_admin.php for proper authentication

### Tables not showing
- Verify `availability_tables` table exists
- Check database query in availability_check.php
- Review browser console for JavaScript errors

## Support
For issues, check:
1. Error messages in the form
2. Browser console (F12) for JavaScript errors
3. Server error logs
4. Database logs
