<?php
/**
 * Product Options Component - System Compatibility Checks
 */

/**
 * Check PHP version
 * @return array Result with success status
 */
function product_options_check_php_version() {
    $minVersion = '7.4.0';
    $currentVersion = PHP_VERSION;
    
    if (version_compare($currentVersion, $minVersion, '>=')) {
        return ['success' => true, 'version' => $currentVersion];
    }
    
    return ['success' => false, 'version' => $currentVersion, 'required' => $minVersion];
}

/**
 * Check required PHP extensions
 * @return array Result with success status and missing extensions
 */
function product_options_check_extensions() {
    $required = ['mysqli', 'json'];
    $missing = [];
    
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    
    return [
        'success' => empty($missing),
        'missing' => $missing
    ];
}

/**
 * Check file permissions
 * @return array Result with success status
 */
function product_options_check_permissions() {
    $componentPath = __DIR__ . '/..';
    $writable = [
        $componentPath,
        $componentPath . '/assets/css',
        $componentPath . '/assets/js'
    ];
    
    $issues = [];
    foreach ($writable as $path) {
        if (!is_writable($path)) {
            $issues[] = $path;
        }
    }
    
    return [
        'success' => empty($issues),
        'issues' => $issues
    ];
}

/**
 * Run all compatibility checks
 * @return array Results of all checks
 */
function product_options_run_checks() {
    return [
        'php_version' => product_options_check_php_version(),
        'extensions' => product_options_check_extensions(),
        'permissions' => product_options_check_permissions()
    ];
}

