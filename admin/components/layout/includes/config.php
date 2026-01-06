<?php
/**
 * Layout Component - Config Loader
 * Loads component configuration
 */

// Load config if it exists
$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Use defaults if config doesn't exist (for installation)
    if (!defined('LAYOUT_TABLE_PREFIX')) {
        define('LAYOUT_TABLE_PREFIX', 'layout_');
    }
    if (!defined('LAYOUT_COMPONENT_PATH')) {
        define('LAYOUT_COMPONENT_PATH', __DIR__ . '/..');
    }
}

