# Sales Report - Quick Start Guide

## Accessing the Report

**URL:** `http://localhost/maataFishFarmSystem/reports_sales.php`

**Requirements:**
- Admin login required
- View permission on sales data

---

## Dashboard Metrics Overview

### Top Row - Key Performance Indicators (KPIs)

```
┌─────────────────────┬──────────────────────┬──────────────────────┬──────────────────────┐
│  📅 TODAY SALES     │ 📊 THIS MONTH SALES  │  💰 TOTAL SALES      │ ⏳ PENDING ORDERS    │
│  ₱0.00              │  ₱500.00             │  ₱215,015.00         │  0                   │
│  All services       │  Month to date       │  All time            │  Menu orders waiting │
└─────────────────────┴──────────────────────┴──────────────────────┴──────────────────────┘
```

### Middle Rows - Service Breakdown (8 Services)

```
┌──────────────────────────────┬──────────────────────────────┬──────────────────────────────┬──────────────────────────────┐
│ 🐟 ONLINE FISH ORDERS        │ 🏪 WALK-IN FISH ORDERS       │ 🍽️ ONLINE MENU ORDERS       │ 🏪 DIRECT MENU ORDERS        │
│ ₱183,680.00                  │ ₱260.00                      │ (Calculated from online)     │ ₱500.00                      │
│ 15 completed                 │ 2 completed                  │ completed                    │ completed                    │
└──────────────────────────────┴──────────────────────────────┴──────────────────────────────┴──────────────────────────────┘

┌──────────────────────────────┬──────────────────────────────┬──────────────────────────────┬──────────────────────────────┐
│ 🏠 ONLINE COTTAGE            │ 🏠 WALK-IN COTTAGE           │ ⛵ BOAT REVENUE              │ 🎫 ENTRANCE FEE              │
│ ₱500.00                      │ ₱9,500.00                    │ ₱200.00                      │ ₱350.00                      │
│ 1 completed                  │ 11 completed                 │ 1 completed                  │ 0 guests today               │
└──────────────────────────────┴──────────────────────────────┴──────────────────────────────┴──────────────────────────────┘
```

---

## How to Use

### Step 1: Filter by Date Range

Click the **Period** dropdown and select:

| Option | Shows |
|--------|-------|
| **Today** | Current date only |
| **Last 7 Days** | Previous week including today |
| **Last 30 Days** | Last month (default) |
| **Last 90 Days** | Last 3 months |
| **This Month** | Current calendar month |
| **This Year** | Current year to date |
| **All Time** | Complete history |

### Step 2: Click "Filter"

Report updates automatically with selected period.

**Note:** All 8 service metrics update immediately

---

## Export Options

### 📄 Print Report

1. Click **Print** button (or Ctrl+P)
2. Choose printer or "Print to PDF"
3. Adjust settings:
   - **Orientation:** Portrait or Landscape
   - **Margins:** 0.5 inches (recommended)
   - **Scale:** Fit to page
4. Click **Print**

**What's Included:**
- Dashboard header with metrics
- All 8 service rows
- Detailed analytics tables
- Charts excluded (not printable)

### 📥 Export to CSV

1. Click **Export CSV** button
2. Saves as: `sales-report-YYYY-MM-DD.csv`
3. Open with Excel, Google Sheets, or text editor

**Use for:**
- Email to manager/accountant
- Backup to cloud storage
- Import to accounting software
- Analysis in spreadsheet

---

## Understanding the Report Sections

### Section 1: Dashboard Metrics (Top)
**Purpose:** Quick overview of all revenue

**Shows:**
- Today's sales
- Month-to-date sales
- Total all-time revenue
- Pending orders count
- Each of 8 services with revenue and count

**When to use:** Morning briefing, daily close, quick status check

---

### Section 2: Charts
**Purpose:** Visual trend analysis

**Contains:**
- **Daily Sales Trend** - Revenue over time (line chart)
- **Order Status Distribution** - Order breakdown (pie chart)

**When to use:** Identifying patterns, trend analysis

---

### Section 3: Category Performance & Best Sellers
**Purpose:** Product-level analysis

**Shows:**
- Sales by product category
- Top 10 products by revenue
- Units sold and order count

**When to use:** Inventory planning, marketing analysis

---

### Section 4: Fish & Menu Analysis
**Purpose:** Menu-specific metrics

**Shows:**
- Fish species sales details
- Menu orders by status
- Top menu items ordered

**When to use:** Menu optimization, supplier ordering

---

### Section 5: Status Breakdown
**Purpose:** Order fulfillment tracking

**Shows:**
- Pending orders
- Confirmed orders
- Paid orders
- Cancelled orders
- Visual progress bars

**When to use:** Operational monitoring

---

## Common Tasks

### Task 1: Daily Close Report
```
1. Select "Today"
2. Click "Print"
3. Print to PDF with timestamp
4. File for daily records
```

### Task 2: Weekly Review with Manager
```
1. Select "Last 7 Days"
2. Click "Export CSV"
3. Email to manager
4. Discuss trends
```

### Task 3: Monthly Accounting
```
1. Select "This Month"
2. Click "Print"
3. Compare with bank statements
4. File with month-end reports
```

### Task 4: Annual Tax Preparation
```
1. Select "This Year"
2. Click "Export CSV"
3. Send to accountant
4. Cross-reference with tax forms
```

### Task 5: Service Performance Comparison
```
1. Note metrics from "All Time"
2. Compare to "This Year"
3. Calculate growth percentage
4. Identify underperforming services
```

---

## Key Metrics Explained

### Today Sales
**= TODAY'S TOTAL REVENUE FROM ALL SERVICES**

Includes:
- ✓ Online fish orders
- ✓ Walk-in fish orders  
- ✓ Online menu orders
- ✓ Direct menu orders
- ✓ Online cottage bookings
- ✓ Walk-in cottage bookings
- ✓ Boat rental bookings
- ✓ Entrance fee collection

### This Month Sales
**= CURRENT MONTH TOTAL (from 1st to today)**

Updates as day progresses
Resets on 1st of month

### Total Sales
**= LIFETIME REVENUE (all time)**

Never changes (only grows)
Use for: Year-to-date comparisons

### Pending Orders
**= UNFULFILLED MENU ORDERS**

Shows how many orders waiting for completion
Tap to see detailed pending list (if available)

### Each Service Breakdown
Shows:
- **Total Revenue** for selected period
- **Completed Count** (transactions completed)
- **Service Badge** (Online/Walk-In/Rental/Entry)

---

## Interpretation Tips

### Revenue is Zero?
Check:
- [ ] Correct date range selected
- [ ] Any sales exist in that period
- [ ] Services are actually being used
- [ ] Database has data

### Numbers Seem Low?
Could indicate:
- Quiet business period
- Filtered to specific date range
- Some services not in use
- System startup period

### Numbers Too High?
Check:
- Not duplicated in multiple services
- All amounts are transaction totals
- No test data included
- Correct date range

### Pending Orders Growing?
Indicates:
- Backlog building up
- Staff efficiency issue
- Need more staff
- System alert!

---

## Troubleshooting

### Report Won't Load
1. Check internet connection
2. Verify admin login active
3. Clear browser cache (Ctrl+Shift+Del)
4. Try different browser
5. Contact admin if persists

### Print Has Blank Areas
1. Adjust print margins to 0.5"
2. Try landscape orientation
3. Disable "Optimize for web" option
4. Use Firefox for better print support

### CSV File Won't Open
1. File actually downloaded? Check Downloads folder
2. Wrong file type? Should be `.csv` not `.txt`
3. Excel import issue? Try opening in Google Sheets first
4. Corruption? Try another date range

### Numbers Don't Match Dashboard
This is normal! Reasons:
- Dashboard may show different period
- Time zone differences
- Real-time vs batch calculations
- Rounding differences

**Solution:** Use consistent date ranges for comparison

---

## Tips & Tricks

💡 **Pro Tips:**

1. **Save as PDF** - Print button > Save as PDF for archival
2. **Auto-Refresh** - Refresh page (F5) to get latest metrics
3. **Compare Periods** - Open 2 browser windows with different periods
4. **Email Reports** - Export CSV and email weekly for tracking
5. **Track Growth** - Compare same period year-over-year

⚡ **Keyboard Shortcuts:**
- `Ctrl+P` - Print
- `Ctrl+S` - Save (when print dialog open)
- `F5` - Refresh report
- `Ctrl+L` - Focus address bar

---

## Performance Notes

| Action | Typical Time |
|--------|-------------|
| Page Load | < 2 seconds |
| Filter Application | < 1 second |
| Print Dialog | Instant |
| CSV Download | 1-2 seconds |
| Chart Rendering | < 1 second |

**If slow:**
1. Check internet speed
2. Clear browser cache
3. Try different browser
4. Contact admin

---

## Data Freshness

- **Real-time metrics** update as transactions occur
- **Charts** may have slight delay (seconds)
- **Export data** is current as of click time
- **No caching** - always fresh data

---

## Security Note

⚠️ **Important:**
- Only admins can access reports
- All exports include sensitive data
- Keep CSV files secure
- Printed reports should be shredded when done
- Don't share directly; summarize for staff

---

## Questions?

For help:
1. Check this guide again
2. Review REPORTS_SALES_UPDATES.md for technical details
3. Contact system admin
4. Check system logs for errors

Generated: March 16, 2026
Version: 2.0
