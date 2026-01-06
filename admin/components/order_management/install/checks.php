<?php
/**
 * Order Management Component - System Compatibility Checks
 */

/**
 * Check PHP version
 * @return array Check result
 */
function order_management_check_php_version() {
    $required = '7.4.0';
    $current = PHP_VERSION;
    
    return [
        'name' => 'PHP Version',
        'required' => $required,
        'current' => $current,
        'passed' => version_compare($current, $required, '>=')
    ];
}

/**
 * Check required PHP extensions
 * @return array Check results
 */
function order_management_check_extensions() {
    $required = ['mysqli', 'json', 'mbstring', 'openssl'];
    $results = [];
    
    foreach ($required as $ext) {
        $results[] = [
            'name' => $ext . ' extension',
            'required' => 'Yes',
            'current' => extension_loaded($ext) ? 'Yes' : 'No',
            'passed' => extension_loaded($ext)
        ];
    }
    
    return $results;
}

/**
 * Check file permissions
 * @return array Check results
 */
function order_management_check_permissions() {
    $componentPath = dirname(dirname(__DIR__));
    $results = [];
    
    $paths = [
        $componentPath,
        $componentPath . '/assets/css',
        $componentPath . '/assets/js',
        $componentPath . '/admin',
        $componentPath . '/api',
        $componentPath . '/cron'
    ];
    
    foreach ($paths as $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        $writable = is_writable($path);
        $results[] = [
            'name' => basename($path) . ' directory',
            'required' => 'Writable',
            'current' => $writable ? 'Writable' : 'Not writable',
            'passed' => $writable
        ];
    }
    
    return $results;
}

/**
 * Check if commerce component is available
 * @return array Check result
 */
function order_management_check_commerce() {
    $available = false;
    $message = 'Commerce component not found';
    
    // Try to detect commerce
    $basePath = dirname(dirname(dirname(__DIR__)));
    $commercePath = $basePath . '/commerce';
    if (is_dir($commercePath)) {
        require_once $commercePath . '/core/database.php';
        if (function_exists('commerce_is_installed')) {
            $available = commerce_is_installed();
            $message = $available ? 'Commerce component is installed' : 'Commerce component found but not installed';
        }
    }
    
    return [
        'name' => 'Commerce Component',
        'required' => 'Recommended',
        'current' => $available ? 'Available' : $message,
        'passed' => true, // Not required, just recommended
        'warning' => !$available
    ];
}

/**
 * Run all compatibility checks
 * @return array All check results
 */
function order_management_run_checks() {
    $checks = [
        'php_version' => order_management_check_php_version(),
        'extensions' => order_management_check_extensions(),
        'permissions' => order_management_check_permissions(),
        'commerce' => order_management_check_commerce()
    ];
    
    $allPassed = true;
    foreach ($checks as $checkGroup) {
        if (is_array($checkGroup) && isset($checkGroup['passed'])) {
            if (!$checkGroup['passed'] && !isset($checkGroup['warning'])) {
                $allPassed = false;
            }
        } else {
            foreach ($checkGroup as $check) {
                if (!$check['passed'] && !isset($check['warning'])) {
                    $allPassed = false;
                }
            }
        }
    }
    
    return [
        'all_passed' => $allPassed,
        'checks' => $checks
    ];
}

