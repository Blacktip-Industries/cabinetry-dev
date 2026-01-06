<?php
/**
 * URL Routing Component - Helper Functions
 * Core utility functions with url_routing_ prefix
 */

require_once __DIR__ . '/database.php';

/**
 * Get base URL dynamically
 * @return string Base URL
 */
function url_routing_get_base_url() {
    if (defined('URL_ROUTING_BASE_URL') && !empty(URL_ROUTING_BASE_URL)) {
        return URL_ROUTING_BASE_URL;
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
 * Get base path for routing (project folder name if in subdirectory)
 * @return string Base path
 */
function url_routing_get_base_path() {
    if (php_sapi_name() === 'cli') {
        return '';
    }
    
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir = dirname($script);
    
    // Extract project folder name if in subdirectory
    // e.g., /cabinetry-dev/admin/... -> /cabinetry-dev
    $parts = explode('/', trim($dir, '/'));
    if (count($parts) > 1 && $parts[0] !== 'admin') {
        return '/' . $parts[0];
    }
    
    return '';
}

/**
 * Get project root directory
 * @return string Project root path
 */
function url_routing_get_project_root() {
    if (defined('URL_ROUTING_ROOT_PATH') && !empty(URL_ROUTING_ROOT_PATH)) {
        return URL_ROUTING_ROOT_PATH;
    }
    
    // Default: go up from admin/components/url_routing
    return dirname(dirname(dirname(dirname(__DIR__))));
}

/**
 * Get static routes (hardcoded routes for performance)
 * @return array Associative array of slug => file_path
 */
function url_routing_get_static_routes() {
    return [
        'dashboard' => 'admin/dashboard.php',
        'login' => 'admin/login.php',
        'logout' => 'admin/logout.php',
        'user-add' => 'admin/users/add.php',
        'user-edit' => 'admin/users/edit.php',
    ];
}

/**
 * Generate clean URL from slug
 * @param string $slug Route slug
 * @param array $params Query parameters
 * @return string Full URL
 */
function url_routing_url($slug, $params = []) {
    $baseUrl = url_routing_get_base_url();
    $basePath = url_routing_get_base_path();
    
    $url = rtrim($baseUrl, '/') . $basePath . '/' . ltrim($slug, '/');
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Generate slug from file path
 * @param string $filePath File path (e.g., admin/users/add.php)
 * @return string Generated slug (e.g., user-add)
 */
function url_routing_generate_slug_from_path($filePath) {
    // Remove .php extension
    $filePath = preg_replace('/\.php$/', '', $filePath);
    
    // Remove admin/ prefix if present
    $filePath = preg_replace('/^admin\//', '', $filePath);
    
    // Replace slashes and underscores with hyphens
    $slug = str_replace(['/', '_'], '-', $filePath);
    
    // Convert to lowercase
    $slug = strtolower($slug);
    
    // Remove any non-alphanumeric characters except hyphens
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    
    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Trim hyphens from start and end
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Validate slug format
 * @param string $slug Slug to validate
 * @return bool True if valid
 */
function url_routing_validate_slug($slug) {
    // Only alphanumeric, hyphens, and underscores allowed
    return preg_match('/^[a-z0-9\-_]+$/i', $slug) === 1;
}

/**
 * Validate file path (security check)
 * @param string $filePath File path to validate
 * @param string $projectRoot Project root directory
 * @return bool True if valid and safe
 */
function url_routing_validate_file_path($filePath, $projectRoot = null) {
    if ($projectRoot === null) {
        $projectRoot = url_routing_get_project_root();
    }
    
    // Prevent directory traversal
    if (strpos($filePath, '..') !== false) {
        return false;
    }
    
    // Normalize path
    $fullPath = realpath($projectRoot . '/' . $filePath);
    $rootPath = realpath($projectRoot);
    
    // Ensure file is within project root
    if ($fullPath === false || $rootPath === false) {
        return false;
    }
    
    return strpos($fullPath, $rootPath) === 0;
}

