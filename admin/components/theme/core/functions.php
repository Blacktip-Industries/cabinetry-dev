<?php
/**
 * Theme Component - Core Helper Functions
 * All functions prefixed with theme_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get component version
 * @return string Version number
 */
function theme_get_version() {
    return defined('THEME_VERSION') ? THEME_VERSION : '1.0.0';
}

/**
 * Check if theme component is installed
 * @return bool True if installed
 */
function theme_is_installed() {
    return file_exists(__DIR__ . '/../config.php');
}

/**
 * Get theme component path
 * @return string Component path
 */
function theme_get_component_path() {
    return __DIR__ . '/..';
}

/**
 * Get theme assets URL
 * @return string Assets URL
 */
function theme_get_assets_url() {
    if (defined('THEME_ADMIN_URL')) {
        return THEME_ADMIN_URL . '/components/theme/assets';
    }
    return '/admin/components/theme/assets';
}

/**
 * Get theme CSS URL
 * @return string CSS URL
 */
function theme_get_css_url() {
    return theme_get_assets_url() . '/css';
}

/**
 * Get theme JS URL
 * @return string JavaScript URL
 */
function theme_get_js_url() {
    return theme_get_assets_url() . '/js';
}

