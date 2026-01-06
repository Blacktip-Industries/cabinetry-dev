<?php
/**
 * Commerce Component - Default Parameters
 * Inserts default parameters during installation
 */

/**
 * Insert default parameters
 * @param mysqli $conn Database connection
 * @return array Result
 */
function commerce_insert_default_parameters($conn) {
    $defaultParams = [
        // General Settings
        ['section' => 'General', 'parameter_name' => 'default_currency', 'value' => 'USD', 'description' => 'Default currency code'],
        ['section' => 'General', 'parameter_name' => 'order_number_prefix', 'value' => 'ORD', 'description' => 'Order number prefix'],
        ['section' => 'General', 'parameter_name' => 'tax_enabled', 'value' => 'no', 'description' => 'Enable tax calculation'],
        ['section' => 'General', 'parameter_name' => 'default_tax_rate', 'value' => '10', 'description' => 'Default tax rate (%)'],
        
        // Shipping Settings
        ['section' => 'Shipping', 'parameter_name' => 'default_shipping_zone', 'value' => '', 'description' => 'Default shipping zone ID'],
        ['section' => 'Shipping', 'parameter_name' => 'shipping_calculation_enabled', 'value' => 'yes', 'description' => 'Enable shipping calculation'],
        ['section' => 'Shipping', 'parameter_name' => 'free_shipping_threshold', 'value' => '0', 'description' => 'Free shipping threshold amount'],
        
        // Inventory Settings
        ['section' => 'Inventory', 'parameter_name' => 'default_warehouse', 'value' => '', 'description' => 'Default warehouse ID'],
        ['section' => 'Inventory', 'parameter_name' => 'low_stock_threshold', 'value' => '10', 'description' => 'Default low stock threshold'],
        ['section' => 'Inventory', 'parameter_name' => 'auto_reserve_inventory', 'value' => 'yes', 'description' => 'Automatically reserve inventory on order'],
        
        // Cart Settings
        ['section' => 'Cart', 'parameter_name' => 'cart_expiry_days', 'value' => '30', 'description' => 'Cart expiry in days'],
        ['section' => 'Cart', 'parameter_name' => 'allow_guest_checkout', 'value' => 'yes', 'description' => 'Allow guest checkout'],
        
        // Bulk Orders
        ['section' => 'Bulk Orders', 'parameter_name' => 'bulk_orders_enabled', 'value' => 'yes', 'description' => 'Enable bulk order tables'],
        
        // Integration Settings
        ['section' => 'Integration', 'parameter_name' => 'product_options_integration', 'value' => 'yes', 'description' => 'Enable product_options component integration'],
        ['section' => 'Integration', 'parameter_name' => 'payment_processing_integration', 'value' => 'yes', 'description' => 'Enable payment_processing component integration'],
        ['section' => 'Integration', 'parameter_name' => 'email_marketing_integration', 'value' => 'yes', 'description' => 'Enable email_marketing component integration'],
        
        // CSS Variables
        ['section' => 'CSS', 'parameter_name' => '--commerce-button-primary-bg', 'value' => 'var(--color-primary, #007bff)', 'description' => 'Primary button background color'],
        ['section' => 'CSS', 'parameter_name' => '--commerce-button-primary-text', 'value' => 'var(--color-white, #ffffff)', 'description' => 'Primary button text color'],
        ['section' => 'CSS', 'parameter_name' => '--commerce-card-bg', 'value' => 'var(--bg-card, #ffffff)', 'description' => 'Card background color'],
        ['section' => 'CSS', 'parameter_name' => '--commerce-border-radius', 'value' => 'var(--border-radius-md, 8px)', 'description' => 'Border radius'],
    ];
    
    $inserted = 0;
    $errors = [];
    
    foreach ($defaultParams as $param) {
        try {
            $tableName = 'commerce_parameters';
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

