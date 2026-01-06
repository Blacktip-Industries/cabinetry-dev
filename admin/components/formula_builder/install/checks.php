<?php
/**
 * Formula Builder Component - System Compatibility Checks
 */

/**
 * Check PHP version
 * @return array Result with success status and message
 */
function formula_builder_check_php_version() {
    $requiredVersion = '7.4.0';
    $currentVersion = PHP_VERSION;
    
    if (version_compare($currentVersion, $requiredVersion, '>=')) {
        return ['success' => true, 'message' => "PHP version {$currentVersion} meets requirement ({$requiredVersion})"];
    } else {
        return ['success' => false, 'message' => "PHP version {$currentVersion} is below requirement ({$requiredVersion})"];
    }
}

/**
 * Check required PHP extensions
 * @return array Result with success status and missing extensions
 */
function formula_builder_check_extensions() {
    $required = ['mysqli', 'json', 'mbstring'];
    $missing = [];
    
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    
    if (empty($missing)) {
        return ['success' => true, 'message' => 'All required extensions are loaded'];
    } else {
        return ['success' => false, 'message' => 'Missing extensions: ' . implode(', ', $missing), 'missing' => $missing];
    }
}

/**
 * Check file permissions
 * @return array Result with success status and issues
 */
function formula_builder_check_permissions() {
    $issues = [];
    $componentPath = __DIR__ . '/..';
    
    // Check if config.php can be created
    $configPath = $componentPath . '/config.php';
    if (file_exists($configPath) && !is_writable($configPath)) {
        $issues[] = 'config.php is not writable';
    } elseif (!file_exists($configPath) && !is_writable($componentPath)) {
        $issues[] = 'Component directory is not writable';
    }
    
    // Check assets directory
    $assetsPath = $componentPath . '/assets';
    if (!is_dir($assetsPath)) {
        @mkdir($assetsPath, 0755, true);
    }
    if (!is_writable($assetsPath)) {
        $issues[] = 'assets directory is not writable';
    }
    
    if (empty($issues)) {
        return ['success' => true, 'message' => 'File permissions are correct'];
    } else {
        return ['success' => false, 'message' => implode(', ', $issues), 'issues' => $issues];
    }
}

/**
 * Run all compatibility checks
 * @return array Results of all checks
 */
function formula_builder_run_checks() {
    return [
        'php_version' => formula_builder_check_php_version(),
        'extensions' => formula_builder_check_extensions(),
        'permissions' => formula_builder_check_permissions()
    ];
}

