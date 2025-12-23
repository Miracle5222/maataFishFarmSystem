# Sales Report Print & Detail Enhancements

## Overview
Enhanced the Sales Report (reports_sales.php) with more detailed data, better formatting, and comprehensive print functionality. The report now displays additional relevant information for analysis and includes professional print-ready output.

## Changes Made

### 1. **Print Header Section**
- Added professional print header with:
  - **Farm Name**: "MAATA FISH FARM SYSTEM"
  - **Report Type**: "Sales Report"
  - **Period Information**: Shows selected date range
  - **Generation Timestamp**: Date and time the report was generated
- Hidden on screen display but shows on print with proper formatting

### 2. **Print Summary Metrics**
- Added summary metrics section that displays:
  - **Total Revenue**: Overall sales amount
  - **Total Orders**: Number of orders processed
  - **Unique Customers**: Count of individual customers
  - **Average Order Value**: Mean order amount
- Displays as flexbox layout on print for professional appearance
- Automatically calculates metrics from data

### 3. **Print-Only Summary & Insights Section**
- Added comprehensive report summary visible only on print:
  
  **Sales Overview:**
  - Total Revenue with currency formatting
  - Total Orders count
  - Average Order Value
  - Unique Customers
  - Top Category with revenue amount

  **Top Performers:**
  - Best Selling Product with revenue
  - Top Fish Species with quantity in kg
  - Top Menu Item
  - Primary Order Status with order count

  **Footer Note**: Explains data sources (both traditional and menu orders) and contact info

### 4. **Enhanced Table Headers with Descriptive Titles**

#### Category Performance Table
- **Column Headers**:
  - Category
  - Orders (Number of Orders) [with tooltip]
  - Qty Sold (Total Quantity Sold) [with tooltip]
  - Total Sales (Total Revenue) [with tooltip]
  - Avg Price (Average Unit Price) [with tooltip]
  - **% of Total** [Hidden, visible in print] - Shows percentage of total revenue

#### Top 10 Best Sellers Table
- **Column Headers**:
  - Item Name
  - Category
  - Units/Qty Sold (Quantity Sold) [with tooltip]
  - Total Sales (Total Revenue) [with tooltip]
  - Orders (Number of Orders) [with tooltip]
  - Avg Price (Average Unit Price) [with tooltip]
  - **Avg Unit Price** [Hidden, visible in print]

#### Fish Species Sales Table
- **Column Headers**:
  - Fish Species
  - Units Sold (kg) [with tooltip showing "Quantity in Kilograms"]
  - Total Sales (Total Revenue) [with tooltip]
  - Orders (Number of Orders) [with tooltip]
  - Avg Price/kg (Average Price Per Unit) [with tooltip]
  - **Market Demand** [Hidden, visible in print] - High/Low demand indicator

#### Menu Orders Summary Table
- **Column Headers**:
  - Order Status (with color-coded badges: green=paid, blue=confirmed, orange=pending, red=cancelled)
  - Orders (Number of Orders) [with tooltip]
  - Total Sales (Total Revenue) [with tooltip]
  - Avg Order Value (Average Order Value) [with tooltip]
  - **% Share** [Hidden, visible in print] - Shows percentage of total menu revenue

#### Top Menu Items Table
- **Column Headers**:
  - Item Name
  - Type (with badges: blue=Product, info-blue=Fish) [with tooltip]
  - Quantity Sold (Quantity in units or kg) [with tooltip]
  - Total Sales (Total Revenue) [with tooltip]
  - Orders (Number of Orders) [with tooltip]
  - Avg Price (Average Price per Unit) [with tooltip]
  - **% Sold** [Hidden, visible in print] - Percentage of total items sold
  - **Popularity** [Hidden, visible in print] - High/Medium/Low based on order count

### 5. **Print CSS Enhancements**
- Updated @media print styles:
  - Shows print header and summary with `display: block !important` and `display: flex !important`
  - Makes all hidden columns visible: `table td[style*="display:none"] { display: table-cell !important; }`
  - Ensures print-only sections display: `.print-only { display: block !important; }`
  - Compact font size (11px) for better print layout
  - Proper table borders and spacing for readability
  - Hides navigation, buttons, and charts
  - Prevents page breaks within cards and rows

### 6. **JavaScript Print Event Handlers**
- Added `beforeprint` event listener:
  - Shows print header element
  - Shows print summary metrics
  - Removes card shadows for cleaner print
  - Applies simple borders instead

- Added `afterprint` event listener:
  - Hides print header element
  - Hides print summary metrics
  - Restores card styling

### 7. **Enhanced CSV Export**
- CSV file now includes:
  - Summary metrics section at top (Total Revenue, Total Orders, Average Order Value, Unique Customers)
  - Farm name and period information
  - All data tables with descriptive headers
  - All columns including hidden detail columns
  - Proper formatting:
    - Currency values without ₱ symbol (just numbers for Excel)
    - Quantities with units
    - Percentages with % symbol
    - Status names properly capitalized
  - Report generation timestamp
  - Professional separator lines

## How to Use

### Printing the Report
1. Open reports_sales.php in a web browser
2. Select date range and click "Filter Report"
3. Click **Print Report** button or use Ctrl+P
4. In the print dialog:
   - Print header and summary will appear at top
   - All tables include hidden detail columns
   - Chart.js visualizations are hidden (not suitable for print)
   - Professional layout with proper spacing

### Exporting to CSV
1. Open reports_sales.php and filter data
2. Click **Export to CSV** button
3. Opens file download with name format: `sales-report-YYYY-MM-DD.csv`
4. File includes summary section, all tables, and all columns with proper formatting

## Data Sources
- **Traditional Orders System**: orders → order_items table
- **Menu Orders System**: menu_orders → menu_order_items table
- **Products**: products table (categories)
- **Fish Species**: fish_species table
- All systems combined with UNION queries for comprehensive reporting

## Notes
- Hidden columns (display:none) are displayed in print and CSV for detailed analysis
- Print output is optimized for A4 paper size
- All currency values shown in Philippine Pesos (₱)
- Column tooltips (title attributes) provide descriptions on hover
- Color-coded badges help identify order status at a glance
- Top performers automatically calculate based on filtered data

## Files Modified
- `c:\xampp\htdocs\maataFishFarmSystem\reports_sales.php`
  - Lines 975-1010: Enhanced print CSS
  - Lines 1015-1050: Print event handlers
  - Lines 1100-1190: Print header and summary sections
  - Lines 1280-1320: Category Performance table enhancements
  - Lines 1325-1355: Top 10 Best Sellers table enhancements
  - Lines 1360-1400: Fish Species Sales table enhancements
  - Lines 1410-1450: Menu Orders Summary table enhancements
  - Lines 1455-1510: Top Menu Items table enhancements
  - Lines 1640-1690: Print-only summary & insights section
