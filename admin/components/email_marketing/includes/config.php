<?php
/**
 * Email Marketing Component - Config Loader
 * Loads component configuration
 */

// Load component config if it exists
$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile)) {
    require_once $configFile;
} else {
    // Load example config as fallback
    $exampleConfig = __DIR__ . '/../config.example.php';
    if (file_exists($exampleConfig)) {
        require_once $exampleConfig;
    }
}

/**
 * Check if component is installed
 * @return bool
 */
function email_marketing_is_installed() {
    return defined('EMAIL_MARKETING_VERSION') && !empty(EMAIL_MARKETING_VERSION);
}

/**
 * Get component version
 * @return string
 */
function email_marketing_get_version() {
    return defined('EMAIL_MARKETING_VERSION') ? EMAIL_MARKETING_VERSION : '0.0.0';
}

