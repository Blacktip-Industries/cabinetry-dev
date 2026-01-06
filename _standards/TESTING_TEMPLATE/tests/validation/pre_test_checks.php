<?php
/**
 * Pre-Test Validation Checks
 * Validates environment before running tests
 */

/**
 * Run all pre-test checks
 */
function run_pre_test_checks() {
    $results = [
        'success' => true,
        'errors' => [],
        'warnings' => []
    ];
    
    // Check PHP version
    $phpCheck = check_php_version();
    if (!$phpCheck['success']) {
        $results['success'] = false;
        $results['errors'] = array_merge($results['errors'], $phpCheck['errors']);
    }
    
    // Check required extensions
    $extCheck = check_required_extensions();
    if (!$extCheck['success']) {
        $results['success'] = false;
        $results['errors'] = array_merge($results['errors'], $extCheck['errors']);
    }
    
    // Check database connectivity
    $dbCheck = check_database_connectivity();
    if (!$dbCheck['success']) {
        $results['success'] = false;
        $results['errors'] = array_merge($results['errors'], $dbCheck['errors']);
    }
    
    // Check file permissions
    $permCheck = check_file_permissions();
    if (!$permCheck['success']) {
        $results['warnings'] = array_merge($results['warnings'], $permCheck['warnings']);
    }
    
    return $results;
}

/**
 * Check PHP version
 */
function check_php_version() {
    $minVersion = '7.4.0';
    $currentVersion = PHP_VERSION;
    
    if (version_compare($currentVersion, $minVersion, '<')) {
        return [
            'success' => false,
            'errors' => ["PHP version {$currentVersion} is below minimum required {$minVersion}"]
        ];
    }
    
    return ['success' => true, 'errors' => []];
}

/**
 * Check required extensions
 */
function check_required_extensions() {
    $required = ['mysqli', 'json', 'mbstring'];
    $missing = [];
    
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    
    if (!empty($missing)) {
        return [
            'success' => false,
            'errors' => ["Missing required PHP extensions: " . implode(', ', $missing)]
        ];
    }
    
    return ['success' => true, 'errors' => []];
}

/**
 * Check database connectivity
 */
function check_database_connectivity() {
    try {
        $conn = get_main_db_connection();
        if (!$conn) {
            return [
                'success' => false,
                'errors' => ['Cannot connect to database']
            ];
        }
        return ['success' => true, 'errors' => []];
    } catch (Exception $e) {
        return [
            'success' => false,
            'errors' => ['Database connection failed: ' . $e->getMessage()]
        ];
    }
}

/**
 * Check file permissions
 */
function check_file_permissions() {
    $warnings = [];
    $testDir = __DIR__ . '/../..';
    
    if (!is_writable($testDir)) {
        $warnings[] = "Test directory is not writable: {$testDir}";
    }
    
    return [
        'success' => empty($warnings),
        'warnings' => $warnings
    ];
}

