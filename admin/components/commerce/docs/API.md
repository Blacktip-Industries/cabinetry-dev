# Commerce Component - API Documentation

## Overview

The Commerce Component provides a comprehensive API for managing products, carts, orders, shipping, and inventory.

## Core Functions

### Products

#### `commerce_get_product($productId)`
Get product by ID.

**Parameters:**
- `$productId` (int) - Product ID

**Returns:**
```php
[
    'id' => 1,
    'product_name' => 'Product Name',
    'slug' => 'product-name',
    'base_price' => 99.99,
    // ... other fields
]
```

#### `commerce_get_products($filters)`
Get products with filters.

**Parameters:**
- `$filters` (array) - Filter options
  - `category_id` (int) - Filter by category
  - `is_active` (bool) - Filter by active status
  - `search` (string) - Search term
  - `limit` (int) - Results limit
  - `offset` (int) - Results offset

**Returns:** Array of products

### Cart

#### `commerce_get_cart($sessionId, $accountId)`
Get or create cart.

**Parameters:**
- `$sessionId` (string|null) - Session ID
- `$accountId` (int|null) - Account ID

**Returns:** Cart data array

#### `commerce_add_to_cart($cartId, $productId, $quantity, $variantId, $options)`
Add item to cart.

**Parameters:**
- `$cartId` (int) - Cart ID
- `$productId` (int) - Product ID
- `$quantity` (int) - Quantity
- `$variantId` (int|null) - Variant ID
- `$options` (array) - Option values

**Returns:**
```php
['success' => true, 'item_id' => 123]
```

### Checkout

#### `commerce_process_checkout($cartId, $checkoutData)`
Process checkout and create order.

**Parameters:**
- `$cartId` (int) - Cart ID
- `$checkoutData` (array) - Checkout data
  - `customer_email` (string) - Customer email
  - `customer_name` (string) - Customer name
  - `billing_address` (array) - Billing address
  - `shipping_address` (array) - Shipping address
  - `shipping_method_id` (int) - Shipping method ID
  - `payment_method` (string) - Payment method
  - `gateway_key` (string) - Payment gateway key

**Returns:**
```php
[
    'success' => true,
    'order_id' => 123,
    'order_number' => 'ORD-20250127-ABC123'
]
```

### Orders

#### `commerce_get_order($orderId)`
Get order by ID.

**Parameters:**
- `$orderId` (int) - Order ID

**Returns:** Order data array

#### `commerce_update_order_status($orderId, $statusType, $newStatus)`
Update order status.

**Parameters:**
- `$orderId` (int) - Order ID
- `$statusType` (string) - Status type ('order', 'payment', 'shipping')
- `$newStatus` (string) - New status value

**Returns:**
```php
['success' => true]
```

### Shipping

#### `commerce_calculate_shipping_rate($cartItems, $shippingAddress, $shippingMethodId)`
Calculate shipping cost.

**Parameters:**
- `$cartItems` (array) - Cart items
- `$shippingAddress` (array) - Shipping address
- `$shippingMethodId` (int) - Shipping method ID

**Returns:**
```php
[
    'success' => true,
    'cost' => 15.99,
    'method' => [...]
]
```

### Inventory

#### `commerce_update_inventory($productId, $variantId, $warehouseId, $quantityChange, ...)`
Update inventory quantity.

**Parameters:**
- `$productId` (int) - Product ID
- `$variantId` (int|null) - Variant ID
- `$warehouseId` (int) - Warehouse ID
- `$quantityChange` (int) - Quantity change (positive for increase, negative for decrease)
- `$movementType` (string) - Movement type
- `$referenceType` (string|null) - Reference type
- `$referenceId` (int|null) - Reference ID

**Returns:**
```php
[
    'success' => true,
    'inventory_id' => 123,
    'quantity' => 50
]
```

### Bulk Orders

#### `commerce_get_bulk_order_tables($productId)`
Get bulk order tables for product.

**Parameters:**
- `$productId` (int) - Product ID

**Returns:** Array of bulk order tables

#### `commerce_calculate_bulk_order_item_price($tableId, $rowData)`
Calculate bulk order item price.

**Parameters:**
- `$tableId` (int) - Table ID
- `$rowData` (array) - Row data

**Returns:** (float) Calculated price

