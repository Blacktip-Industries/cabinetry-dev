# Inventory Component - Barcodes Guide

## Overview

The Inventory Component includes comprehensive barcode support for tracking and managing inventory items. This guide covers barcode generation, scanning, and management.

## Supported Barcode Types

### CODE128
- **Description:** High-density linear barcode
- **Use Case:** General purpose, supports alphanumeric characters
- **Length:** Variable (up to 80 characters)
- **Best For:** Internal inventory tracking

### EAN13
- **Description:** European Article Number, 13 digits
- **Use Case:** Retail products, international standard
- **Length:** 13 digits (12 data + 1 check digit)
- **Best For:** Products sold in retail

### UPC
- **Description:** Universal Product Code, 12 digits
- **Use Case:** North American retail standard
- **Length:** 12 digits (11 data + 1 check digit)
- **Best For:** Products sold in North America

### QR Code
- **Description:** 2D matrix barcode
- **Use Case:** Can store large amounts of data, scannable with smartphones
- **Length:** Variable (up to 4,296 characters)
- **Best For:** Mobile scanning, detailed information storage

## Generating Barcodes

### Via Admin Interface

1. Navigate to **Inventory > Barcodes > Generate**
2. Select an item
3. Choose barcode type
4. Optionally enter custom barcode value
5. Set as primary if needed
6. Click "Generate Barcode"

### Via API

```php
$result = inventory_create_barcode(
    $itemId = 1,
    $barcodeType = 'CODE128',
    $barcodeValue = null, // Auto-generate if null
    $isPrimary = 1
);

if ($result['success']) {
    echo "Barcode: " . $result['barcode_value'];
}
```

### Auto-Generation

If no custom barcode value is provided, the system auto-generates based on:
- Item ID
- Item Code
- Timestamp
- Random component

Format: `{PREFIX}-{ITEM_ID}-{TIMESTAMP}`

## Primary Barcodes

Each item can have one primary barcode. The primary barcode is:
- Used as default for scanning
- Displayed prominently in item views
- Used for quick lookup operations

### Setting Primary Barcode

1. Via Admin: Click "Set Primary" on any barcode
2. Via API: Set `is_primary = 1` when creating barcode
3. Automatically: First barcode created becomes primary

## Scanning Barcodes

### Via Admin Interface

1. Navigate to **Inventory > Barcodes > Scan**
2. Use barcode scanner or enter manually
3. System automatically looks up item
4. View item details and stock levels

### Via API

```php
$item = inventory_scan_barcode('1234567890123');
if ($item) {
    echo "Found: " . $item['item_name'];
} else {
    echo "Barcode not found";
}
```

### Barcode Scanner Setup

#### USB Barcode Scanners

Most USB barcode scanners work as keyboard input:
1. Connect scanner via USB
2. Scanner appears as keyboard
3. Scan barcode - input appears in text field
4. System auto-submits on Enter key

#### Mobile Scanning

For mobile devices:
1. Use camera to scan QR codes
2. Use mobile barcode scanner apps
3. Enter barcode manually if needed

## Barcode Management

### Viewing Barcodes

- **All Barcodes:** Inventory > Barcodes
- **Item Barcodes:** Inventory > Items > View Item > Barcodes tab
- **Filter by Item:** Use item filter in barcodes list

### Editing Barcodes

Barcodes cannot be edited once created. To change:
1. Delete old barcode
2. Create new barcode with desired value

### Deleting Barcodes

1. Navigate to barcode list
2. Click "Delete" on barcode
3. Confirm deletion
4. Note: Cannot delete primary barcode if it's the only one

## Barcode Printing

### Print Individual Barcode

1. View item details
2. Go to Barcodes tab
3. Click "Print" on barcode
4. Print dialog opens with formatted barcode

### Bulk Printing

1. Navigate to Items list
2. Select items to print
3. Click "Print Barcodes"
4. Generate print-ready labels

### Label Format

Barcode labels include:
- Item name
- Item code
- Barcode value
- Barcode image (if library installed)
- Optional: Location, quantity

## Integration with Other Systems

### POS Systems

Barcodes can be used with Point of Sale systems:
1. Scan barcode at checkout
2. System looks up item
3. Retrieves price and stock
4. Updates inventory on sale

### Warehouse Management

For warehouse operations:
1. Scan barcode to identify item
2. Verify location
3. Update stock levels
4. Record movements

## Best Practices

### Barcode Selection

- **Internal Use:** CODE128 (flexible, supports alphanumeric)
- **Retail Products:** EAN13 or UPC (industry standard)
- **Mobile Apps:** QR Code (scannable with phones)
- **Small Items:** EAN13 or UPC (compact)
- **Large Items:** CODE128 or QR (more data capacity)

### Barcode Management

1. **One Primary Per Item:** Keep one primary barcode
2. **Consistent Format:** Use same format for similar items
3. **Regular Audits:** Verify barcodes match items
4. **Backup System:** Keep manual records as backup

### Label Quality

1. **Print Quality:** Use high-resolution printing
2. **Label Material:** Use durable labels for harsh environments
3. **Size:** Ensure barcode is scannable size
4. **Placement:** Place labels where easily accessible

## Troubleshooting

### Barcode Not Scanning

1. Check barcode format is correct
2. Verify barcode value exists in system
3. Check scanner compatibility with barcode type
4. Ensure label is not damaged or dirty

### Duplicate Barcodes

The system prevents duplicate barcode values. If you need similar barcodes:
- Use different barcode types
- Add prefixes or suffixes
- Use item-specific codes

### Missing Barcodes

If barcode is missing:
1. Check if item has barcodes assigned
2. Verify barcode was not deleted
3. Generate new barcode if needed

## API Reference

See `API.md` for complete barcode API documentation including:
- `inventory_create_barcode()`
- `inventory_scan_barcode()`
- `inventory_get_item_barcodes()`
- `inventory_set_primary_barcode()`
- `inventory_delete_barcode()`

## Advanced Features

### Custom Barcode Formats

For custom barcode formats:
1. Extend barcode generation function
2. Add validation rules
3. Update admin interface
4. Test thoroughly

### Barcode Validation

The system validates:
- Format compliance (EAN13, UPC checksums)
- Uniqueness
- Length requirements
- Character restrictions

### Batch Operations

For bulk operations:
1. Use API functions in loops
2. Implement error handling
3. Log all operations
4. Verify results

