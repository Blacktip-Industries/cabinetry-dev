<?php
/**
 * Payment Processing Component - Config Loader
 * Loads component configuration from config.php
 */

// Load config if it exists
$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

// Define component path if not defined
if (!defined('PAYMENT_PROCESSING_PATH')) {
    define('PAYMENT_PROCESSING_PATH', __DIR__ . '/..');
}

// Define base URL if not defined
if (!defined('PAYMENT_PROCESSING_BASE_URL')) {
    // Try to auto-detect base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = str_replace('/admin/components/payment_processing/includes', '', $scriptPath);
    define('PAYMENT_PROCESSING_BASE_URL', $protocol . '://' . $host . $basePath);
}

