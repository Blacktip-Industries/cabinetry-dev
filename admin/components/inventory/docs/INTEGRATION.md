# Inventory Component - Integration Guide

## Overview

The Inventory Component is designed to integrate seamlessly with other components in the system, particularly the Commerce component. This guide explains how to set up and use these integrations.

## Commerce Component Integration

### Prerequisites

1. Commerce component must be installed
2. Inventory component must be installed
3. Integration must be enabled in Inventory Settings

### Enabling Integration

1. Navigate to **Inventory > Settings > Integration**
2. Check "Enable Commerce Integration"
3. Save settings

### Features

#### Product-Item Linking

Link inventory items to commerce products for automatic stock synchronization.

**Example:**
```php
// Link an inventory item to a commerce product
$result = inventory_sync_product_with_commerce($itemId, $productId);
```

#### Automatic Stock Updates

When enabled, inventory stock levels are automatically updated when:
- Orders are placed in Commerce
- Orders are fulfilled
- Orders are cancelled

#### Stock Level Synchronization

Stock levels can be synchronized bidirectionally:
- Inventory → Commerce: Update product stock from inventory
- Commerce → Inventory: Update inventory from product sales

### API Functions

#### `inventory_is_commerce_available()`
Check if Commerce component is installed and available.

**Returns:** Boolean

#### `inventory_is_commerce_integration_enabled()`
Check if Commerce integration is enabled.

**Returns:** Boolean

#### `inventory_sync_product_with_commerce($itemId, $productId)`
Link an inventory item to a commerce product.

**Parameters:**
- `$itemId` (int): Inventory item ID
- `$productId` (int): Commerce product ID

**Returns:** Array with `success` (bool) or `error` (string)

#### `inventory_update_stock_from_commerce($productId, $quantityChange)`
Update inventory stock from commerce order.

**Parameters:**
- `$productId` (int): Commerce product ID
- `$quantityChange` (int): Quantity change (negative for sales)

**Returns:** Array with `success` (bool) or `error` (string)

### Integration Workflow

1. **Setup Phase:**
   - Install both components
   - Enable integration in Inventory Settings
   - Link products to items (one-time setup)

2. **Operation Phase:**
   - Stock levels sync automatically
   - Orders update inventory in real-time
   - Low stock alerts can trigger product status changes

3. **Maintenance Phase:**
   - Regular stock reconciliation
   - Monitor sync status
   - Handle discrepancies

## Menu System Integration

The Inventory component automatically creates menu links in the admin menu system if the `menu_system` component is installed.

### Menu Links Created

- Inventory Dashboard
- Items
- Locations
- Movements
- Transfers
- Adjustments
- Barcodes
- Reports
- Alerts
- Settings

### Customizing Menu Links

Menu links are created during installation. To modify:
1. Edit `install/default-menu-links.php`
2. Re-run installer or manually update menu system

## Access Control Integration

If the `access` component is installed, inventory functions respect access control permissions.

### Required Permissions

- `inventory_view`: View inventory data
- `inventory_manage`: Manage items and locations
- `inventory_transfer`: Create and process transfers
- `inventory_adjust`: Create adjustments
- `inventory_admin`: Full administrative access

## Email Integration

Alerts can send emails using the system's email functionality.

### Alert Email Configuration

1. Set default alert recipients in Settings
2. Configure per-alert email addresses
3. Alerts automatically send when triggered

## Custom Integrations

### Adding Custom Integrations

To integrate with other components:

1. **Check Component Availability:**
```php
if (inventory_is_component_available('your_component')) {
    // Integration code
}
```

2. **Add Integration Functions:**
Create functions in `core/integrations.php`:
```php
function inventory_integrate_with_your_component($data) {
    // Integration logic
}
```

3. **Update Settings Page:**
Add integration options to `admin/settings/integration.php`

## Webhook Support

The component can trigger webhooks for external integrations:

### Available Webhooks

- `inventory.stock.updated`: When stock levels change
- `inventory.transfer.completed`: When transfer is completed
- `inventory.adjustment.approved`: When adjustment is approved
- `inventory.alert.triggered`: When an alert is triggered

### Webhook Configuration

Configure webhooks in component parameters:
```php
inventory_set_parameter('webhook_url', 'https://example.com/webhook');
inventory_set_parameter('webhook_secret', 'your-secret-key');
```

## API Integration

External systems can integrate via the API functions. See `API.md` for complete API documentation.

### Authentication

API calls should use the system's authentication mechanism. For external access, consider:
- API keys
- OAuth tokens
- Session-based authentication

## Best Practices

1. **Enable Integration Gradually:**
   - Start with read-only operations
   - Test thoroughly before enabling writes
   - Monitor for errors

2. **Handle Errors Gracefully:**
   - Log integration errors
   - Provide fallback mechanisms
   - Notify administrators of failures

3. **Regular Reconciliation:**
   - Compare stock levels between systems
   - Resolve discrepancies promptly
   - Document reconciliation process

4. **Performance Considerations:**
   - Batch operations when possible
   - Use background jobs for heavy operations
   - Cache frequently accessed data

## Troubleshooting

### Integration Not Working

1. Check component installation status
2. Verify integration is enabled in settings
3. Check component logs for errors
4. Verify database connections

### Stock Sync Issues

1. Check sync settings
2. Verify product-item links
3. Review movement history
4. Check for conflicting updates

### Alert Email Issues

1. Verify email configuration
2. Check alert recipient addresses
3. Review email logs
4. Test email functionality

## Support

For integration issues:
1. Check component logs
2. Review API documentation
3. Test with minimal configuration
4. Contact component support

