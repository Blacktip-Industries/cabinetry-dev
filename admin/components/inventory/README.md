# Inventory Component

Advanced, highly configurable inventory management component with barcode support, multi-location tracking, stock transfers, adjustments, advanced reporting, alerts, and multiple costing methods (FIFO, LIFO, Average Cost).

## Overview

The Inventory Component provides a complete inventory management solution that can operate standalone or integrate with the commerce component:

- **Standalone Mode**: Track inventory for any items (not tied to commerce products)
- **Commerce Integration Mode**: Link to `commerce_products` when commerce component is installed
- **Multi-Location Hierarchy**: Warehouse → Zone → Bin → Shelf tracking
- **Barcode/QR Code Support**: Generate and scan barcodes for items
- **Stock Transfers**: Transfer stock between locations with approval workflows
- **Stock Adjustments**: Adjustment requests with approval workflows
- **Advanced Reporting**: Stock levels, movements, valuation, alerts
- **Configurable Alerts**: Low stock, high stock, expiry, movement thresholds
- **Costing Methods**: FIFO, LIFO, and Average Cost calculation

## Installation

1. Navigate to `/admin/components/inventory/install.php` in your browser
2. The installer will auto-detect your database configuration
3. Click "Install Inventory Component"

For CLI installation:
```bash
php admin/components/inventory/install.php --auto
```

## Features

### Item Management
- Create/edit items independent of commerce
- Support for SKU, barcode, QR codes
- Item categories and attributes
- Integration flag to link to commerce products when available

### Location Management
- Multi-level location hierarchy (warehouse → zone → bin → shelf)
- Stock tracking at any level
- Location-based reporting
- Default location assignment

### Stock Management
- Stock level tracking per item/location
- Stock reservations for pending orders
- Stock movements with complete history
- Automatic stock updates on movements

### Barcode System
- Generate barcodes (EAN13, UPC, CODE128)
- Generate QR codes with item data
- Barcode scanning interface (web + mobile-friendly)
- Multiple barcodes per item support

### Stock Transfers
- Create transfer requests between locations
- Approval workflow (configurable)
- Transfer status tracking (pending → in_transit → completed)
- Transfer history and reporting

### Stock Adjustments
- Create adjustment requests with reason
- Approval workflow (configurable)
- Adjustment types: count, correction, damage, expiry
- Before/after quantity tracking

### Costing Methods
- **FIFO (First In, First Out)**: Track cost layers, oldest cost first
- **LIFO (Last In, First Out)**: Track cost layers, newest cost first
- **Average Cost**: Weighted average of all purchases
- Cost calculation on movements
- Inventory valuation reports

### Alert System
- Low stock alerts (per item/location or global)
- High stock alerts
- Expiry date alerts (if expiry tracking enabled)
- Movement threshold alerts
- Email notifications via `email_marketing` component (if available)

### Advanced Reporting
- Stock level reports (by location, category, item)
- Movement history reports (filtered by date, type, location)
- Inventory valuation reports (using costing method)
- Transfer reports
- Adjustment reports
- Alert reports
- Export to CSV/PDF

## Integration Points

### Commerce Component
- Detect `commerce` component via `inventory_is_commerce_available()`
- Link items to commerce products via `commerce_product_id`
- Sync stock on commerce order creation/completion
- Use commerce warehouse data if available (optional mapping)

### Email Marketing Component
- Send alert emails via `email_marketing_send_email()`
- Scheduled report delivery
- Low stock notifications

### Access Component
- Track `created_by`, `approved_by` users
- User-based permissions for adjustments/transfers

## Database Tables

The component creates the following tables:
- `inventory_config` - Component configuration
- `inventory_parameters` - Component settings
- `inventory_items` - Items (standalone or linked to commerce)
- `inventory_locations` - Multi-level location hierarchy
- `inventory_stock` - Stock levels per item/location
- `inventory_movements` - Complete movement history
- `inventory_transfers` - Stock transfer requests
- `inventory_transfer_items` - Transfer line items
- `inventory_adjustments` - Stock adjustment requests
- `inventory_adjustment_items` - Adjustment line items
- `inventory_barcodes` - Barcode/QR code management
- `inventory_costs` - Cost tracking for FIFO/LIFO
- `inventory_alerts` - Configurable alert rules
- `inventory_reports` - Report configurations

## Configuration Parameters

- `default_costing_method` (FIFO/LIFO/Average)
- `enable_commerce_integration` (yes/no)
- `require_adjustment_approval` (yes/no)
- `require_transfer_approval` (yes/no)
- `default_location_id`
- `low_stock_threshold` (global default)
- `barcode_type_default` (EAN13/CODE128)
- `enable_expiry_tracking` (yes/no)
- `alert_email_recipients` (comma-separated)

## Documentation

- [API Documentation](docs/API.md)
- [Integration Guide](docs/INTEGRATION.md)
- [Barcode System Guide](docs/BARCODES.md)
- [Costing Methods Guide](docs/COSTING.md)
- [Reporting Guide](docs/REPORTS.md)

## Version

Current version: 1.0.0

## License

Proprietary - All rights reserved

