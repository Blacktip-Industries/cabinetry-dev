<?php
/**
 * Commerce Component - Integration Functions
 * Functions for integrating with other components
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if product_options component is available
 * @return bool True if available
 */
function commerce_is_product_options_available() {
    return commerce_is_component_available('product_options') && function_exists('product_options_get_option');
}

/**
 * Check if payment_processing component is available
 * @return bool True if available
 */
function commerce_is_payment_processing_available() {
    return commerce_is_component_available('payment_processing') && function_exists('payment_processing_process_payment');
}

/**
 * Check if access component is available
 * @return bool True if available
 */
function commerce_is_access_available() {
    return commerce_is_component_available('access') && function_exists('access_get_account');
}

/**
 * Check if email_marketing component is available
 * @return bool True if available
 */
function commerce_is_email_marketing_available() {
    return commerce_is_component_available('email_marketing') && function_exists('email_marketing_send_email');
}

/**
 * Send order confirmation email
 * @param int $orderId Order ID
 * @return array Result
 */
function commerce_send_order_confirmation_email($orderId) {
    if (!commerce_is_email_marketing_available()) {
        return ['success' => false, 'error' => 'email_marketing component not available'];
    }
    
    require_once __DIR__ . '/orders.php';
    $order = commerce_get_order($orderId);
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found'];
    }
    
    // TODO: Implement email sending using email_marketing component
    return ['success' => true];
}

/**
 * Send low stock alert email
 * @param array $lowStockItem Low stock item data
 * @return array Result
 */
function commerce_send_low_stock_alert($lowStockItem) {
    if (!commerce_is_email_marketing_available()) {
        return ['success' => false, 'error' => 'email_marketing component not available'];
    }
    
    $alertEmail = $lowStockItem['alert_email'] ?? commerce_get_parameter('admin_email', '');
    if (empty($alertEmail)) {
        return ['success' => false, 'error' => 'No alert email configured'];
    }
    
    // TODO: Implement email sending
    return ['success' => true];
}

