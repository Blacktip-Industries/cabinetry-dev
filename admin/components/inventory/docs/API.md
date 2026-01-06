# Inventory Component - API Documentation

## Overview

The Inventory Component provides a comprehensive API for managing inventory items, locations, stock levels, movements, transfers, adjustments, barcodes, costing, alerts, and reports.

## Core Functions

### Items Management

#### `inventory_get_item($itemId)`
Get a single inventory item by ID.

**Parameters:**
- `$itemId` (int): Item ID

**Returns:** Array with item data or null

#### `inventory_get_items($filters = [], $limit = 100, $offset = 0)`
Get list of inventory items with optional filters.

**Parameters:**
- `$filters` (array): Filter options (is_active, category, search, etc.)
- `$limit` (int): Maximum number of items to return
- `$offset` (int): Number of items to skip

**Returns:** Array of items

#### `inventory_save_item($itemData)`
Create or update an inventory item.

**Parameters:**
- `$itemData` (array): Item data including:
  - `id` (int, optional): Item ID for updates
  - `item_name` (string, required): Item name
  - `item_code` (string, required): Item code/SKU
  - `category` (string, optional): Category
  - `description` (string, optional): Description
  - `unit_of_measure` (string, optional): Unit of measure
  - `reorder_point` (int, optional): Reorder point
  - `reorder_quantity` (int, optional): Reorder quantity
  - `is_active` (int, optional): Active status (1 or 0)

**Returns:** Array with `success` (bool) and `id` (int) or `error` (string)

#### `inventory_delete_item($itemId)`
Delete an inventory item.

**Parameters:**
- `$itemId` (int): Item ID

**Returns:** Array with `success` (bool) or `error` (string)

### Locations Management

#### `inventory_get_location($locationId)`
Get a single location by ID.

**Parameters:**
- `$locationId` (int): Location ID

**Returns:** Array with location data or null

#### `inventory_get_locations($filters = [])`
Get list of locations with optional filters.

**Parameters:**
- `$filters` (array): Filter options (is_active, parent_id, etc.)

**Returns:** Array of locations

#### `inventory_save_location($locationData)`
Create or update a location.

**Parameters:**
- `$locationData` (array): Location data

**Returns:** Array with `success` (bool) and `id` (int) or `error` (string)

### Stock Management

#### `inventory_get_stock($itemId, $locationId)`
Get stock level for an item at a location.

**Parameters:**
- `$itemId` (int): Item ID
- `$locationId` (int): Location ID

**Returns:** Array with stock data or null

#### `inventory_update_stock($itemId, $locationId, $quantityChange, $movementType, $referenceType = null, $referenceId = null, $notes = null)`
Update stock level for an item.

**Parameters:**
- `$itemId` (int): Item ID
- `$locationId` (int): Location ID
- `$quantityChange` (int): Quantity change (positive for increase, negative for decrease)
- `$movementType` (string): Movement type (in, out, adjustment, transfer, reservation, release)
- `$referenceType` (string, optional): Reference type
- `$referenceId` (int, optional): Reference ID
- `$notes` (string, optional): Notes

**Returns:** Array with `success` (bool) or `error` (string)

#### `inventory_reserve_stock($itemId, $locationId, $quantity)`
Reserve stock for an order.

**Parameters:**
- `$itemId` (int): Item ID
- `$locationId` (int): Location ID
- `$quantity` (int): Quantity to reserve

**Returns:** Array with `success` (bool) or `error` (string)

### Movements

#### `inventory_record_movement($movementData)`
Record an inventory movement.

**Parameters:**
- `$movementData` (array): Movement data

**Returns:** Array with `success` (bool) and `id` (int) or `error` (string)

#### `inventory_get_movements($filters = [], $limit = 100, $offset = 0)`
Get list of movements with filters.

**Parameters:**
- `$filters` (array): Filter options
- `$limit` (int): Maximum number of records
- `$offset` (int): Offset

**Returns:** Array of movements

### Transfers

#### `inventory_create_transfer($transferData)`
Create a stock transfer.

**Parameters:**
- `$transferData` (array): Transfer data including:
  - `from_location_id` (int, required)
  - `to_location_id` (int, required)
  - `items` (array, required): Array of items with item_id and quantity
  - `notes` (string, optional)

**Returns:** Array with `success` (bool) and `id` (int) or `error` (string)

#### `inventory_approve_transfer($transferId)`
Approve a pending transfer.

**Parameters:**
- `$transferId` (int): Transfer ID

**Returns:** Array with `success` (bool) or `error` (string)

#### `inventory_process_transfer_ship($transferId, $shippedItems)`
Process transfer shipment.

**Parameters:**
- `$transferId` (int): Transfer ID
- `$shippedItems` (array): Array of item_id => quantity

**Returns:** Array with `success` (bool) or `error` (string)

#### `inventory_complete_transfer($transferId, $receivedItems)`
Complete transfer receipt.

**Parameters:**
- `$transferId` (int): Transfer ID
- `$receivedItems` (array): Array of item_id => quantity

**Returns:** Array with `success` (bool) or `error` (string)

### Adjustments

#### `inventory_create_adjustment($adjustmentData)`
Create a stock adjustment.

**Parameters:**
- `$adjustmentData` (array): Adjustment data including:
  - `location_id` (int, required)
  - `adjustment_type` (string, required): count, correction, damage, expiry, other
  - `items` (array, required): Array of items with item_id, quantity_after, unit_cost, reason
  - `reason` (string, optional)
  - `notes` (string, optional)

**Returns:** Array with `success` (bool) and `id` (int) or `error` (string)

#### `inventory_approve_adjustment($adjustmentId)`
Approve and process an adjustment.

**Parameters:**
- `$adjustmentId` (int): Adjustment ID

**Returns:** Array with `success` (bool) or `error` (string)

### Barcodes

#### `inventory_create_barcode($itemId, $barcodeType, $barcodeValue = null, $isPrimary = 0)`
Create a barcode for an item.

**Parameters:**
- `$itemId` (int): Item ID
- `$barcodeType` (string): Barcode type (CODE128, EAN13, UPC, QR)
- `$barcodeValue` (string, optional): Custom barcode value (auto-generated if null)
- `$isPrimary` (int): Set as primary barcode (1 or 0)

**Returns:** Array with `success` (bool) and barcode data or `error` (string)

#### `inventory_scan_barcode($barcodeValue)`
Look up an item by barcode.

**Parameters:**
- `$barcodeValue` (string): Barcode value to scan

**Returns:** Array with item data or null

### Costing

#### `inventory_calculate_item_cost($itemId, $locationId, $quantity = null)`
Calculate item cost using configured costing method.

**Parameters:**
- `$itemId` (int): Item ID
- `$locationId` (int): Location ID
- `$quantity` (int, optional): Quantity to calculate cost for

**Returns:** Float cost value

#### `inventory_get_costing_method()`
Get current costing method.

**Returns:** String (FIFO, LIFO, or Average)

### Alerts

#### `inventory_create_alert($alertData)`
Create an alert rule.

**Parameters:**
- `$alertData` (array): Alert data including:
  - `alert_type` (string, required): low_stock, high_stock, expiry, movement_threshold
  - `item_id` (int, optional): Item ID (null for all items)
  - `location_id` (int, optional): Location ID (null for all locations)
  - `threshold_quantity` (int, optional): Threshold quantity
  - `threshold_value` (float, optional): Threshold value
  - `alert_email` (string, optional): Alert email
  - `alert_recipients` (array, optional): Additional email recipients
  - `is_active` (int): Active status (1 or 0)

**Returns:** Array with `success` (bool) and `id` (int) or `error` (string)

#### `inventory_check_alerts($itemId = null, $locationId = null)`
Check for triggered alerts.

**Parameters:**
- `$itemId` (int, optional): Item ID to check
- `$locationId` (int, optional): Location ID to check

**Returns:** Array of triggered alerts

### Reports

#### `inventory_generate_stock_level_report($filters = [])`
Generate stock levels report.

**Parameters:**
- `$filters` (array): Filter options

**Returns:** Array of stock level records

#### `inventory_generate_movement_report($filters = [])`
Generate movement history report.

**Parameters:**
- `$filters` (array): Filter options

**Returns:** Array of movement records

#### `inventory_generate_valuation_report($filters = [])`
Generate inventory valuation report.

**Parameters:**
- `$filters` (array): Filter options

**Returns:** Array with `items` (array) and `total_valuation` (float)

## Helper Functions

### `inventory_format_currency($amount, $currency = 'USD')`
Format amount as currency.

### `inventory_format_date($date, $format = 'Y-m-d')`
Format date string.

### `inventory_get_parameter($name, $default = null)`
Get component parameter value.

### `inventory_set_parameter($name, $value)`
Set component parameter value.

### `inventory_is_component_available($componentName)`
Check if another component is available.

### `inventory_is_installed()`
Check if inventory component is installed.

## Error Handling

All functions return arrays with:
- `success` (bool): Whether operation succeeded
- `error` (string): Error message if failed
- `id` (int): ID of created/updated record (if applicable)

## Examples

### Create an Item
```php
$itemData = [
    'item_name' => 'Widget A',
    'item_code' => 'WID-A-001',
    'category' => 'Widgets',
    'reorder_point' => 10,
    'reorder_quantity' => 50,
    'is_active' => 1
];

$result = inventory_save_item($itemData);
if ($result['success']) {
    echo "Item created with ID: " . $result['id'];
}
```

### Update Stock
```php
$result = inventory_update_stock(
    $itemId = 1,
    $locationId = 1,
    $quantityChange = 10,
    $movementType = 'in',
    $referenceType = 'purchase',
    $referenceId = 123,
    $notes = 'Received from supplier'
);
```

### Create Transfer
```php
$transferData = [
    'from_location_id' => 1,
    'to_location_id' => 2,
    'items' => [
        ['item_id' => 1, 'quantity' => 5],
        ['item_id' => 2, 'quantity' => 10]
    ],
    'notes' => 'Transfer to warehouse'
];

$result = inventory_create_transfer($transferData);
```

