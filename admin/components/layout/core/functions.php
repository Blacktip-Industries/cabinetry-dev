<?php
/**
 * Layout Component - Helper Functions
 * Core utility functions with layout_ prefix
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/component_detector.php';

/**
 * Get base URL dynamically
 * @return string Base URL
 */
function layout_get_base_url() {
    if (defined('LAYOUT_BASE_URL') && !empty(LAYOUT_BASE_URL)) {
        return LAYOUT_BASE_URL;
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
function layout_get_admin_url($path = '') {
    if (defined('LAYOUT_ADMIN_URL') && !empty(LAYOUT_ADMIN_URL)) {
        return LAYOUT_ADMIN_URL . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    $base = layout_get_base_url();
    $adminPath = '/admin';
    if ($path && $path[0] !== '/') {
        $adminPath .= '/';
    }
    return $base . $adminPath . $path;
}

/**
 * Get component URL
 * @param string $path Path relative to component
 * @return string Component URL
 */
function layout_get_component_url($path = '') {
    $base = layout_get_base_url();
    $componentPath = '/admin/components/layout';
    if ($path && $path[0] !== '/') {
        $componentPath .= '/';
    }
    return $base . $componentPath . $path;
}

/**
 * Get component path
 * @param string $file File path relative to component root
 * @return string Full file path
 */
function layout_get_component_path($file = '') {
    $componentRoot = __DIR__ . '/..';
    if ($file) {
        return $componentRoot . '/' . ltrim($file, '/');
    }
    return $componentRoot;
}

/**
 * Determine correct CSS/JS path based on current script location
 * Handles subdirectories like setup/, settings/, scripts/, etc.
 * @param string $assetPath Asset path relative to admin/assets
 * @return string Correct relative path to asset
 */
function layout_get_asset_path($assetPath) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/');
    $scriptDir = str_replace('\\', '/', $scriptDir);
    
    // Check if script is in a subdirectory
    $subdirs = ['/setup', '/settings', '/scripts', '/backups', '/customers', '/components'];
    $isSubdir = false;
    foreach ($subdirs as $subdir) {
        if (strpos($scriptDir, $subdir) !== false || strpos($scriptDir, str_replace('/', '\\', $subdir)) !== false) {
            $isSubdir = true;
            break;
        }
    }
    
    if ($isSubdir) {
        return '../assets/' . $assetPath;
    } else {
        return 'assets/' . $assetPath;
    }
}

/**
 * Get current page identifier from script path
 * @return string Current page identifier
 */
function layout_get_current_page() {
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = str_replace('\\', '/', $scriptPath);
    $adminPath = '/admin/';
    $adminPos = strpos($scriptPath, $adminPath);
    
    if ($adminPos !== false) {
        return substr($scriptPath, $adminPos + strlen($adminPath));
    }
    
    return basename($scriptPath);
}

