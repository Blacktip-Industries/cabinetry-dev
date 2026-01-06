<?php
/**
 * URL Routing Component - System Compatibility Checks
 */

/**
 * Check PHP version
 * @return array ['success' => bool, 'message' => string]
 */
function url_routing_check_php_version() {
    $required = '7.4.0';
    $current = PHP_VERSION;
    
    if (version_compare($current, $required, '>=')) {
        return ['success' => true, 'message' => "PHP version OK: {$current}"];
    } else {
        return ['success' => false, 'message' => "PHP version {$current} is below required {$required}"];
    }
}

/**
 * Check required PHP extensions
 * @return array ['success' => bool, 'missing' => array]
 */
function url_routing_check_extensions() {
    $required = ['mysqli', 'json'];
    $missing = [];
    
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    
    if (empty($missing)) {
        return ['success' => true, 'missing' => []];
    } else {
        return ['success' => false, 'missing' => $missing];
    }
}

/**
 * Check file permissions
 * @param string $path Directory path
 * @return array ['success' => bool, 'message' => string]
 */
function url_routing_check_permissions($path) {
    if (!is_writable($path)) {
        return ['success' => false, 'message' => "Directory not writable: {$path}"];
    }
    
    return ['success' => true, 'message' => "Directory writable: {$path}"];
}

/**
 * Run all compatibility checks
 * @return array Results of all checks
 */
function url_routing_run_checks() {
    $results = [
        'php_version' => url_routing_check_php_version(),
        'extensions' => url_routing_check_extensions(),
    ];
    
    $allPassed = true;
    foreach ($results as $check) {
        if (isset($check['success']) && !$check['success']) {
            $allPassed = false;
            break;
        }
    }
    
    return [
        'success' => $allPassed,
        'checks' => $results
    ];
}

