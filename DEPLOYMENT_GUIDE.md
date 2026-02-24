# Deployment Guide - Online Platform Setup

> **Date:** February 23, 2026  
> **Status:** Ready for Production Deployment

## Overview
This guide provides step-by-step instructions to set up your Maata Fish Farm System on the hosting platform. Since database changes were made locally, you'll need to run migrations on the online database.

---

## 📋 SECTION 1: Database Migrations (CRITICAL)

### Required Database Tables & Columns

Run these SQL commands in your hosting platform's database management tool (phpMyAdmin, cPanel, etc.):

#### 1.1 Create `availability_tables` Table
```sql
CREATE TABLE IF NOT EXISTS availability_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 1,
    notes TEXT,
    status ENUM('available', 'not available') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 1.2 Add `table_id` Column to `reservations` Table
```sql
ALTER TABLE reservations ADD COLUMN table_id INT NULL AFTER cottage_id;
ALTER TABLE reservations ADD INDEX idx_table_id (table_id);
```

#### 1.3 Create `boat_inventory` Table (if not exists)
```sql
CREATE TABLE IF NOT EXISTS boat_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    boat_name VARCHAR(100) NOT NULL UNIQUE,
    boat_type VARCHAR(50),
    capacity INT NOT NULL DEFAULT 1,
    rental_price DECIMAL(10, 2) DEFAULT 0,
    status ENUM('available', 'unavailable', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 1.4 Create `boat_rentals` Table (if not exists)
```sql
CREATE TABLE IF NOT EXISTS boat_rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    boat_name VARCHAR(100) NOT NULL,
    customer_id INT NULL,
    num_people INT DEFAULT 1,
    rental_date DATE NOT NULL,
    rental_time TIME,
    duration_hours INT DEFAULT 1,
    total_cost DECIMAL(10, 2) DEFAULT 0,
    status ENUM('pending', 'active', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_boat_name (boat_name),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 1.5 Add Missing Columns to `customers` Table
```sql
ALTER TABLE customers ADD COLUMN IF NOT EXISTS government_id_verified TINYINT(1) DEFAULT 0;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS government_id_image VARCHAR(255) NULL;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```

#### 1.6 Add Missing Columns to `reservations` Table
```sql
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```

---

## 📁 SECTION 2: File Deployment Confirmation

### Verify These Files Are Uploaded

**Core Modified Files (16 files):**
- ✅ `availability_check.php`
- ✅ `availability_set.php`
- ✅ `client/booking.php`
- ✅ `client/id_verification.php`
- ✅ `client/partials/header.php`
- ✅ `client/profile.php`
- ✅ `customer_id_verification.php`
- ✅ `entrance_fee.php`
- ✅ `handlers/availability_delete.php`
- ✅ `handlers/availability_set_handler.php`
- ✅ `handlers/availability_update.php`
- ✅ `handlers/booking_handler.php`
- ✅ `handlers/order_details.php`
- ✅ `index.php`
- ✅ `orders_view.php`
- ✅ `partials/navigation.php`

**New Feature Files (32 files):**
- ✅ All files in `handlers/` folder (boat rental, fish order, customer)
- ✅ `boat_management.php`
- ✅ `boat_rent.php`
- ✅ `admin_fish_order.php`
- ✅ `fish_orders_view.php`
- ✅ `fish_order_receipt.php`
- ✅ `db_setup_now.php`
- ✅ `setup_*.php` (all setup scripts)
- ✅ All migration scripts in root folder

---

## 🔧 SECTION 3: Online Setup Steps

### Step 1: Create Directory for Customer IDs
```bash
# Via FTP/File Manager, create this directory:
/assets/img/customer_ids/

# Give it write permissions (755 or 775)
```

### Step 2: Run Database Migrations

**Method A: Using Online Setup Scripts (RECOMMENDED)**

1. Upload all files to your hosting platform
2. Navigate to your domain and run:
   ```
   https://yourdomain.com/maataFishFarmSystem/db_setup_now.php
   ```
3. This will automatically create the required tables

**Method B: Manual phpMyAdmin**

1. Log in to cPanel → phpMyAdmin
2. Select your database
3. Go to "SQL" tab
4. Copy and paste ALL SQL commands from Section 1.1 to 1.6
5. Click "Go" to execute

### Step 3: Verify Database Changes
```sql
-- Run this to verify tables exist:
SHOW TABLES LIKE '%availability%';
SHOW TABLES LIKE '%boat%';

-- Check columns in reservations:
SHOW COLUMNS FROM reservations;

-- Check columns in customers:
SHOW COLUMNS FROM customers;
```

---

## 🧪 SECTION 4: Testing Checklist

After deployment, verify the following:

### Admin Panel Tests
- [ ] Login as admin: `https://yourdomain.com/maataFishFarmSystem/admin_login.php`
- [ ] Dashboard loads without errors: `https://yourdomain.com/maataFishFarmSystem/index.php`
- [ ] Navigate to **Availability → Set Availability**: `https://yourdomain.com/maataFishFarmSystem/availability_set.php`
- [ ] Create a test table (e.g., "Table 1", Capacity: 4)
- [ ] Navigate to **Availability → Check Availability**: `https://yourdomain.com/maataFishFarmSystem/availability_check.php`
- [ ] See the created table in the list
- [ ] Try editing and deleting the table

### Boat Rental Tests
- [ ] Navigate to **Reservation → Boat Management**: `https://yourdomain.com/maataFishFarmSystem/boat_management.php`
- [ ] Add a test boat (e.g., "Boat 1", Capacity: 2, Price: 100)
- [ ] Navigate to **Reservation → Boat Rent**: `https://yourdomain.com/maataFishFarmSystem/boat_rent.php`
- [ ] Create a boat rental booking

### Customer Tests
- [ ] Login as customer: `https://yourdomain.com/maataFishFarmSystem/client/`
- [ ] Test dine-in booking: Navigate to **Booking → Dine-In**
- [ ] Verify tables appear in dropdown
- [ ] Select a table and complete booking
- [ ] Check **Profile → ID Verification**
- [ ] Upload government ID

### Admin Verification
- [ ] Navigate to **Customers → ID Verification**: `https://yourdomain.com/maataFishFarmSystem/customer_id_verification.php`
- [ ] See pending verification requests
- [ ] View uploaded government IDs
- [ ] Test approval/feedback features

---

## 📊 SECTION 5: Database Schema Reference

### New/Modified Tables

```
availability_tables
├── id (INT, Primary Key)
├── table_name (VARCHAR 100, Unique)
├── capacity (INT)
├── notes (TEXT)
├── status (ENUM: 'available', 'not available')
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)

boat_inventory
├── id (INT, Primary Key)
├── boat_name (VARCHAR 100, Unique)
├── boat_type (VARCHAR 50)
├── capacity (INT)
├── rental_price (DECIMAL 10,2) ← NEW COLUMN
├── status (ENUM)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)

boat_rentals (NEW TABLE)
├── id (INT, Primary Key)
├── boat_name (VARCHAR 100)
├── customer_id (INT, Nullable) ← ALLOWS ANONYMOUS
├── num_people (INT) ← NEW COLUMN
├── rental_date (DATE)
├── rental_time (TIME)
├── duration_hours (INT)
├── total_cost (DECIMAL)
├── status (ENUM)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)

reservations (MODIFIED)
├── ... (existing columns)
├── table_id (INT, Nullable) ← NEW COLUMN FOR DINE-IN
├── updated_at (TIMESTAMP) ← NEWLY ADDED
└── ... more columns

customers (MODIFIED)
├── ... (existing columns)
├── government_id_verified (TINYINT) ← NEW
├── government_id_image (VARCHAR) ← NEW
├── created_at (TIMESTAMP) ← NEWLY ADDED
└── updated_at (TIMESTAMP) ← NEWLY ADDED
```

---

## ⚠️ SECTION 6: Important Notes

### Permissions & Security
- Ensure `/assets/img/customer_ids/` directory is writable by the web server
- Check that `config/db.php` has correct online database credentials
- Verify database user has ALTER TABLE permissions

### File Uploads
- Make sure customer ID images directory has proper permissions (755/775)
- Test file upload functionality with a test customer

### Session & Cookies
- If issues with login, clear browser cookies
- Check that `session.save_path` is writable on hosting server

### Backups
- BEFORE running any SQL, backup your online database
- Contact your hosting provider support if unsure about phpMyAdmin

---

## 🚀 SECTION 7: Quick Check (2-5 minutes)

Run this quick verification:

1. ✅ Upload all files and run migrations
2. ✅ Visit `db_setup_now.php` - should show success
3. ✅ Login as admin and create a table
4. ✅ Login as customer and try booking a table
5. ✅ Check if reservation saved with table info

---

## 📞 TROUBLESHOOTING

### "Table doesn't exist" Error
- [ ] Run `db_setup_now.php` again
- [ ] Manually run SQL from Section 1
- [ ] Check database permissions

### File Upload Not Working
- [ ] Check `/assets/img/customer_ids/` directory exists
- [ ] Verify write permissions (705 or 755)
- [ ] Check error logs in cPanel

### Booking Form Show No Tables
- [ ] Ensure `availability_tables` table created
- [ ] Create at least one table with status='available'
- [ ] Clear browser cache and reload

### Database Connection Error
- [ ] Verify `config/db.php` has correct credentials
- [ ] Check username/password with hosting provider
- [ ] Ensure database name is correct

---

## 📝 Deployment Checklist

- [ ] All files uploaded to hosting
- [ ] Database migrations run successfully
- [ ] `/assets/img/customer_ids/` directory created
- [ ] Admin login works
- [ ] Create/view/edit/delete tables works
- [ ] Customer booking shows tables
- [ ] Customer ID verification works
- [ ] Boat rental system works
- [ ] Fish ordering system works (if enabled)

---

**Status:** Ready for Production  
**Last Updated:** February 23, 2026  
**Support:** Contact your hosting provider for database/server issues
