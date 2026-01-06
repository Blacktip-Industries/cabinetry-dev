<?php
/**
 * Commerce Component - Core Helper Functions
 * General utility functions
 */

require_once __DIR__ . '/database.php';

/**
 * Generate unique identifier
 * @param string $prefix Prefix for identifier
 * @return string Unique identifier
 */
function commerce_generate_uid($prefix = '') {
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    $timestamp = date('YmdHis');
    return $prefix . $timestamp . '-' . $random;
}

/**
 * Format currency
 * @param float $amount Amount
 * @param string $currency Currency code
 * @return string Formatted currency string
 */
function commerce_format_currency($amount, $currency = 'USD') {
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
function commerce_sanitize_input($input) {
    if (is_array($input)) {
        return array_map('commerce_sanitize_input', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 * @param string $email Email address
 * @return bool True if valid
 */
function commerce_validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get component version
 * @return string Version
 */
function commerce_get_component_version() {
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
function commerce_is_component_available($componentName) {
    $componentPath = __DIR__ . '/../../' . $componentName;
    return is_dir($componentPath) && file_exists($componentPath . '/config.php');
}

