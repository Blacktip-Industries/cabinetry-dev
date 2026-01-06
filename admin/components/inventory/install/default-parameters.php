<?php
/**
 * Inventory Component - Default Parameters
 * Default component parameters
 */

/**
 * Get default parameters
 * @return array Array of default parameters
 */
function inventory_get_default_parameters() {
    return [
        // General Settings
        ['section' => 'General', 'parameter_name' => 'default_costing_method', 'value' => 'Average', 'description' => 'Default costing method (FIFO, LIFO, Average)'],
        ['section' => 'General', 'parameter_name' => 'default_location_id', 'value' => '', 'description' => 'Default location ID'],
        ['section' => 'General', 'parameter_name' => 'unit_of_measure_default', 'value' => 'unit', 'description' => 'Default unit of measure'],
        
        // Approval Settings
        ['section' => 'Approvals', 'parameter_name' => 'require_adjustment_approval', 'value' => 'yes', 'description' => 'Require approval for stock adjustments'],
        ['section' => 'Approvals', 'parameter_name' => 'require_transfer_approval', 'value' => 'yes', 'description' => 'Require approval for stock transfers'],
        
        // Integration Settings
        ['section' => 'Integration', 'parameter_name' => 'enable_commerce_integration', 'value' => 'yes', 'description' => 'Enable commerce component integration'],
        
        // Alert Settings
        ['section' => 'Alerts', 'parameter_name' => 'low_stock_threshold', 'value' => '10', 'description' => 'Default low stock threshold'],
        ['section' => 'Alerts', 'parameter_name' => 'alert_email_recipients', 'value' => '', 'description' => 'Comma-separated list of alert email recipients'],
        
        // Barcode Settings
        ['section' => 'Barcodes', 'parameter_name' => 'barcode_type_default', 'value' => 'CODE128', 'description' => 'Default barcode type'],
        
        // Expiry Settings
        ['section' => 'Expiry', 'parameter_name' => 'enable_expiry_tracking', 'value' => 'no', 'description' => 'Enable expiry date tracking'],
        
        // CSS Variables (for styling)
        ['section' => 'CSS', 'parameter_name' => '--inventory-card-padding', 'value' => 'var(--spacing-md, 16px)', 'description' => 'Card padding'],
        ['section' => 'CSS', 'parameter_name' => '--inventory-table-border', 'value' => 'var(--border-color, #ddd)', 'description' => 'Table border color'],
    ];
}

