<?php
/**
 * Payment Processing Component - Core Helper Functions
 * General utility functions
 */

require_once __DIR__ . '/database.php';

/**
 * Generate unique transaction ID
 * @return string Transaction ID
 */
function payment_processing_generate_transaction_id() {
    return 'TXN-' . time() . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Generate unique refund ID
 * @return string Refund ID
 */
function payment_processing_generate_refund_id() {
    return 'REF-' . time() . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Generate unique subscription ID
 * @return string Subscription ID
 */
function payment_processing_generate_subscription_id() {
    return 'SUB-' . time() . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Format currency amount
 * @param float $amount Amount
 * @param string $currency Currency code
 * @return string Formatted amount
 */
function payment_processing_format_currency($amount, $currency = 'USD') {
    $formatters = [
        'USD' => '$%.2f',
        'EUR' => '€%.2f',
        'GBP' => '£%.2f',
        'AUD' => 'A$%.2f',
        'CAD' => 'C$%.2f',
        'JPY' => '¥%.0f'
    ];
    
    $format = $formatters[$currency] ?? $currency . ' %.2f';
    return sprintf($format, $amount);
}

/**
 * Convert currency
 * @param float $amount Amount
 * @param string $fromCurrency Source currency
 * @param string $toCurrency Target currency
 * @return float Converted amount
 */
function payment_processing_convert_currency($amount, $fromCurrency, $toCurrency) {
    if ($fromCurrency === $toCurrency) {
        return $amount;
    }
    
    // Get exchange rates (would typically come from an API or database)
    $exchangeRates = payment_processing_get_exchange_rates();
    
    $rate = $exchangeRates[$fromCurrency][$toCurrency] ?? 1.0;
    
    return $amount * $rate;
}

/**
 * Get exchange rates
 * @return array Exchange rates
 */
function payment_processing_get_exchange_rates() {
    // This would typically fetch from an API or database
    // For now, return a simple structure
    return [
        'USD' => [
            'EUR' => 0.85,
            'GBP' => 0.73,
            'AUD' => 1.35,
            'CAD' => 1.25
        ],
        'EUR' => [
            'USD' => 1.18,
            'GBP' => 0.86,
            'AUD' => 1.59
        ]
        // Add more as needed
    ];
}

/**
 * Validate currency code
 * @param string $currency Currency code
 * @return bool True if valid
 */
function payment_processing_validate_currency($currency) {
    $validCurrencies = [
        'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'JPY', 'CHF', 'NZD', 'SGD', 'HKD',
        'CNY', 'INR', 'BRL', 'MXN', 'ZAR', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK'
    ];
    
    return in_array(strtoupper($currency), $validCurrencies);
}

/**
 * Validate payment method
 * @param string $method Payment method
 * @return bool True if valid
 */
function payment_processing_validate_payment_method($method) {
    $validMethods = [
        'card', 'bank_transfer', 'ach_direct_debit', 'paypal', 'apple_pay', 'google_pay'
    ];
    
    return in_array(strtolower($method), $validMethods);
}

/**
 * Get transaction status display name
 * @param string $status Status code
 * @return string Display name
 */
function payment_processing_get_status_display($status) {
    $statuses = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired'
    ];
    
    return $statuses[$status] ?? ucfirst($status);
}

/**
 * Get transaction status color class
 * @param string $status Status code
 * @return string CSS class
 */
function payment_processing_get_status_color($status) {
    $colors = [
        'pending' => 'warning',
        'processing' => 'info',
        'completed' => 'success',
        'failed' => 'danger',
        'refunded' => 'secondary',
        'cancelled' => 'dark',
        'expired' => 'secondary'
    ];
    
    return $colors[$status] ?? 'secondary';
}

/**
 * Check if component is installed
 * @return bool True if installed
 */
function payment_processing_is_installed() {
    return file_exists(__DIR__ . '/../config.php');
}

/**
 * Get component version
 * @return string Version number
 */
function payment_processing_get_version() {
    $versionFile = __DIR__ . '/../VERSION';
    if (file_exists($versionFile)) {
        return trim(file_get_contents($versionFile));
    }
    return '1.0.0';
}

