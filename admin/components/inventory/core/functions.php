<?php
/**
 * Inventory Component - Core Helper Functions
 * General utility functions and component detection
 */

require_once __DIR__ . '/database.php';

/**
 * Generate unique identifier
 * @param string $prefix Prefix for identifier
 * @return string Unique identifier
 */
function inventory_generate_uid($prefix = '') {
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    $timestamp = date('YmdHis');
    return $prefix . $timestamp . '-' . $random;
}

/**
 * Generate transfer number
 * @return string Transfer number
 */
function inventory_generate_transfer_number() {
    return inventory_generate_uid('TRF-');
}

/**
 * Generate adjustment number
 * @return string Adjustment number
 */
function inventory_generate_adjustment_number() {
    return inventory_generate_uid('ADJ-');
}

/**
 * Format currency
 * @param float $amount Amount
 * @param string $currency Currency code
 * @return string Formatted currency string
 */
function inventory_format_currency($amount, $currency = 'USD') {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'AUD' => 'A$',
        'CAD' => 'C$'
    ];
    
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}

/**
 * Sanitize input
 * @param mixed $input Input to sanitize
 * @return mixed Sanitized input
 */
function inventory_sanitize_input($input) {
    if (is_array($input)) {
        return array_map('inventory_sanitize_input', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 * @param string $email Email address
 * @return bool True if valid
 */
function inventory_validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get component version
 * @return string Version
 */
function inventory_get_component_version() {
    $versionFile = __DIR__ . '/../VERSION';
    if (file_exists($versionFile)) {
        return trim(file_get_contents($versionFile));
    }
    return '1.0.0';
}

/**
 * Check if component is available
 * @param string $componentName Component name
 * @return bool True if available
 */
function inventory_is_component_available($componentName) {
    $componentPath = __DIR__ . '/../../' . $componentName;
    return is_dir($componentPath) && file_exists($componentPath . '/config.php');
}

/**
 * Check if commerce component is available
 * @return bool True if available
 */
function inventory_is_commerce_available() {
    return inventory_is_component_available('commerce') && function_exists('commerce_get_db_connection');
}

/**
 * Check if email_marketing component is available
 * @return bool True if available
 */
function inventory_is_email_marketing_available() {
    return inventory_is_component_available('email_marketing') && function_exists('email_marketing_send_email');
}

/**
 * Check if access component is available
 * @return bool True if available
 */
function inventory_is_access_available() {
    return inventory_is_component_available('access') && function_exists('access_get_account');
}

/**
 * Get current user ID (from access component or session)
 * @return int|null User ID or null
 */
function inventory_get_current_user_id() {
    // Try access component first
    if (inventory_is_access_available() && function_exists('access_get_current_user_id')) {
        return access_get_current_user_id();
    }
    
    // Fallback to session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Format date/time
 * @param string|DateTime $date Date to format
 * @param string $format Date format
 * @return string Formatted date
 */
function inventory_format_date($date, $format = 'Y-m-d H:i:s') {
    if ($date instanceof DateTime) {
        return $date->format($format);
    }
    if (is_string($date)) {
        $dt = new DateTime($date);
        return $dt->format($format);
    }
    return '';
}

/**
 * Calculate days between dates
 * @param string|DateTime $date1 First date
 * @param string|DateTime $date2 Second date
 * @return int Days difference
 */
function inventory_days_between($date1, $date2) {
    $d1 = $date1 instanceof DateTime ? $date1 : new DateTime($date1);
    $d2 = $date2 instanceof DateTime ? $date2 : new DateTime($date2);
    $diff = $d1->diff($d2);
    return $diff->days;
}

/**
 * Validate barcode format
 * @param string $barcode Barcode value
 * @param string $type Barcode type
 * @return bool True if valid
 */
function inventory_validate_barcode($barcode, $type = 'CODE128') {
    switch ($type) {
        case 'EAN13':
            return preg_match('/^\d{13}$/', $barcode);
        case 'UPC':
            return preg_match('/^\d{12}$/', $barcode);
        case 'CODE128':
            return strlen($barcode) > 0 && strlen($barcode) <= 255;
        case 'QR':
            return strlen($barcode) > 0;
        default:
            return strlen($barcode) > 0;
    }
}

/**
 * Get costing method
 * @return string Costing method (FIFO, LIFO, Average)
 */
function inventory_get_costing_method() {
    return inventory_get_parameter('default_costing_method', 'Average');
}

/**
 * Check if adjustment approval is required
 * @return bool True if approval required
 */
function inventory_requires_adjustment_approval() {
    return inventory_get_parameter('require_adjustment_approval', 'yes') === 'yes';
}

/**
 * Check if transfer approval is required
 * @return bool True if approval required
 */
function inventory_requires_transfer_approval() {
    return inventory_get_parameter('require_transfer_approval', 'yes') === 'yes';
}

/**
 * Check if commerce integration is enabled
 * @return bool True if enabled
 */
function inventory_is_commerce_integration_enabled() {
    return inventory_get_parameter('enable_commerce_integration', 'yes') === 'yes';
}

