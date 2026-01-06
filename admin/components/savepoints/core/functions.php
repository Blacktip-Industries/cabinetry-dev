<?php
/**
 * Savepoints Component - Helper Functions
 * Core utility functions with savepoints_ prefix
 */

require_once __DIR__ . '/database.php';

/**
 * Get base URL dynamically
 * @return string Base URL
 */
function savepoints_get_base_url() {
    if (defined('SAVEPOINTS_BASE_URL') && !empty(SAVEPOINTS_BASE_URL)) {
        return SAVEPOINTS_BASE_URL;
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
function savepoints_get_admin_url($path = '') {
    if (defined('SAVEPOINTS_ADMIN_URL') && !empty(SAVEPOINTS_ADMIN_URL)) {
        return SAVEPOINTS_ADMIN_URL . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    $base = savepoints_get_base_url();
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
function savepoints_get_component_url($path = '') {
    $base = savepoints_get_base_url();
    $componentPath = '/admin/components/savepoints';
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
function savepoints_get_component_path($file = '') {
    $componentPath = defined('SAVEPOINTS_ROOT_PATH') 
        ? SAVEPOINTS_ROOT_PATH . '/admin/components/savepoints'
        : __DIR__ . '/..';
    
    if (empty($file)) {
        return $componentPath;
    }
    
    return $componentPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
}

/**
 * Get project root path
 * @return string Project root path
 */
function savepoints_get_project_root() {
    if (defined('SAVEPOINTS_ROOT_PATH') && !empty(SAVEPOINTS_ROOT_PATH)) {
        return SAVEPOINTS_ROOT_PATH;
    }
    
    // Go up from admin/components/savepoints/core to project root
    return dirname(dirname(dirname(__DIR__)));
}

/**
 * Normalize file path (convert backslashes to forward slashes)
 * @param string $path File path
 * @return string Normalized path
 */
function savepoints_normalize_path($path) {
    return str_replace('\\', '/', $path);
}

/**
 * Validate file path to prevent directory traversal
 * @param string $path File path to validate
 * @param string $basePath Base path that file must be within
 * @return bool True if valid, false otherwise
 */
function savepoints_validate_path($path, $basePath) {
    $realPath = realpath($path);
    $realBase = realpath($basePath);
    
    if ($realPath === false || $realBase === false) {
        return false;
    }
    
    // Check if path is within base path
    return strpos($realPath, $realBase) === 0;
}

/**
 * Encrypt sensitive data (simple base64 encoding for now, can be enhanced)
 * @param string $data Data to encrypt
 * @return string Encrypted data
 */
function savepoints_encrypt($data) {
    if (empty($data)) {
        return '';
    }
    // Simple encoding - in production, use proper encryption
    return base64_encode($data);
}

/**
 * Decrypt sensitive data
 * @param string $encryptedData Encrypted data
 * @return string Decrypted data
 */
function savepoints_decrypt($encryptedData) {
    if (empty($encryptedData)) {
        return '';
    }
    // Simple decoding - in production, use proper decryption
    return base64_decode($encryptedData);
}

/**
 * Get excluded directories from parameters
 * @return array Array of excluded directory patterns
 */
function savepoints_get_excluded_directories() {
    $excludedJson = savepoints_get_parameter('Backup', 'excluded_directories', '["uploads", "node_modules", "vendor", ".git"]');
    $excluded = json_decode($excludedJson, true);
    return is_array($excluded) ? $excluded : ['uploads', 'node_modules', 'vendor', '.git'];
}

/**
 * Get included directories from parameters
 * @return array Array of included directory patterns (empty = all)
 */
function savepoints_get_included_directories() {
    $includedJson = savepoints_get_parameter('Backup', 'included_directories', '[]');
    $included = json_decode($includedJson, true);
    return is_array($included) ? $included : [];
}

/**
 * Check if path should be excluded from backup
 * @param string $path File or directory path
 * @param array $excludedDirs Array of excluded patterns
 * @return bool True if should be excluded
 */
function savepoints_is_excluded($path, $excludedDirs = null) {
    if ($excludedDirs === null) {
        $excludedDirs = savepoints_get_excluded_directories();
    }
    
    $normalizedPath = savepoints_normalize_path($path);
    
    foreach ($excludedDirs as $excluded) {
        $excludedNormalized = savepoints_normalize_path($excluded);
        // Check if path starts with excluded directory
        if (strpos($normalizedPath, $excludedNormalized) === 0) {
            return true;
        }
        // Check if excluded pattern matches anywhere in path
        if (strpos($normalizedPath, '/' . $excludedNormalized . '/') !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Format file size in human-readable format
 * @param int $bytes File size in bytes
 * @return string Formatted size
 */
function savepoints_format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Sanitize commit message
 * @param string $message Commit message
 * @return string Sanitized message
 */
function savepoints_sanitize_message($message) {
    // Remove null bytes and trim
    $message = str_replace("\0", '', $message);
    $message = trim($message);
    
    // Limit length
    if (strlen($message) > 500) {
        $message = substr($message, 0, 500);
    }
    
    return $message;
}

