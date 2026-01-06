<?php
/**
 * Mobile API Component - Configuration Loader
 * Loads component configuration and sets up environment
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('MOBILE_API_COMPONENT')) {
    die('Direct access not allowed');
}

// Define component constant
define('MOBILE_API_COMPONENT', true);

// Component directory
define('MOBILE_API_DIR', __DIR__ . '/..');
define('MOBILE_API_CORE_DIR', MOBILE_API_DIR . '/core');
define('MOBILE_API_ADMIN_DIR', MOBILE_API_DIR . '/admin');
define('MOBILE_API_ASSETS_DIR', MOBILE_API_DIR . '/assets');

// Load config file if it exists
$config_file = MOBILE_API_DIR . '/config.php';
if (file_exists($config_file)) {
    require_once $config_file;
} else {
    // Use defaults if config doesn't exist (during installation)
    if (!defined('MOBILE_API_VERSION')) {
        define('MOBILE_API_VERSION', '1.0.0');
    }
}

// Load core files
require_once MOBILE_API_CORE_DIR . '/database.php';
require_once MOBILE_API_CORE_DIR . '/functions.php';

