# 🎯 QUICK START GUIDE - Online Deployment (5 Steps)

**Date:** February 23, 2026  
**Status:** Ready for Immediate Deployment

---

## ⚡ TL;DR - Do This Now

1. **Upload** all files to hosting platform via FTP
2. **Run** `https://yourdomain.com/maataFishFarmSystem/db_setup_now.php` (creates database tables automatically)
3. **Create** first dining table in `availability_set.php`
4. **Test** booking with table selection in client portal
5. **Done!** 🎉

**Estimated Time: 10-15 minutes**

---

## 📋 Files to Upload (All 48 Files)

### Modified Files (16) - Already Changed
```
1.  availability_check.php
2.  availability_set.php
3.  client/booking.php
4.  client/id_verification.php
5.  client/partials/header.php
6.  client/profile.php
7.  customer_id_verification.php
8.  entrance_fee.php
9.  handlers/availability_delete.php
10. handlers/availability_set_handler.php
11. handlers/availability_update.php
12. handlers/booking_handler.php
13. handlers/order_details.php
14. index.php
15. orders_view.php
16. partials/navigation.php
```

### New Feature Files (32) - Will Be Added
```
handlers/admin_fish_items.php
handlers/admin_fish_order.php
handlers/boat_rental_handler.php
handlers/customer_id_delete.php
handlers/fish_order_delete.php
handlers/fish_order_details.php
handlers/fish_order_update.php
handlers/get_entrance_fee_guests.php
handlers/send_customer_feedback.php

boat_management.php
boat_rent.php
admin_fish_order.php
db_setup_now.php
fish_orders_view.php
fish_order_receipt.php

Migration/Setup Files:
add_updated_at_column.php
check_customers_schema.php
create_boat_rentals_table.php
migrate_add_num_people.php
migrate_add_rental_price.php
migrate_create_availability_tables.php
setup_availability_table.php
setup_resubmission_tracking.php
setup_table_availability.php
setup_table_id_column.php

Additional:
TABLE_AVAILABILITY_SETUP.md (Documentation)
assets/img/customer_ids/ (Directory for government IDs)
Customer ID images (pre-uploaded samples)
```

---

## 🗄️ Database Setup (Choose One Method)

### Method 1️⃣ - Automatic (EASIEST - Recommended)
```
1. Open: https://yourdomain.com/maataFishFarmSystem/db_setup_now.php
2. Click "Create Tables" button
3. Wait for success confirmation
4. Done! All tables created automatically
```

### Method 2️⃣ - Manual SQL (via phpMyAdmin)
```
1. Log into cPanel
2. Open phpMyAdmin
3. Select database "maata"
4. Click "SQL" tab
5. Open file: SQL_MIGRATIONS.sql
6. Copy all content
7. Paste into SQL editor
8. Click "Go"
9. Wait for execution complete message
```

---

## 🚀 Quick Setup Walkthrough

### Step 1: Upload Files
- [ ] Use FTP client or cPanel File Manager
- [ ] Upload all 48 files to `public_html/maataFishFarmSystem/`
- [ ] Verify all directories created properly

### Step 2: Create Directory
- [ ] Create `/assets/img/customer_ids/` directory
- [ ] Set permissions to 755 or 775
- [ ] This stores customer government ID images

### Step 3: Run Setup
- [ ] Visit: `https://yourdomain.com/maataFishFarmSystem/db_setup_now.php`
- [ ] See "Setup Complete" message
- [ ] All database tables created automatically

### Step 4: Login & Test
- [ ] Admin login: `https://yourdomain.com/maataFishFarmSystem/admin_login.php`
- [ ] Create a test table: **Availability → Set Availability**
- [ ] View tables: **Availability → Check Availability**

### Step 5: Test Customer Features
- [ ] Customer login: `https://yourdomain.com/maataFishFarmSystem/client/`
- [ ] Try booking table: **Booking → Dine-In**
- [ ] Verify table selection dropdown works

---

## 📊 New Features Added (What Users Will See)

### For Administrators
1. **Table Management** - Create, edit, delete dining tables
   - Location: Availability menu
   - Set table name, capacity, notes
   - Toggle available/not available status

2. **Boat Rental Management** - Manage boat inventory
   - Location: Reservation → Boat Management
   - Add boats with per-boat rental rates
   - Set boat capacity and status

3. **Customer ID Verification** - Verify government IDs
   - Location: Customers → ID Verification
   - View uploaded ID images
   - Send feedback or approve/reject

4. **Fish Orders** - New order type for fish sales
   - Location: Orders → Fish Order
   - Create and manage fish orders from customers

### For Customers
1. **Dine-In Booking with Table Selection**
   - Select dining table when booking
   - System validates guest count vs. table capacity
   - Reservation linked to specific table

2. **Government ID Upload** - New verification feature
   - Upload government-issued ID for verification
   - Track verification status
   - Get approved/feedback from admin

3. **Boat Rental Booking** - New rental service
   - Browse available boats
   - Select rental date and duration
   - Automatic price calculation
   - Can book anonymously or with account

---

## 🔧 Database Changes Summary

### New Tables
- `availability_tables` - Manages dining tables
- `boat_inventory` - Stores boat information
- `boat_rentals` - Tracks boat rental bookings

### Modified Tables
- `reservations` - Added `table_id` column (links to specific table)
- `customers` - Added government ID verification columns

### New Columns
```
reservations.table_id (INT) - Links to availability_tables
reservations.updated_at (TIMESTAMP) - Track updates

customers.government_id_verified (TINYINT)
customers.government_id_image (VARCHAR)
customers.created_at (TIMESTAMP)
customers.updated_at (TIMESTAMP)
```

---

## ✅ Quick Verification Checklist

After setup, quickly verify:

```
Admin Panel
☐ Can login with admin credentials
☐ Dashboard loads without errors
☐ Can create new table (Availability → Set Availability)
☐ Can view tables (Availability → Check Availability)
☐ Can create boat (Reservation → Boat Management)
☐ Can view customer IDs (Customers → ID Verification)

Customer Portal
☐ Can login with customer credentials
☐ Can see available tables in booking form
☐ Can upload government ID in profile
☐ Can complete dine-in booking
☐ Can browse boats for rental

Database
☐ availability_tables table exists and has data
☐ boat_inventory table exists
☐ boat_rentals table exists
☐ reservations table has table_id column
☐ customers table has government_id columns
```

---

## 🆘 If Something Goes Wrong

### Database Error: "Table doesn't exist"
```
Solution: Visit https://yourdomain.com/maataFishFarmSystem/db_setup_now.php
This will create all required tables automatically
```

### File Upload Not Working
```
Solution: 
1. Verify /assets/img/customer_ids/ directory exists
2. Check permissions are 755 or 775
3. Contact hosting support if still failing
```

### Can't Login
```
Solution:
1. Clear browser cookies (Ctrl+Shift+Delete)
2. Try incognito/private window
3. Verify username/password are correct
4. Check user exists in database via phpMyAdmin
```

### White Screen / Error 500
```
Solution:
1. Check error_log in cPanel
2. Verify config/db.php credentials are correct for online database
3. Check file permissions (644 for PHP files)
4. Contact hosting support with error log details
```

---

## 📞 Support Resource Files

These documents are included in your root folder:

- `DEPLOYMENT_GUIDE.md` - Comprehensive 7-section deployment guide
- `SQL_MIGRATIONS.sql` - All SQL commands to run manually
- `DEPLOYMENT_CHECKLIST.md` - Detailed testing checklist
- `QUICK_REFERENCE.md` - This file

---

## 🎯 Success Criteria

You've successfully deployed when:

✅ All 48 files uploaded  
✅ Database tables created (via db_setup_now.php or manual SQL)  
✅ Admin can login and create tables  
✅ Customer can see tables in booking form  
✅ All pages load without database errors  
✅ File uploads working (government IDs, etc.)  

---

## 📝 Deployment Record

**Started:** ________________ (Date/Time)  
**Completed:** ________________ (Date/Time)  
**Total Time:** ________________  
**By:** ________________ (Your Name)  

**Notes:**
___________________________________________________________________
___________________________________________________________________

---

## Next Steps (After Successful Deployment)

1. **Create Sample Data**
   - Add 5-10 sample tables for testing
   - Add sample boats for rental
   - Create sample bookings

2. **Train Staff**
   - Show how to create/manage tables
   - Show how to verify customer IDs
   - Show how to manage boat rentals

3. **Communicate with Customers**
   - Announce new table booking feature
   - Inform about government ID verification
   - Promote boat rental service

4. **Monitor System**
   - Check error logs daily for first week
   - Verify backups are running
   - Monitor database performance

---

**Deployment Guide Version:** 1.0  
**Date:** February 23, 2026  
**System:** Maata Fish Farm System  
**Status:** Ready for Production
