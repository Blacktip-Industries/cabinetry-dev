# Order Management Component

Advanced, highly configurable order management component that enhances and extends the existing commerce orders system with comprehensive workflow automation, fulfillment management, advanced reporting, returns processing, multi-channel support, and deep integrations.

## Overview

The Order Management Component provides a complete order management solution with:

- **Custom Order Status Workflows**: Define custom status sequences with approval requirements
- **Advanced Fulfillment Management**: Multi-warehouse fulfillment, picking lists, packing interface
- **Automation Rules Engine**: Trigger-based automation with conditional actions
- **Returns & Refunds Management**: Complete return workflow with approval process
- **Advanced Reporting & Analytics**: Custom report builder with filters, grouping, aggregations
- **Multi-Channel Order Management**: Support for online, phone, in-store, marketplace channels
- **Custom Order Fields**: Admin-configurable custom fields with validation
- **Complete Audit Trail**: Track all order changes with before/after values
- **Notification System**: Template-based notifications via multiple channels
- **Order Tags & Organization**: Tag-based filtering and organization
- **Order Priority System**: Priority levels with SLA tracking
- **Order Templates**: Reusable order templates for quick order creation
- **Order Splitting & Merging**: Split orders into multiple or merge multiple orders
- **COGS Tracking**: Cost of Goods Sold tracking with profit margin calculation
- **Order Archiving**: Archive completed/old orders with auto-archiving rules
- **Advanced Search System**: Full-text search with saved searches
- **Communication Tracking**: Internal/external communication history
- **File Attachments**: Attach files to orders (invoices, packing slips, labels)
- **REST API**: Full REST API for all order operations
- **Webhook System**: Configurable webhook endpoints with event-based triggers
- **Hybrid Migration System**: Auto-detect and migrate existing commerce orders
- **Custom Permission System**: Role-based access control
- **Caching System**: Multi-level caching for performance
- **Background Job Processing**: Queue system for heavy operations
- **Print/PDF Generation**: Document templates for invoices, packing slips, labels
- **Comprehensive Dashboard**: Customizable widgets with real-time KPIs
- **Error Handling & Logging**: Comprehensive error and system logging

## Installation

1. Navigate to `/admin/components/order_management/install.php` in your browser
2. The installer will auto-detect your database configuration
3. Click "Install Order Management Component"
4. Configure settings in the admin interface

For CLI installation:
```bash
php admin/components/order_management/install.php --auto
```

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB equivalent)
- Commerce component (recommended, for order data)
- Payment Processing component (optional, for refunds)
- Inventory component (optional, for stock management)
- Email Marketing component (optional, for notifications)

## Features

### Workflow Management
- Define custom order status workflows
- Conditional status transitions
- Approval requirements at specific steps
- Integration with commerce order status

### Fulfillment Management
- Multi-warehouse fulfillment
- Picking list generation and optimization
- Packing interface with barcode scanning
- Shipping label integration
- Fulfillment status tracking per item

### Automation Engine
- Trigger-based automation (order created, status changed, payment received, etc.)
- Conditional actions (update status, send notification, allocate inventory, etc.)
- Priority-based rule execution
- Rule testing and simulation mode

### Returns & Refunds
- Return request workflow
- Return approval process
- Integration with payment_processing for refunds
- Inventory restocking on returns
- Return reason tracking and analytics

### Reporting & Analytics
- Custom report builder with filters, grouping, aggregations
- Saved report templates
- Export to CSV/Excel/PDF
- Real-time dashboards
- Order metrics and KPIs

## Integration Points

### Commerce Component
- Extends `commerce_orders` table
- Adds fulfillment, workflow, and metadata
- Syncs order status changes bidirectionally

### Payment Processing Component
- Links refunds to returns
- Syncs payment status to order status
- Creates refund transactions from return approvals

### Inventory Component
- Reserves stock on order creation
- Allocates stock to fulfillments
- Restocks on returns/cancellations
- Multi-location fulfillment support

### Email Marketing Component
- Sends order confirmation emails
- Status change notifications
- Return request confirmations
- Fulfillment notifications

## Database Tables

The component creates 57 database tables with the prefix `order_management_*`:

- Core tables: config, parameters, parameters_configs
- Workflow tables: workflows, workflow_steps, status_history, approvals
- Fulfillment tables: fulfillments, fulfillment_items, picking_lists, picking_items
- Automation tables: automation_rules, automation_logs
- Returns tables: returns, return_items, refunds
- And many more...

See `install/database.sql` for complete schema.

## API Functions

### Core Functions
- `order_management_get_db_connection()` - Get database connection
- `order_management_get_table_name($tableName)` - Get table name with prefix
- `order_management_is_installed()` - Check if component is installed
- `order_management_get_version()` - Get component version

### Helper Functions
- `order_management_get_parameter($name, $default)` - Get parameter value
- `order_management_set_parameter($name, $value, $section, $description)` - Set parameter
- `order_management_log_error($message, $context, $level)` - Log error

## Documentation

- [API Documentation](docs/API.md)
- [Integration Guide](docs/INTEGRATION.md)
- [Workflows Guide](docs/WORKFLOWS.md)
- [Automation Guide](docs/AUTOMATION.md)
- [Fulfillment Guide](docs/FULFILLMENT.md)

## Version

Current Version: 1.0.0

## License

Proprietary - All rights reserved

