<!-- ================================================================
     DEPLOYMENT TRACKING CHECKLIST
     Use this to track your progress through the online deployment
     ================================================================ -->

# 🚀 Online Deployment Progress Tracker

**Domain:** `https://yourdomain.com/maataFishFarmSystem/`  
**Database:** `maata` (or your database name)  
**Date Started:** [Your Date]  
**Status:** 🟡 IN PROGRESS

---

## PHASE 1: Files Upload ⬜→🟦→✅

- [ ] All 16 modified files uploaded
- [ ] All 32 new feature files uploaded  
- [ ] Verify all files are in correct directories via FTP/File Manager
- [ ] Check file permissions (644 for .php files, 755 for directories)

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

---

## PHASE 2: Directory Setup ⬜→🟦→✅

- [ ] Create `/assets/img/customer_ids/` directory via FTP
- [ ] Set directory permissions to 755 or 775
- [ ] Create `/handlers/` directory if missing
- [ ] Create `/client/` directory if missing
- [ ] Create `/partials/` directory if missing
- [ ] Verify `config/db.php` exists and has correct credentials

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

---

## PHASE 3: Database Migrations ⬜→🟦→✅

### Option A: Automated Setup (RECOMMENDED)
- [ ] Upload all files to hosting
- [ ] Visit: `https://yourdomain.com/maataFishFarmSystem/db_setup_now.php`
- [ ] Page shows "Setup Complete" message
- [ ] Links to "Create Table" and "View Tables" are available

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

### Option B: Manual phpMyAdmin
- [ ] Log into cPanel
- [ ] Open phpMyAdmin
- [ ] Select your database
- [ ] Click "SQL" tab
- [ ] Copy content from `SQL_MIGRATIONS.sql` file
- [ ] Paste into SQL editor
- [ ] Click "Go" to execute
- [ ] Wait for "X queries executed successfully" message

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

---

## PHASE 4: Verification Tests ⬜→🟦→✅

### Database Tables
- [ ] Table `availability_tables` exists
- [ ] Table `boat_inventory` exists
- [ ] Table `boat_rentals` exists
- [ ] Column `table_id` added to `reservations`
- [ ] Columns added to `customers` table

**Check in phpMyAdmin:**
```sql
SHOW TABLES LIKE '%availability%';
SHOW TABLES LIKE '%boat%';
SHOW COLUMNS FROM reservations LIKE '%table_id%';
SHOW COLUMNS FROM customers LIKE '%government_id%';
```

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

### Admin Panel - Table Availability
- [ ] Login works: `https://yourdomain.com/maataFishFarmSystem/admin_login.php`
- [ ] Dashboard loads: `https://yourdomain.com/maataFishFarmSystem/index.php`
- [ ] Access Set Availability: `https://yourdomain.com/maataFishFarmSystem/availability_set.php`
- [ ] No database errors on page load
- [ ] Create test table (Name: "Table 1", Capacity: 4)
- [ ] Success message appears
- [ ] Access Check Availability: `https://yourdomain.com/maataFishFarmSystem/availability_check.php`
- [ ] Test table appears in list
- [ ] Edit button works (change capacity to 5)
- [ ] Delete button works (remove test table)

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

### Admin Panel - Boat Management
- [ ] Access Boat Management: `.../boat_management.php`
- [ ] Create test boat (Name: "Boat 1", Capacity: 2, Price: 100)
- [ ] Edit boat details
- [ ] Delete test boat

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

### Admin Panel - Customer Verification
- [ ] Access Customer ID Verification: `.../customer_id_verification.php`
- [ ] Page loads without errors
- [ ] Can see pending verification requests (if any)

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

### Customer Portal - Booking
- [ ] Customer login works: `https://yourdomain.com/maataFishFarmSystem/client/`
- [ ] User can access booking: `.../client/booking.php?type=dine-in`
- [ ] Table dropdown shows available tables
- [ ] Can select a table and submit booking
- [ ] Booking saved to database (check reservations table)

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

### Customer Portal - ID Verification
- [ ] User can access profile: `.../client/profile.php`
- [ ] Can upload government ID image
- [ ] Image saved to `/assets/img/customer_ids/` directory
- [ ] Admin can see pending verification

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

### Customer Portal - Boat Rental
- [ ] Customer can access boat rental: `.../client/booking.php?type=boat-rent`
- [ ] List of available boats shows
- [ ] Can select boat and complete booking
- [ ] Booking saved with correct pricing

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

---

## PHASE 5: Performance & Final Checks ⬜→🟦→✅

- [ ] All pages load within 3 seconds
- [ ] No browser console errors (F12 → Console tab)
- [ ] No white screen of death (blank pages)
- [ ] Database connections stable (no timeout errors)
- [ ] File uploads working (government ID, etc.)
- [ ] Emails sending (if configured)
- [ ] Session/login persistence working

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

---

## PHASE 6: Go-Live ⬜→🟦→✅

- [ ] All tests passed
- [ ] Database backed up (ask hosting provider if needed)
- [ ] Admin team notified of new features
- [ ] Staff trained on new functionality
- [ ] Customer communication sent about new features
- [ ] Monitor error logs for first 24 hours

**Status:** ⬜ Not Started | 🟦 In Progress | ✅ Complete

---

## 🆘 TROUBLESHOOTING

### Issue: "Table 'maata.availability_tables' doesn't exist"
**Solution:**
- [ ] Run `db_setup_now.php` again
- [ ] OR manually run SQL migrations in phpMyAdmin
- [ ] Check that SQL executed successfully (no errors in output)

### Issue: File Upload Not Working
**Solution:**
- [ ] Verify `/assets/img/customer_ids/` directory exists
- [ ] Check directory permissions (should be 755 or 775)
- [ ] Check server file upload size limits in cPanel

### Issue: Customer Can't Login
**Solution:**
- [ ] Clear browser cookies
- [ ] Try incognito/private browsing mode
- [ ] Verify customer account exists in database
- [ ] Check session save path in cPanel

### Issue: Booking Form Shows No Tables
**Solution:**
- [ ] Create at least one table first: `.../availability_set.php`
- [ ] Verify table status is "available"
- [ ] Clear browser cache (Ctrl+Shift+Delete)
- [ ] Check database for availability_tables entries

### Issue: White Screen / Database Connection Error
**Solution:**
- [ ] Check `config/db.php` credentials match cPanel database info
- [ ] Verify username and password are correct
- [ ] Check database name is correct
- [ ] Ensure file permissions are 644 for PHP files
- [ ] Check error logs in cPanel

---

## 📝 DEPLOYMENT NOTES

**Completed Actions:**
- Date: ________  Action: ________________
- Date: ________  Action: ________________
- Date: ________  Action: ________________

**Issues Encountered & Resolution:**
- Issue: ________________________  |  Fixed: _____ (Date)
- Issue: ________________________  |  Fixed: _____ (Date)

**Next Steps:**
- [ ] ________________
- [ ] ________________
- [ ] ________________

---

## ✅ FINAL SIGN-OFF

**Deployment Completed By:** ________________  
**Date Completed:** ________________  
**Time Taken:** ________________  
**All Tests Passed:** ⬜ YES | ⬜ NO  
**Ready for Production:** ⬜ YES | ⬜ NO  

**Notes for Future Reference:**
___________________________________________________________________
___________________________________________________________________
___________________________________________________________________

---

**Last Updated:** February 23, 2026  
**Version:** 1.0
