<?php
/**
 * Mobile API Component - System Compatibility Checks
 * Checks PHP version, extensions, and system requirements
 */

/**
 * Check PHP version
 * @return array Check result
 */
function mobile_api_check_php_version() {
    $required = '7.4';
    $current = PHP_VERSION;
    $passed = version_compare($current, $required, '>=');
    
    return [
        'name' => 'PHP Version',
        'required' => $required . ' or higher',
        'current' => $current,
        'passed' => $passed,
        'message' => $passed ? 'PHP version is compatible' : "PHP version must be {$required} or higher"
    ];
}

/**
 * Check required PHP extensions
 * @return array Check results
 */
function mobile_api_check_extensions() {
    $required = ['mysqli', 'json', 'mbstring'];
    $results = [];
    
    foreach ($required as $ext) {
        $loaded = extension_loaded($ext);
        $results[] = [
            'name' => $ext . ' extension',
            'required' => 'Required',
            'current' => $loaded ? 'Loaded' : 'Not loaded',
            'passed' => $loaded,
            'message' => $loaded ? "{$ext} extension is loaded" : "{$ext} extension is required"
        ];
    }
    
    return $results;
}

/**
 * Check HTTPS (required for PWA)
 * @return array Check result
 */
function mobile_api_check_https() {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    return [
        'name' => 'HTTPS',
        'required' => 'Required for PWA features',
        'current' => $is_https ? 'Enabled' : 'Not enabled',
        'passed' => $is_https || php_sapi_name() === 'cli',
        'message' => $is_https || php_sapi_name() === 'cli' 
            ? 'HTTPS is enabled (or CLI mode)' 
            : 'HTTPS is required for PWA features (service worker, push notifications)'
    ];
}

/**
 * Check file permissions
 * @return array Check results
 */
function mobile_api_check_permissions() {
    $component_dir = __DIR__ . '/..';
    $writable_dirs = [
        $component_dir,
        $component_dir . '/assets',
        $component_dir . '/assets/css',
        $component_dir . '/assets/js'
    ];
    
    $results = [];
    foreach ($writable_dirs as $dir) {
        if (!file_exists($dir)) {
            $results[] = [
                'name' => basename($dir) . ' directory',
                'required' => 'Must exist',
                'current' => 'Does not exist',
                'passed' => false,
                'message' => "Directory does not exist: {$dir}"
            ];
            continue;
        }
        
        $writable = is_writable($dir);
        $results[] = [
            'name' => basename($dir) . ' directory',
            'required' => 'Writable',
            'current' => $writable ? 'Writable' : 'Not writable',
            'passed' => $writable,
            'message' => $writable ? "Directory is writable" : "Directory must be writable: {$dir}"
        ];
    }
    
    return $results;
}

/**
 * Run all compatibility checks
 * @return array All check results
 */
function mobile_api_run_checks() {
    $checks = [];
    
    // PHP version
    $checks[] = mobile_api_check_php_version();
    
    // Extensions
    $checks = array_merge($checks, mobile_api_check_extensions());
    
    // HTTPS
    $checks[] = mobile_api_check_https();
    
    // Permissions
    $checks = array_merge($checks, mobile_api_check_permissions());
    
    // Overall status
    $all_passed = true;
    foreach ($checks as $check) {
        if (!$check['passed']) {
            $all_passed = false;
            break;
        }
    }
    
    return [
        'all_passed' => $all_passed,
        'checks' => $checks
    ];
}

