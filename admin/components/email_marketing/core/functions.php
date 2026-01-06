<?php
/**
 * Email Marketing Component - Helper Functions
 */

require_once __DIR__ . '/database.php';

/**
 * Format currency
 * @param float $amount Amount
 * @return string Formatted currency
 */
function email_marketing_format_currency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function email_marketing_format_date($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Calculate days until expiry
 * @param string $expiryDate Expiry date
 * @return int|null Days until expiry or null if never expires
 */
function email_marketing_days_until_expiry($expiryDate) {
    if (empty($expiryDate)) {
        return null;
    }
    
    $expiry = strtotime($expiryDate);
    $now = time();
    $diff = $expiry - $now;
    
    return $diff > 0 ? floor($diff / (60 * 60 * 24)) : 0;
}

/**
 * Get campaign statistics
 * @param int $campaignId Campaign ID
 * @return array Statistics
 */
function email_marketing_get_campaign_stats($campaignId) {
    $campaign = email_marketing_get_campaign($campaignId);
    if (!$campaign) {
        return null;
    }
    
    $sent = $campaign['sent_count'] ?? 0;
    $opened = $campaign['opened_count'] ?? 0;
    $clicked = $campaign['clicked_count'] ?? 0;
    $bounced = $campaign['bounced_count'] ?? 0;
    
    return [
        'sent' => $sent,
        'opened' => $opened,
        'clicked' => $clicked,
        'bounced' => $bounced,
        'open_rate' => $sent > 0 ? ($opened / $sent) * 100 : 0,
        'click_rate' => $sent > 0 ? ($clicked / $sent) * 100 : 0,
        'bounce_rate' => $sent > 0 ? ($bounced / $sent) * 100 : 0
    ];
}

