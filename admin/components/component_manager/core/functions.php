<?php
/**
 * Component Manager - Helper Functions
 * Core utility functions with component_manager_ prefix
 */

require_once __DIR__ . '/database.php';

/**
 * Get base URL dynamically
 * @return string Base URL
 */
function component_manager_get_base_url() {
    if (defined('COMPONENT_MANAGER_BASE_URL') && !empty(COMPONENT_MANAGER_BASE_URL)) {
        return COMPONENT_MANAGER_BASE_URL;
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir = dirname($script);
    
    // If we're in the admin directory, remove /admin and everything after it
    if (strpos($dir, '/admin') !== false) {
        $base = preg_replace('/\/admin.*$/', '', $dir);
    } else {
        $base = $dir;
    }
    
    return $protocol . '://' . $host . $base;
}

/**
 * Get admin URL
 * @param string $path Path to append
 * @return string Admin URL
 */
function component_manager_get_admin_url($path = '') {
    if (defined('COMPONENT_MANAGER_ADMIN_URL') && !empty(COMPONENT_MANAGER_ADMIN_URL)) {
        return COMPONENT_MANAGER_ADMIN_URL . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    $base = component_manager_get_base_url();
    $adminPath = '/admin';
    if ($path && $path[0] !== '/') {
        $adminPath .= '/';
    }
    return $base . $adminPath . $path;
}

/**
 * Get component path
 * @param string $path Path to append
 * @return string Component path
 */
function component_manager_get_component_path($path = '') {
    $componentPath = defined('COMPONENT_MANAGER_COMPONENT_PATH') ? COMPONENT_MANAGER_COMPONENT_PATH : __DIR__ . '/..';
    if ($path) {
        return $componentPath . '/' . ltrim($path, '/');
    }
    return $componentPath;
}

/**
 * Sanitize component name
 * @param string $name Component name
 * @return string Sanitized component name
 */
function component_manager_sanitize_component_name($name) {
    // Only allow lowercase letters, numbers, and underscores
    return preg_replace('/[^a-z0-9_]/', '', strtolower($name));
}

/**
 * Validate component name
 * @param string $name Component name
 * @return bool True if valid
 */
function component_manager_validate_component_name($name) {
    // Must be lowercase with underscores, 1-100 characters
    return preg_match('/^[a-z][a-z0-9_]{0,99}$/', $name) === 1;
}

/**
 * Get component version from VERSION file
 * @param string $componentName Component name
 * @param string $componentPath Component path
 * @return string|null Version or null if not found
 */
function component_manager_get_component_version_file($componentName, $componentPath) {
    $versionFile = $componentPath . '/VERSION';
    if (file_exists($versionFile)) {
        return trim(file_get_contents($versionFile));
    }
    return null;
}

/**
 * Compare semantic versions
 * @param string $version1 First version
 * @param string $version2 Second version
 * @return int -1 if version1 < version2, 0 if equal, 1 if version1 > version2
 */
function component_manager_compare_versions($version1, $version2) {
    return version_compare($version1, $version2);
}

/**
 * Check if savepoints component is available
 * @return bool True if savepoints is installed
 */
function component_manager_savepoints_available() {
    // Check if savepoints component exists
    $savepointsPath = __DIR__ . '/../../savepoints';
    if (!file_exists($savepointsPath)) {
        return false;
    }
    
    // Check if savepoints config exists
    if (!file_exists($savepointsPath . '/config.php')) {
        return false;
    }
    
    // Check if savepoints_history table exists
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $result = $conn->query("SHOW TABLES LIKE 'savepoints_history'");
        return $result->num_rows > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Alias for savepoints_integration compatibility
function component_manager_savepoints_available_check() {
    return component_manager_savepoints_available();
}

