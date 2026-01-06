<?php
/**
 * Error Monitoring Component - Config Loader
 * Loads component configuration
 */

// Load config file if it exists
$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile)) {
    require_once $configFile;
} else {
    // Use example config as fallback
    $exampleConfigFile = __DIR__ . '/../config.example.php';
    if (file_exists($exampleConfigFile)) {
        require_once $exampleConfigFile;
    }
}

