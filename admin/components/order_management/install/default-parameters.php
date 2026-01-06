<?php
/**
 * Order Management Component - Default Parameters
 * Inserts default parameters during installation
 */

/**
 * Insert default parameters
 * @param mysqli $conn Database connection
 * @return array Result
 */
function order_management_insert_default_parameters($conn) {
    $defaultParams = [
        // General Settings
        ['section' => 'General', 'parameter_name' => 'default_workflow', 'value' => '', 'description' => 'Default order workflow ID'],
        ['section' => 'General', 'parameter_name' => 'auto_assign_workflow', 'value' => 'yes', 'description' => 'Automatically assign default workflow to new orders'],
        ['section' => 'General', 'parameter_name' => 'order_number_format', 'value' => 'ORD-{date}-{random}', 'description' => 'Order number format'],
        
        // Workflow Settings
        ['section' => 'Workflows', 'parameter_name' => 'enable_custom_workflows', 'value' => 'yes', 'description' => 'Enable custom order workflows'],
        ['section' => 'Workflows', 'parameter_name' => 'require_approval_for_status_change', 'value' => 'no', 'description' => 'Require approval for status changes'],
        
        // Fulfillment Settings
        ['section' => 'Fulfillment', 'parameter_name' => 'default_warehouse', 'value' => '', 'description' => 'Default warehouse ID for fulfillment'],
        ['section' => 'Fulfillment', 'parameter_name' => 'auto_create_picking_lists', 'value' => 'yes', 'description' => 'Automatically create picking lists'],
        ['section' => 'Fulfillment', 'parameter_name' => 'enable_barcode_scanning', 'value' => 'yes', 'description' => 'Enable barcode scanning for fulfillment'],
        
        // Automation Settings
        ['section' => 'Automation', 'parameter_name' => 'automation_enabled', 'value' => 'yes', 'description' => 'Enable automation rules'],
        ['section' => 'Automation', 'parameter_name' => 'automation_execution_mode', 'value' => 'immediate', 'description' => 'Automation execution mode (immediate, scheduled)'],
        
        // Notification Settings
        ['section' => 'Notifications', 'parameter_name' => 'send_order_confirmation', 'value' => 'yes', 'description' => 'Send order confirmation emails'],
        ['section' => 'Notifications', 'parameter_name' => 'send_status_updates', 'value' => 'yes', 'description' => 'Send order status update notifications'],
        ['section' => 'Notifications', 'parameter_name' => 'notification_channels', 'value' => 'email', 'description' => 'Notification channels (comma-separated)'],
        
        // Reporting Settings
        ['section' => 'Reporting', 'parameter_name' => 'report_cache_duration', 'value' => '3600', 'description' => 'Report cache duration in seconds'],
        ['section' => 'Reporting', 'parameter_name' => 'enable_report_caching', 'value' => 'yes', 'description' => 'Enable report caching'],
        
        // Priority Settings
        ['section' => 'Priority', 'parameter_name' => 'default_priority', 'value' => 'normal', 'description' => 'Default order priority'],
        ['section' => 'Priority', 'parameter_name' => 'enable_priority_auto_assignment', 'value' => 'no', 'description' => 'Enable automatic priority assignment'],
        
        // Archiving Settings
        ['section' => 'Archiving', 'parameter_name' => 'auto_archive_enabled', 'value' => 'no', 'description' => 'Enable automatic order archiving'],
        ['section' => 'Archiving', 'parameter_name' => 'archive_after_days', 'value' => '365', 'description' => 'Archive orders after X days'],
        
        // COGS Settings
        ['section' => 'COGS', 'parameter_name' => 'cogs_tracking_enabled', 'value' => 'yes', 'description' => 'Enable Cost of Goods Sold tracking'],
        ['section' => 'COGS', 'parameter_name' => 'cogs_costing_method', 'value' => 'average', 'description' => 'COGS costing method (fifo, lifo, average)'],
        
        // API Settings
        ['section' => 'API', 'parameter_name' => 'api_enabled', 'value' => 'yes', 'description' => 'Enable REST API'],
        ['section' => 'API', 'parameter_name' => 'api_rate_limit', 'value' => '1000', 'description' => 'API rate limit per hour'],
        
        // Webhook Settings
        ['section' => 'Webhooks', 'parameter_name' => 'webhooks_enabled', 'value' => 'yes', 'description' => 'Enable webhooks'],
        ['section' => 'Webhooks', 'parameter_name' => 'webhook_retry_attempts', 'value' => '3', 'description' => 'Webhook retry attempts'],
        
        // Cache Settings
        ['section' => 'Cache', 'parameter_name' => 'cache_enabled', 'value' => 'yes', 'description' => 'Enable caching'],
        ['section' => 'Cache', 'parameter_name' => 'cache_duration', 'value' => '3600', 'description' => 'Default cache duration in seconds'],
        
        // Queue Settings
        ['section' => 'Queue', 'parameter_name' => 'queue_enabled', 'value' => 'yes', 'description' => 'Enable background job queue'],
        ['section' => 'Queue', 'parameter_name' => 'queue_max_attempts', 'value' => '3', 'description' => 'Maximum queue job attempts'],
        
        // Integration Settings
        ['section' => 'Integration', 'parameter_name' => 'commerce_integration', 'value' => 'yes', 'description' => 'Enable commerce component integration'],
        ['section' => 'Integration', 'parameter_name' => 'payment_processing_integration', 'value' => 'yes', 'description' => 'Enable payment_processing component integration'],
        ['section' => 'Integration', 'parameter_name' => 'inventory_integration', 'value' => 'yes', 'description' => 'Enable inventory component integration'],
        ['section' => 'Integration', 'parameter_name' => 'email_marketing_integration', 'value' => 'yes', 'description' => 'Enable email_marketing component integration'],
        
        // CSS Variables
        ['section' => 'CSS', 'parameter_name' => '--order-management-color-primary', 'value' => 'var(--color-primary, #007bff)', 'description' => 'Primary color'],
        ['section' => 'CSS', 'parameter_name' => '--order-management-color-success', 'value' => 'var(--color-success, #28a745)', 'description' => 'Success color'],
        ['section' => 'CSS', 'parameter_name' => '--order-management-color-danger', 'value' => 'var(--color-danger, #dc3545)', 'description' => 'Danger color'],
        ['section' => 'CSS', 'parameter_name' => '--order-management-spacing-md', 'value' => 'var(--spacing-md, 16px)', 'description' => 'Medium spacing'],
    ];
    
    $inserted = 0;
    $errors = [];
    
    foreach ($defaultParams as $param) {
        try {
            $tableName = 'order_management_parameters';
            $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, value, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description)");
            $stmt->bind_param("ssss", $param['section'], $param['parameter_name'], $param['value'], $param['description']);
            $stmt->execute();
            $stmt->close();
            $inserted++;
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error inserting parameter {$param['parameter_name']}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

