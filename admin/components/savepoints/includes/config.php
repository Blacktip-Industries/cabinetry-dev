<?php
/**
 * Savepoints Component - Config Loader
 * Loads component configuration
 */

// Load config if it exists
$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Use defaults if config doesn't exist (for installation)
    if (!defined('SAVEPOINTS_TABLE_PREFIX')) {
        define('SAVEPOINTS_TABLE_PREFIX', 'savepoints_');
    }
    if (!defined('SAVEPOINTS_COMPONENT_PATH')) {
        define('SAVEPOINTS_COMPONENT_PATH', __DIR__ . '/..');
    }
}

