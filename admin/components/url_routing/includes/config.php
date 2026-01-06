<?php
/**
 * URL Routing Component - Config Loader
 * Loads component configuration
 */

$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

