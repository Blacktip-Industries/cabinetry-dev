<?php
/**
 * Formula Builder Component - Config Loader
 * Loads component configuration
 */

// Load config if it exists
$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

// Load core database functions
if (file_exists(__DIR__ . '/../core/database.php')) {
    require_once __DIR__ . '/../core/database.php';
}

