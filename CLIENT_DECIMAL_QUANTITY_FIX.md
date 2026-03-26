# Client Fish Ordering - Decimal Quantity Fix

## Problem
When ordering fish from `http://localhost/maataFishFarmSystem/client/index.php`, entering decimal quantities (0.5, 0.6 kg, etc.) was not calculating prices correctly. The system was rounding to whole numbers instead of supporting partial kilogram purchases.

## Solution
Updated the entire fish ordering flow to properly support decimal quantities with accurate price calculations, matching the functionality in `admin_fish_order.php`.

## Changes Made

### 1. Frontend - Quantity Input
**File:** [client/index.php](client/index.php#L80)
- Changed input field attributes:
  - From: `min="1"` 
  - To: `min="0.1" step="0.1"`
- Now supports entering quantities like 0.5, 0.6, 0.2, etc.

### 2. JavaScript - Add to Cart Function
**File:** [client/partials/header.php](client/partials/header.php#L309)
- Changed: `parseInt(qtyEl.value)` → `parseFloat(qtyEl.value)`
- Added validation for positive quantities
- Now correctly reads decimal values from input field
- Fixed: 0.5 is now read as 0.5, not converted to 0 or 1

### 3. PHP Cart API - Add Item Handler
**File:** [handlers/client_cart_api.php](handlers/client_cart_api.php)

#### Line 42: Fetch Cart Items
- Changed: `$qty = (int)$row['quantity'];`
- To: `$qty = (float)$row['quantity'];`

#### Line 87: Add Item Request
- Changed: `$quantity = max(1, (int) ($_POST['quantity'] ?? 1));`
- To: `$quantity = max(0.1, (float) ($_POST['quantity'] ?? 1));`

#### Line 165: Update Existing Item
- Changed: `$newQty = (int)$existing['quantity'] + $quantity;`
- To: `$newQty = (float)$existing['quantity'] + $quantity;`

#### Line 181: Update Bind Parameter
- Changed: `$upd->bind_param('ii', $newQty, $existing['id']);`
- To: `$upd->bind_param('di', $newQty, $existing['id']);`
- Note: 'd' means double (float type)

### 4. PHP Cart API - Update Item Handler
**File:** [handlers/client_cart_api.php](handlers/client_cart_api.php#L236)
- Changed: `$quantity = max(0, (int) ($_POST['quantity'] ?? 0));`
- To: `$quantity = max(0.1, (float) ($_POST['quantity'] ?? 0));`

- Changed: `$stmt->bind_param('iii', $quantity, $cart_id, $cid);`
- To: `$stmt->bind_param('dii', $quantity, $cart_id, $cid);`

### 5. Cart Display - Price Breakdown
**File:** [client/cart.php](client/cart.php#L207)

#### Cart Item Display
Now shows detailed price breakdown:
```
₱100.00/kg × 0.5 kg
₱50.00
```

Instead of just:
```
₱50.00
x0.5
```

#### Quantity Display
- Shows quantity with 2 decimal places: `0.50` instead of `0`
- Min width increased from 24px to 40px to accommodate decimal display

#### Order Summary
Shows clear breakdown in checkout summary:
```
Fish Name
₱100.00/kg × 0.5 kg
₱50.00
```

### 6. Cart Item Operations
**Minus/Plus Buttons:** [client/cart.php](client/cart.php#L193-L205)
- Changed increment/decrement from ±1 to ±0.1
- Uses proper rounding: `Math.round((quantity ± 0.1) * 10) / 10`
- Minimum allowed: 0.1 kg

## Price Calculation Formula

### Before (Incorrect)
```
Quantity Input: 0.5 kg
Parsed As: 0 (parseInt rounds down)
Display: Full price (not discounted)
Total: ₱100.00 (WRONG)
```

### After (Correct)
```
Quantity Input: 0.5 kg
Parsed As: 0.5 (parseFloat preserves decimal)
Calculation: 0.5 × ₱100.00 = ₱50.00
Display Breakdown: ₱100.00/kg × 0.5 kg = ₱50.00
Total: ₱50.00 (CORRECT)
```

## Examples

### Scenario 1: Order 0.6 kg of Fish @ ₱200/kg
1. Customer enters: `0.6`
2. System calculates: `0.6 × ₱200 = ₱120`
3. Cart displays: 
   - `₱200.00/kg × 0.6 kg`
   - `₱120.00`

### Scenario 2: Order 0.5 kg of Fish @ ₱300/kg
1. Customer enters: `0.5`
2. System calculates: `0.5 × ₱300 = ₱150`
3. Cart displays:
   - `₱300.00/kg × 0.5 kg`
   - `₱150.00`

### Scenario 3: Adjust quantity using +/- buttons
1. Start with: 0.5 kg
2. Click `+`: becomes 0.6 kg 
3. Click `+` again: becomes 0.7 kg
4. Click `−`: becomes 0.6 kg (increments of 0.1)

## Data Types in Database

The `carts` table already stores `quantity` as DECIMAL(10,2), which supports decimal values perfectly:
- ✅ 0.1, 0.2, 0.5, 0.6, 1.0, etc. all supported
- ✅ Precise price calculation without rounding errors

## Compatibility

✅ Works with existing cart system
✅ Compatible with admin_fish_order.php workflow (same decimal support)
✅ Database schema unchanged (already supports decimals)
✅ No breaking changes to existing orders

## Testing Checklist

- [ ] Enter 0.5 kg - verify total price is half of unit price
- [ ] Enter 0.6 kg - verify correct calculation
- [ ] Use +/− buttons to adjust - verify 0.1 increments
- [ ] Multiple items in cart - verify each calculated correctly
- [ ] Checkout with decimal quantities - verify order created with correct amounts
- [ ] Mobile/responsive - verify quantity display fits in card
