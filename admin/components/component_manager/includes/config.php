<?php
/**
 * Component Manager - Config Loader
 * Loads component configuration
 */

// Load config if it exists
$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

// Load core functions
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

