<?php
/**
 * Admin Configuration
 * Base URL and path configuration
 */

// Get the base URL dynamically
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    
    // Get the directory of the current script (e.g., /cabinetry-dev/admin/login.php -> /cabinetry-dev/admin)
    $dir = dirname($script);
    
    // If we're in the admin directory, remove /admin and everything after it to get base
    // Otherwise, use the directory as-is
    if (strpos($dir, '/admin') !== false) {
        // Remove /admin and everything after it (e.g., /cabinetry-dev/admin/setup -> /cabinetry-dev)
        $base = preg_replace('/\/admin.*$/', '', $dir);
    } else {
        $base = $dir;
    }
    
    return $protocol . '://' . $host . $base;
}

// Get admin base URL
function getAdminUrl($path = '') {
    $base = getBaseUrl();
    $adminPath = '/admin';
    if ($path && $path[0] !== '/') {
        $adminPath .= '/';
    }
    return $base . $adminPath . $path;
}

// Get relative admin path (for use in redirects within admin)
function getAdminPath($file = '') {
    if (empty($file)) {
        return '';
    }
    if ($file[0] === '/') {
        return $file;
    }
    return $file;
}

