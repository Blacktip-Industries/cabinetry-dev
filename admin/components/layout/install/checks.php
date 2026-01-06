<?php
/**
 * Layout Component - System Compatibility Checks
 * Checks PHP version, extensions, and system requirements
 */

/**
 * Run all compatibility checks
 * @return array Check results
 */
function layout_run_compatibility_checks() {
    $checks = [
        'php_version' => layout_check_php_version(),
        'mysqli' => layout_check_extension('mysqli'),
        'json' => layout_check_extension('json'),
        'mbstring' => layout_check_extension('mbstring'),
        'file_permissions' => layout_check_file_permissions(),
    ];
    
    $allPassed = true;
    foreach ($checks as $check) {
        if (!$check['passed']) {
            $allPassed = false;
            break;
        }
    }
    
    return [
        'all_passed' => $allPassed,
        'checks' => $checks
    ];
}

/**
 * Check PHP version (requires 7.4+)
 * @return array Check result
 */
function layout_check_php_version() {
    $required = '7.4.0';
    $current = PHP_VERSION;
    $passed = version_compare($current, $required, '>=');
    
    return [
        'passed' => $passed,
        'message' => $passed 
            ? "PHP version {$current} meets requirement ({$required}+)"
            : "PHP version {$current} does not meet requirement ({$required}+)",
        'current' => $current,
        'required' => $required
    ];
}

/**
 * Check if PHP extension is loaded
 * @param string $extension Extension name
 * @return array Check result
 */
function layout_check_extension($extension) {
    $loaded = extension_loaded($extension);
    
    return [
        'passed' => $loaded,
        'message' => $loaded 
            ? "Extension '{$extension}' is loaded"
            : "Extension '{$extension}' is not loaded",
        'extension' => $extension
    ];
}

/**
 * Check file permissions for component directory
 * @return array Check result
 */
function layout_check_file_permissions() {
    $componentPath = __DIR__ . '/..';
    $writable = is_writable($componentPath);
    
    return [
        'passed' => $writable,
        'message' => $writable 
            ? "Component directory is writable"
            : "Component directory is not writable",
        'path' => $componentPath
    ];
}

