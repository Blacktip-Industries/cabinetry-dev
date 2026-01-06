# Commerce Component

Advanced, highly configurable e-commerce component with integrated shipping management, inventory tracking, and bulk order table system.

## Overview

The Commerce Component provides a complete e-commerce solution with:

- **Full Product Management**: Products, variants, categories, images
- **Shopping Cart & Checkout**: Session and account-based carts with checkout process
- **Order Management**: Complete order lifecycle with status tracking
- **Shipping System**: Zones, methods, rate calculation, carrier integrations
- **Inventory Management**: Stock tracking, warehouses, low stock alerts
- **Bulk Order Tables**: Configurable table-based order entry (e.g., doors)
- **Component Integration**: Seamless integration with payment_processing, product_options, access, email_marketing

## Installation

1. Navigate to `/admin/components/commerce/install.php` in your browser
2. The installer will auto-detect your database configuration
3. Click "Install Commerce Component"
4. Configure settings in the admin interface

For CLI installation:
```bash
php admin/components/commerce/install.php --auto
```

## Features

### Product Management
- Product CRUD with variants
- Category management
- Product images
- Product-option linking (dynamic options based on product)
- Integration with product_options component for pricing

### Bulk Order Tables
- Configurable table structures per product type
- Dynamic column definitions (admin configurable)
- Formula-based pricing per column
- Custom validation rules per column
- Table format order entry (spreadsheet-like interface)

### Shipping System
- Shipping zones (country, state, postcode-based)
- Multiple shipping methods per zone
- Rate calculation engines:
  - Flat rate
  - Weight-based
  - Price-based
  - Carrier API integration
- Carrier integrations (plugin architecture)
- Tracking number management

### Inventory Management
- Stock tracking per product/variant
- Multi-warehouse support
- Stock movement history
- Low stock alerts (configurable thresholds)
- Stock reservations for pending orders

### Cart & Checkout
- Session-based and account-based carts
- Cart persistence
- Checkout process with shipping calculation
- Integration with payment_processing component
- Order creation workflow

### Order Management
- Order status workflow
- Order history and audit trail
- Order fulfillment tracking
- Shipment creation and tracking
- Integration with email_marketing for notifications

## Integration Points

### product_options Component
- Link products to option sets
- Dynamic option rendering based on product
- Pricing calculation using product_options formulas
- Option-based variant generation

### payment_processing Component
- Create transactions on checkout
- Link orders to payment transactions
- Handle payment status updates
- Refund processing

### access Component
- Customer account linking
- Account type-based pricing/shipping rules
- Customer order history

### email_marketing Component
- Order confirmation emails
- Shipping notifications
- Low stock alerts
- Order status updates

## Database Tables

The component creates the following tables:
- `commerce_config` - Component configuration
- `commerce_parameters` - Component settings
- `commerce_products` - Product catalog
- `commerce_product_variants` - Product variants
- `commerce_product_categories` - Product categories
- `commerce_product_images` - Product images
- `commerce_product_options` - Links products to product_options
- `commerce_inventory` - Stock levels
- `commerce_warehouses` - Warehouse locations
- `commerce_inventory_movements` - Stock movement history
- `commerce_carts` - Shopping carts
- `commerce_cart_items` - Cart line items
- `commerce_orders` - Order headers
- `commerce_order_items` - Standard order line items
- `commerce_bulk_order_tables` - Bulk order table configurations
- `commerce_bulk_order_table_columns` - Column definitions
- `commerce_bulk_order_items` - Bulk order line items
- `commerce_shipping_zones` - Shipping zones
- `commerce_shipping_methods` - Shipping methods
- `commerce_shipping_rates` - Rate calculations
- `commerce_carriers` - Carrier integrations
- `commerce_shipments` - Shipment records
- `commerce_shipment_tracking` - Tracking information

## API Functions

### Products
- `commerce_get_product($productId)` - Get product by ID
- `commerce_get_product_by_slug($slug)` - Get product by slug
- `commerce_get_products($filters)` - Get products with filters
- `commerce_get_product_variants($productId)` - Get product variants
- `commerce_calculate_product_price($productId, $variantId, $optionValues)` - Calculate price

### Cart
- `commerce_get_cart($sessionId, $accountId)` - Get or create cart
- `commerce_add_to_cart($cartId, $productId, $quantity, $variantId, $options)` - Add item to cart
- `commerce_update_cart_item($itemId, $quantity)` - Update cart item
- `commerce_remove_cart_item($itemId)` - Remove cart item
- `commerce_calculate_cart_total($cartId)` - Calculate cart total

### Checkout
- `commerce_process_checkout($cartId, $checkoutData)` - Process checkout

### Orders
- `commerce_get_order($orderId)` - Get order by ID
- `commerce_get_order_by_number($orderNumber)` - Get order by number
- `commerce_create_order($data)` - Create order
- `commerce_update_order_status($orderId, $statusType, $newStatus)` - Update order status

### Shipping
- `commerce_get_shipping_zones($filters)` - Get shipping zones
- `commerce_find_shipping_zone($address)` - Find zone for address
- `commerce_calculate_shipping_rate($cartItems, $shippingAddress, $shippingMethodId)` - Calculate shipping

### Inventory
- `commerce_get_inventory($productId, $variantId, $warehouseId)` - Get inventory
- `commerce_update_inventory($productId, $variantId, $warehouseId, $quantityChange, ...)` - Update inventory
- `commerce_reserve_inventory_for_order($orderId)` - Reserve inventory

### Bulk Orders
- `commerce_get_bulk_order_tables($productId)` - Get bulk order tables
- `commerce_get_bulk_order_table_columns($tableId)` - Get table columns
- `commerce_calculate_bulk_order_item_price($tableId, $rowData)` - Calculate bulk item price
- `commerce_validate_bulk_order_row($tableId, $rowData)` - Validate bulk row

## Configuration

Component settings can be configured in the admin interface or via parameters:
- Default currency
- Tax settings
- Shipping zones and methods
- Inventory thresholds
- Bulk order settings
- Integration settings

## Documentation

- [API Documentation](docs/API.md)
- [Integration Guide](docs/INTEGRATION.md)
- [Bulk Orders Guide](docs/BULK_ORDERS.md)
- [Shipping Guide](docs/SHIPPING.md)

## Version

Current version: 1.0.0

## License

Proprietary - All rights reserved

