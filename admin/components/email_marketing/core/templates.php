<?php
/**
 * Email Marketing Component - Template Management Functions
 */

require_once __DIR__ . '/database.php';

// Template functions are already in database.php
// This file can be extended with additional template-specific functions

/**
 * Get default template variables
 * @return array Default variables
 */
function email_marketing_get_default_template_variables() {
    return [
        'name' => 'Customer Name',
        'company' => 'Company Name',
        'email' => 'customer@example.com',
        'coupon_code' => 'COUPON123',
        'points_balance' => '0',
        'tier_name' => 'Bronze',
        'expiry_date' => date('Y-m-d', strtotime('+30 days'))
    ];
}

