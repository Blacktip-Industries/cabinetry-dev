<?php
/**
 * Theme Component - Configuration Loader
 * Loads component configuration and provides helper functions
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

// Load core functions
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

/**
 * Check if theme component is properly configured
 * @return bool True if configured
 */
function theme_is_configured() {
    return defined('THEME_DB_HOST') && !empty(THEME_DB_HOST);
}

/**
 * Get theme component base URL
 * @return string Base URL
 */
function theme_get_base_url() {
    return defined('THEME_BASE_URL') ? THEME_BASE_URL : '';
}

/**
 * Get theme component admin URL
 * @return string Admin URL
 */
function theme_get_admin_url() {
    return defined('THEME_ADMIN_URL') ? THEME_ADMIN_URL : '';
}

