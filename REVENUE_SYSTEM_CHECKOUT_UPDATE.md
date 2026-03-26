# REVENUE TRACKING SYSTEM - CHECKOUT & WALK-IN/ONLINE SEPARATION

## Implementation Summary

### Key Changes Made:

#### 1. ✅ Cottage Rental Revenue - Only Recorded on Checkout
- **File:** `handlers/reservation_update_handler.php`
- **Logic:** When a reservation is marked as `completed` (checkout):
  - Fetches cottage price from cottages table
  - Calculates: `total_amount = cottage_price × 2 hours` (minimum rental)
  - Updates the `total_amount` field in reservations table
- **Previous:** Amounts were calculated at booking time (now removed)
- **Result:** Revenue only shows after guest checks out

#### 2. ✅ Dashboard Revenue Breakdown - Online vs Walk-In

**For Cottage Rentals:**
- **Walk-In Cottage:** All completed reservations without customer activity log OR created by admin/staff/manager
- **Online Cottage:** Completed reservations created by customers (tracked via activity_logs)

**For Boat Rentals:**
- **Walk-In Boat:** All completed rentals without activity log OR created by admin/staff/manager
- **Online Boat:** Completed rentals created by customers (tracked via activity_logs)

#### 3. ✅ Updated Dashboard Display
- **Row 1:** Online/Walk-In Fish Orders, Menu Orders
- **Row 2:** Online Cottage Rentals, Walk-In Cottage Rentals
- **Row 3:** Online Boat Rentals, Walk-In Boat Rentals

Each card shows:
- Total revenue amount
- Number of completed transactions
- Icon and color coding for easy identification

### Files Modified:

1. **handlers/manual_cottage_reservation_handler.php**
   - Removed: total_amount calculation on initial booking
   - Kept: Validation and setup (amount will be added on checkout)

2. **handlers/booking_handler.php**
   - Removed: total_amount calculation on initial booking
   - Kept: Creates reservation with total_amount default = 0

3. **handlers/reservation_update_handler.php**
   - Added: Logic to calculate total_amount when status = 'completed'
   - Fetches cottage price and calculates revenue on checkout
   - Applies to all cottage types

4. **handlers/boat_rental_handler.php**
   - Enhanced: Activity logging on completion with revenue amount
   - Note: Boat rentals already had pricing at creation (uses different formula)

5. **client/booking.php**
   - Updated: Cottage dropdown now shows price (visual indicator)
   - Added: `data-price` attribute for potential JavaScript calculations

6. **index.php (Dashboard)**
   - Modified: Cottage/Boat revenue queries to separate online vs walk-in
   - Uses: activity_logs for source identification (customer vs admin/staff)
   - Uses: LEFT JOIN for historical data compatibility
   - Now displays: 4 separate cards instead of 2

### Revenue Recording Flow:

**Walk-In Cottage:**
1. Staff checks in customer via `manual_cottage_reservation.php`
2. Reservation created with status = pending/confirmed
3. Guest checks out via reservations_list.php → "Check Out" button
4. Handler updates status to 'completed'
5. **At this moment:** Cottage price fetched and total_amount calculated
6. Revenue now appears on dashboard under "Walk-In Cottage"

**Online Cottage:**
1. Customer books via `client/booking.php`
2. Reservation created with status = pending
3. Admin confirms via reservations_list.php → status = 'confirmed'
4. Customer/staff marks checkout → status = 'completed'
5. **At this moment:** Cottage price fetched and total_amount calculated
6. Revenue now appears on dashboard under "Online Cottage"

**Boat Rentals:**
1. Staff records rental via `boat_rent.php`
2. Rental created with status = active, total_amount already calculated
3. Staff clicks "Done" button to mark complete
4. Handler updates status to 'completed'
5. Revenue now appears on dashboard under appropriate boat category

### Dashboard Query Logic:

**Walk-In:**
```sql
LEFT JOIN activity_logs ... 
WHERE status = 'completed'
AND (a.user_type IN ('admin', 'staff', 'manager') OR a.id IS NULL)
```
- Includes: All completed records without activity log (historical)
- Includes: Records created by staff/admin (current)
- Excludes: Records with customer activity log

**Online:**
```sql
INNER JOIN activity_logs ...
WHERE status = 'completed'
AND a.user_type = 'customer'
```
- Includes: Only completed records WITH customer activity log
- Excludes: Historical data without activity log
- Will grow as new online bookings are made

### Pricing Model:

- **Cottage Rentals:** 2-hour minimum × hourly rate
  - Example: Cottage @ ₱500/hour = ₱1,000.00 per completed rental
  
- **Boat Rentals:** Already uses: `people × hours × hourly_rate`
  - Example: 2 people × 3 hours × ₱200 = ₱1,200.00

### Database Status:

- ✅ `reservations` table: Has `total_amount` column (DECIMAL(10,2))
- ✅ `activity_logs` table: Empty but ready for tracking going forward
- ✅ `boat_rentals` table: Already has `total_amount` column

### Testing:

To verify the system:
1. Create a test cottage reservation
2. Mark it as completed/checked out
3. Check dashboard - should see amount in appropriate category
4. Activity log (if available) determines online vs walk-in classification

### Notes for Future:

- Activity logs will be populated for all NEW reservations and boat rentals
- Historical data (8 cottage reservations) show in "Walk-In" due to LEFT JOIN fallback
- Going forward, walks-in and online will be cleanly separated via activity_logs
- System is backwards compatible and works with or without activity logs

---

**Implementation Date:** 2026-02-25  
**Status:** ✅ COMPLETE - Revenue only recorded on checkout, separated by source
