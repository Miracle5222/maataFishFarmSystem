# Cottage Revenue Separation Implementation

## Summary
The system has been updated to properly distinguish between **online cottage reservations** and **walk-in/manual cottage reservations**.

## Changes Made

### 1. Database Schema Update
- **Added column:** `is_manual` (TINYINT(1)) to `reservations` table
  - `is_manual = 1`: Walk-in/manual reservations (from `manual_cottage_reservation.php`)
  - `is_manual = 0`: Online reservations (from `client/booking.php?type=cottage`)

### 2. Handler Updates

#### `handlers/manual_cottage_reservation_handler.php`
- Updated INSERT statement to set `is_manual = 1` for all walk-in/manual reservations
- File: [manual_cottage_reservation_handler.php](manual_cottage_reservation_handler.php)
- Change: Added `is_manual` column with value `1` to INSERT query

#### `handlers/booking_handler.php`
- Updated INSERT statement to set `is_manual = 0` for all online reservations
- File: [booking_handler.php](booking_handler.php)
- Change: Added `is_manual` column with value `0` to INSERT query

### 3. Dashboard Update

#### `index.php`
- Split cottage revenue queries into two separate queries:
  - **Online Cottage Revenue**: Queries `is_manual = 0` and `status = 'completed'`
  - **Walk-In/Manual Cottage Revenue**: Queries `is_manual = 1` and `status = 'completed'`
- File: [index.php](index.php)
- Revenue Breakdown card now shows:
  - Online Cottage
  - Manual Cottage (Walk-In)

### 4. Data Backfill
- All 11 existing cottage reservations have been marked as `is_manual = 1` (walk-in/manual)
- This maintains backward compatibility with existing data

## Verification

Current State:
```
Online Cottage Revenue: ₱0.00 (0 reservations)
Walk-In/Manual Cottage Revenue: ₱9,500.00 (11 reservations)
Total Cottage Revenue: ₱9,500.00
```

## How It Works

### New Online Cottage Booking (from client/booking.php?type=cottage)
1. Customer books cottage via online form
2. Reservation is created with `is_manual = 0`
3. When checked out via reservations_list.php → Recorded as **Online Cottage Revenue**

### New Walk-In/Manual Cottage Booking (from manual_cottage_reservation.php)
1. Staff creates walk-in reservation via manual form
2. Reservation is created with `is_manual = 1`
3. When checked out via reservations_list.php → Recorded as **Walk-In/Manual Cottage Revenue**

## Dashboard Display

The Revenue Breakdown section on the admin dashboard now shows:
- **Online Cottage**: Total revenue from online bookings (client/booking.php?type=cottage)
- **Manual Cottage (Walk-In)**: Total revenue from manual/walk-in bookings (manual_cottage_reservation.php)

Both are displayed separately in the revenue cards and pie chart.
