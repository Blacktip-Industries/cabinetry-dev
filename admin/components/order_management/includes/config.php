<?php
/**
 * Order Management Component - Config Loader
 * Loads component configuration
 */

// Load config if it exists
$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Use defaults or try to detect from base system
    if (!defined('ORDER_MANAGEMENT_DB_HOST')) {
        // Try to load from base system config
        $baseConfigs = [
            __DIR__ . '/../../../config/database.php',
            __DIR__ . '/../../includes/config.php',
            __DIR__ . '/../../../config.php'
        ];
        
        foreach ($baseConfigs as $baseConfig) {
            if (file_exists($baseConfig)) {
                require_once $baseConfig;
                break;
            }
        }
    }
}

// Set defaults if not defined
if (!defined('ORDER_MANAGEMENT_TABLE_PREFIX')) {
    define('ORDER_MANAGEMENT_TABLE_PREFIX', 'order_management_');
}

if (!defined('ORDER_MANAGEMENT_PATH')) {
    define('ORDER_MANAGEMENT_PATH', __DIR__ . '/..');
}

