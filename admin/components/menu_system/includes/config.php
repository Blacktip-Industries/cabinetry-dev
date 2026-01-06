<?php
/**
 * Menu System Component - Config Loader
 * Loads component configuration
 */

// Load config if it exists
$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Use defaults if config doesn't exist (for installation)
    if (!defined('MENU_SYSTEM_TABLE_PREFIX')) {
        define('MENU_SYSTEM_TABLE_PREFIX', 'menu_system_');
    }
    if (!defined('MENU_SYSTEM_COMPONENT_PATH')) {
        define('MENU_SYSTEM_COMPONENT_PATH', __DIR__ . '/..');
    }
}

