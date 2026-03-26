# Sales Report Enhancement - Complete Documentation

**Date:** March 16, 2026  
**File:** `/reports_sales.php`  
**Status:** ✅ FULLY REVISED & ENHANCED

---

## Overview

The sales report system has been completely revised to provide comprehensive, per-service sales breakdowns with today/monthly/yearly analysis. The report is now fully printable with professional header and footer sections.

---

## Key Features

### 1. **Per-Service Revenue Breakdown** ✅
Each of the 7 services now displays:
- **Today's Sales** - Revenue from today only
- **This Month Sales** - Month-to-date revenue
- **This Year Sales** - Year-to-date revenue
- **All Time Sales** - Total revenue across all time

### 2. **Services Included** ✅
All revenue streams are tracked:
1. 🐟 **Online Fish Orders** - Online customer fish purchases
2. 🏪 **Walk-In Fish Orders** - Walk-in customer fish purchases
3. 🍽️ **Online Menu Orders** - Online restaurant menu orders
4. 🏪 **Walk-In/Direct Menu Orders** - Staff-created menu orders
5. 🏠 **Online Cottage Reservations** - Online cottage bookings
6. 🏠 **Walk-In Cottage Reservations** - Walk-in cottage bookings
7. ⛵ **Boat Rentals** - Boat rental transactions

### 3. **Print-Friendly Layout** ✅

**Screen View:**
- Clean, card-based layout with metrics displayed in 4-column grid
- Colorful service icons for quick identification
- Responsive design for desktop/tablet

**Print View:**
- Professional header with farm name and report title
- Report period and generation timestamp
- Clean table layout for all service metrics
- Footer with "Prepared By" section and signature line

### 4. **Status Filters Applied** ✅

Only paid/completed sales are counted:
- Online orders: `status IN ('paid', 'completed')`
- Walk-in fish: `status = 'paid'`
- Menu orders: `status IN ('paid', 'completed')`
- Cottage reservations: `status = 'completed'`
- Boat rentals: `status = 'completed'`

**Pending orders are EXCLUDED** from all revenue calculations.

---

## Report Sections

### Overall Sales Summary (Top)
Displays aggregate metrics:
- Today's Sales (all services combined)
- This Month Sales (all services combined)
- This Year Sales (all services combined)
- All Time Sales (all services combined)

### Service Categories (Individual Cards)

#### Fish Orders
**Online Fish Orders**
- Counts orders from `orders` table where `is_manual = 0`
- Includes only those with fish items
- Status filter: `IN ('paid', 'completed')`

**Walk-In Fish Orders**
- Counts orders from `fish_orders` table
- Status filter: `= 'paid'`
- Set to paid immediately by admin

#### Menu Orders
**Online Menu Orders**
- Counts orders from `orders` table where `is_manual = 0`
- Includes only those with menu/product items
- Status filter: `IN ('paid', 'completed')`

**Walk-In/Direct Menu Orders**
- Counts orders from `menu_orders` table
- Status filter: `IN ('paid', 'completed')`
- Created directly by staff

#### Accommodations & Activities
**Online Cottage Reservations**
- Counts from `reservations` table where `is_manual = 0`
- Reservation type: `'cottage'`
- Status filter: `= 'completed'`

**Walk-In Cottage Reservations**
- Counts from `reservations` table where `is_manual = 1`
- Reservation type: `'cottage'`
- Status filter: `= 'completed'`

**Boat Rentals**
- Counts from `boat_rentals` table
- Status filter: `= 'completed'`

---

## Print Features

### Print Header
```
MAATA FISH FARM SYSTEM
COMPREHENSIVE SALES REPORT
Report Period: [Period]
Generated: [Timestamp]
```

### Print Footer
Includes:
- Report Summary
- **Prepared By:** Admin name and timestamp
- **Authorized By:** Signature line for authorization

### Print Styles
- Simple, professional layout
- Metrics displayed in clear table format
- Page breaks applied appropriately
- All unnecessary UI elements hidden

---

## Metrics Displayed

### For Each Service
| Metric | Description |
|--------|-------------|
| **Today** | Revenue and order count from today only |
| **This Month** | Month-to-date revenue and order count |
| **This Year** | Year-to-date revenue and order count |
| **All Time** | Total revenue and order count across all time |

### Example: Online Fish Orders
```
Today:       ₱2,800.00  (6 orders)
This Month:  ₱18,500.00 (45 orders)
This Year:   ₱52,000.00 (120 orders)
All Time:    ₱152,300.00 (350 orders)
```

---

## Database Queries

All queries use prepared statements and are structured as:

### Online Orders (Fish/Menu)
```sql
SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt
FROM orders o 
WHERE o.is_manual = 0 
  AND o.status IN ('paid', 'completed')
  AND [DATE FILTER]
  AND EXISTS (
    SELECT 1 FROM order_items oi 
    WHERE oi.order_id = o.id 
    AND [ITEM TYPE FILTER]
  )
```

### Walk-In Fish Orders
```sql
SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt
FROM fish_orders 
WHERE status = 'paid' 
  AND [DATE FILTER]
```

### Menu Orders
```sql
SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt
FROM menu_orders 
WHERE status IN ('paid', 'completed')
  AND [DATE FILTER]
```

### Cottage Reservations
```sql
SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt
FROM reservations 
WHERE reservation_type = 'cottage'
  AND status = 'completed'
  AND is_manual = [0 for online, 1 for walk-in]
  AND [DATE FILTER]
```

### Boat Rentals
```sql
SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt
FROM boat_rentals 
WHERE status = 'completed'
  AND [DATE FILTER]
```

---

## Date Filters

| Period | Filter |
|--------|--------|
| Today | DATE(created_at) = CURDATE() |
| This Month | created_at >= MONTH_START |
| This Year | created_at >= YEAR_START |
| All Time | (no date filter) |

---

## Features

### ✅ Interactive Elements (Screen Only)
- Period selector dropdown (Today / Last 7-30-90 Days / Month / Year / All Time)
- Filter button to apply selected period
- Print button (opens browser print dialog)
- Export to CSV button

### ✅ Print Features
- Click "Print" button or use Ctrl+P
- Professional header with report details
- Service metrics in clear table format
- Footer with prepared by information and signature line
- Automatic "Prepared By" populated from admin session
- Timestamp included in footer

### ✅ CSV Export
- Exports all service data in structured format
- Includes header information
- Today, Month, Year, and All Time data for each service
- Easy import to Excel/Google Sheets

---

## Technical Implementation

### File Structure
- **Main Report:** `/reports_sales.php`
- **Authentication:** Requires admin login via `auth_admin.php`
- **Templates:** Uses standard layout with head/nav/footer partials
- **Styling:** Bootstrap-based responsive design

### Database Tables Used
- `orders` - Online customer orders
- `fish_orders` - Walk-in fish orders
- `menu_orders` - Direct menu orders
- `reservations` - Cottage bookings
- `boat_rentals` - Boat rental transactions
- `order_items` - Line items in orders
- `products` - Product details
- `fish_species` - Fish types

### Key Functions
```php
getServiceMetrics($conn, $serviceName, $serviceType)
```
Returns array with: `today, month, year, all_time, today_count, month_count, year_count, all_count`

### CSS Media Queries
- `@media print` - Print-specific styling
- `@media screen` - Screen-specific styling
- `.no-print` - Hidden on print
- `.print-header`, `.print-footer` - Visible on print only

---

## Usage Instructions

### View Report
1. Navigate to `/reports_sales.php`
2. Select desired period from dropdown
3. Click "Filter" button
4. Review metrics for each service

### Print Report
1. Click "Print" button OR use Ctrl+P
2. Configure print settings (margins, orientation, etc.)
3. Review print preview
4. Print or save as PDF

### Export Data
1. Click "Export CSV" button
2. File downloads as `sales-report-YYYY-MM-DD.csv`
3. Open in Excel/Google Sheets

### Prepared By Section (Print Only)
- Automatically populates with admin name from session
- Includes generation timestamp
- Signature line for authorization

---

## Testing Checklist

- [x] All 7 services displayed with correct names/icons
- [x] Today metrics calculated correctly (same-day only)
- [x] Month metrics calculated correctly (month-to-date)
- [x] Year metrics calculated correctly (year-to-date)
- [x] All time metrics calculated correctly
- [x] Order counts displayed alongside revenue
- [x] Status filters applied (pending orders excluded)
- [x] Print layout is professional and readable
- [x] Header displays report period correctly
- [x] Footer includes "Prepared By" with admin name
- [x] Signature line provided for authorization
- [x] CSV export includes all data
- [x] Responsive design works on mobile/tablet
- [x] Date formatting consistent across all periods
- [x] Currency formatting (₱) applied correctly

---

## Future Enhancements

**Potential additions:**
- Customer breakdown by service type
- Top customers by revenue
- Product/item performance within each service
- Revenue trends and growth charts
- Comparison with previous periods
- Detailed transaction lists per service
- Email delivery of reports
- Scheduled automated reports

---

## Support & Troubleshooting

### Print Not Working
- Check browser print permissions
- Ensure JavaScript is enabled
- Try different browser

### CSV Export Not Working
- Check browser download settings
- Ensure pop-ups aren't blocked
- Try different file format

### Missing Data
- Verify date filters are correct
- Check that transactions have `status = 'paid'` or `'completed'`
- Confirm `is_manual` flag is set correctly
- Review transaction table structure

---

## File Modifications

**Original File:** `reports_sales.php` (2,108 lines)  
**Action:** Completely revised  
**New Line Count:** ~850 lines (simplified and focused)  
**Focus:** Service-level metrics with today/monthly/yearly breakdowns  
**Added:** Print header/footer, Prepared By section  

---

**Created By:** AI Code Assistant  
**Date:** March 16, 2026  
**Version:** 1.0 - Enhanced Sales Report
