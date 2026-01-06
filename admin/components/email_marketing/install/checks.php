<?php
/**
 * Email Marketing Component - System Compatibility Checks
 */

/**
 * Check PHP version
 * @return array Check result
 */
function email_marketing_check_php_version() {
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
function email_marketing_check_extensions() {
    $required = ['mysqli', 'json', 'mbstring', 'curl'];
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
function email_marketing_check_permissions() {
    $componentPath = dirname(dirname(__DIR__));
    $results = [];
    
    $paths = [
        $componentPath,
        $componentPath . '/assets/css',
        $componentPath . '/assets/js'
    ];
    
    foreach ($paths as $path) {
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
 * Run all compatibility checks
 * @return array All check results
 */
function email_marketing_run_checks() {
    $checks = [
        'php_version' => email_marketing_check_php_version(),
        'extensions' => email_marketing_check_extensions(),
        'permissions' => email_marketing_check_permissions()
    ];
    
    $allPassed = true;
    foreach ($checks as $checkGroup) {
        if (is_array($checkGroup) && isset($checkGroup['passed'])) {
            if (!$checkGroup['passed']) {
                $allPassed = false;
            }
        } else {
            foreach ($checkGroup as $check) {
                if (!$check['passed']) {
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

