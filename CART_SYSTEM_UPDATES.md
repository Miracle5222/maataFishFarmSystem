# Cart System Updates - Fish-Based Structure

## Overview
Updated the entire ordering system to work with the new `carts` table structure that uses `fish_id` instead of `product_id`. The system now exclusively handles fish orders from the `fish_species` table.

## Database Structure
```sql
CREATE TABLE `carts` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `fish_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `unique_cart_item` (`customer_id`,`fish_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_product` (`fish_id`),
  CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
)
```

## Files Updated

### 1. **handlers/client_cart_api.php**
- Changed column references from `product_id` to `fish_id`
- Modified GET list action to fetch fish data from `fish_species` table
- Updated POST add action to query only `fish_species` table
- Removed product table lookups entirely
- Unit is hardcoded to 'kg' for all fish items

**Key Changes:**
```php
// List cart items for user
SELECT c.id, c.fish_id, c.quantity, c.unit_price FROM carts WHERE customer_id = ?

// Add to cart
SELECT id, name, price_per_kg, stock FROM fish_species WHERE id = ? AND status = "available"

// Cart operations
INSERT/UPDATE/DELETE using fish_id column
```

### 2. **client/cart.php**
- Updated JavaScript to use `fish_id` from API responses
- Modified cart payload conversion to use `fish_id` instead of `product_id`

**Key Changes:**
```javascript
// Line 280: Changed from product_id to fish_id
var cartData = cartItems.map(function(it) {
    return { id: it.fish_id, name: it.name, price: it.unit_price, unit: it.unit, qty: it.quantity };
});
```

### 3. **handlers/client_cart_order.php**
- Changed product lookup to query `fish_species` table instead of `products`
- Updated stock decrement to use `fish_species.stock` instead of `products.stock_quantity`
- All item processing now uses `fish_id` field

**Key Changes:**
```php
// Fetch fish data
SELECT id, name, price_per_kg, stock FROM fish_species WHERE id = ? AND status = "available"

// Decrement fish stock
UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE id = ?
```

### 4. **handlers/client_order.php**
- Changed product lookup to use `fish_species` table
- Updated stock decrement to work with fish stock
- Adapted for direct order placement from booking page

**Key Changes:**
```php
// Same structure as client_cart_order.php
SELECT id, name, price_per_kg, stock FROM fish_species WHERE id = ? AND status = "available"
UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE id = ?
```

## Order Flow

### Add to Cart
1. User clicks "Add to Cart" on fish menu
2. `addToCart()` in header.php sends `product_id` and `quantity` to API
3. `client_cart_api.php` receives POST, validates fish exists in `fish_species`
4. Stores in `carts` table with `fish_id`, `customer_id`, `unit_price`, `quantity`

### View Cart
1. User opens cart page (`client/cart.php`)
2. JavaScript calls `client_cart_api.php?action=list`
3. API queries `carts` table, joins with `fish_species` for fish names
4. Returns formatted items with `fish_id`, `name`, `quantity`, `unit_price`, `subtotal`
5. Page renders cart items with update/remove buttons

### Checkout
1. User completes checkout form with delivery info
2. Form submits POST with cart JSON payload to `client_cart_order.php`
3. Handler validates each fish item in `fish_species` table
4. Creates order in `orders` table
5. Inserts items in `order_items` with fish reference
6. **Decrements stock in `fish_species` table**
7. Clears cart: `DELETE FROM carts WHERE customer_id = ?`

## Price Fields
- **Fish Price:** `fish_species.price_per_kg` → stored as `unit_price` in cart
- **Order Items:** References `fish_species` via `product_id` field (currently same ID)
- **Unit:** Hardcoded to 'kg' for all fish items

## Testing Checklist
- [ ] Add fish to cart successfully
- [ ] View cart displays fish names and prices correctly
- [ ] Update quantity in cart works
- [ ] Remove item from cart works
- [ ] Proceed to checkout loads delivery form
- [ ] Place order creates order record
- [ ] Fish stock decrements after order
- [ ] Cart clears after successful order
- [ ] Order number displays on success page

## Notes
- System now exclusively handles `fish_species` orders
- No products from `products` table can be added to cart
- Each customer can only have 1 quantity per fish (UNIQUE constraint on customer_id + fish_id)
- Stock is managed in `fish_species.stock` field
- All order items reference fish IDs in `order_items.product_id` column
