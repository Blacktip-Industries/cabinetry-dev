<?php
/**
 * SEO Manager Component - Config Loader
 * Loads component configuration
 */

$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Component not installed
    if (!defined('SEO_MANAGER_NOT_INSTALLED')) {
        define('SEO_MANAGER_NOT_INSTALLED', true);
    }
}

