<?php
/**
 * {COMPONENT_NAME} Component - Test Bootstrap
 * Initialize test environment with comprehensive utilities
 * 
 * Replace {COMPONENT_NAME} with your component name throughout this file
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test environment flag
define('TESTING', true);
define('TEST_DB_PREFIX', '{component_name}_test_');

// Load component configuration
$configPath = __DIR__ . '/../../config.php';
if (!file_exists($configPath)) {
    throw new Exception("Component config.php not found. Please install the component first.");
}
require_once $configPath;

// Load component core files
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Load test utilities
require_once __DIR__ . '/database/create_test_db.php';
require_once __DIR__ . '/fixtures/loader.php';
require_once __DIR__ . '/mocks/MockBase.php';
require_once __DIR__ . '/validation/pre_test_checks.php';
require_once __DIR__ . '/errors/error_formatter.php';

// ============================================================================
// ASSERTION HELPER FUNCTIONS
// ============================================================================

/**
 * Assert that a condition is true
 */
function assert_true($condition, $message = '') {
    if (!$condition) {
        $formatted = format_assertion_error('assert_true', $message, [
            'condition' => $condition,
            'expected' => true
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Assert that a condition is false
 */
function assert_false($condition, $message = '') {
    assert_true(!$condition, $message ?: "Expected false, got true");
}

/**
 * Assert that two values are equal
 */
function assert_equals($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        $formatted = format_assertion_error('assert_equals', $message, [
            'expected' => $expected,
            'actual' => $actual
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Assert that two values are not equal
 */
function assert_not_equals($expected, $actual, $message = '') {
    if ($expected === $actual) {
        $formatted = format_assertion_error('assert_not_equals', $message, [
            'expected_not' => $expected,
            'actual' => $actual
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Assert that a value is not null
 */
function assert_not_null($value, $message = '') {
    if ($value === null) {
        $formatted = format_assertion_error('assert_not_null', $message, [
            'value' => null
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Assert that a value is null
 */
function assert_null($value, $message = '') {
    if ($value !== null) {
        $formatted = format_assertion_error('assert_null', $message, [
            'value' => $value,
            'expected' => null
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Assert that an array has a key
 */
function assert_array_has_key($key, $array, $message = '') {
    if (!isset($array[$key]) && !array_key_exists($key, $array)) {
        $formatted = format_assertion_error('assert_array_has_key', $message, [
            'key' => $key,
            'array_keys' => array_keys($array)
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Assert that an array does not have a key
 */
function assert_array_not_has_key($key, $array, $message = '') {
    if (isset($array[$key]) || array_key_exists($key, $array)) {
        $formatted = format_assertion_error('assert_array_not_has_key', $message, [
            'key' => $key,
            'found_in' => $array
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Assert that a value is an instance of a class
 */
function assert_instance_of($expectedClass, $actual, $message = '') {
    if (!($actual instanceof $expectedClass)) {
        $actualClass = is_object($actual) ? get_class($actual) : gettype($actual);
        $formatted = format_assertion_error('assert_instance_of', $message, [
            'expected_class' => $expectedClass,
            'actual_class' => $actualClass
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Assert that a string contains a substring
 */
function assert_contains($needle, $haystack, $message = '') {
    if (strpos($haystack, $needle) === false) {
        $formatted = format_assertion_error('assert_contains', $message, [
            'needle' => $needle,
            'haystack' => $haystack
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Assert that a string does not contain a substring
 */
function assert_not_contains($needle, $haystack, $message = '') {
    if (strpos($haystack, $needle) !== false) {
        $formatted = format_assertion_error('assert_not_contains', $message, [
            'needle' => $needle,
            'haystack' => $haystack
        ]);
        throw new Exception($formatted);
    }
}

/**
 * Format assertion error with context
 */
function format_assertion_error($assertion, $message, $context = []) {
    $error = "Assertion failed: {$assertion}";
    if ($message) {
        $error .= " - {$message}";
    }
    if (!empty($context)) {
        $error .= "\nContext: " . json_encode($context, JSON_PRETTY_PRINT);
    }
    $error .= "\n" . get_stack_trace();
    return $error;
}

/**
 * Get formatted stack trace
 */
function get_stack_trace() {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $output = "Stack trace:\n";
    foreach ($trace as $i => $frame) {
        $file = $frame['file'] ?? 'unknown';
        $line = $frame['line'] ?? 'unknown';
        $function = $frame['function'] ?? 'unknown';
        $output .= "  #{$i} {$file}({$line}): {$function}()\n";
    }
    return $output;
}

// ============================================================================
// TEST DATABASE SETUP
// ============================================================================

/**
 * Get or create isolated test database connection
 */
function get_test_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        // Create isolated test database
        $testDbName = create_test_database();
        $conn = connect_to_test_database($testDbName);
        
        // Migrate schema
        migrate_test_database_schema($conn);
    }
    
    return $conn;
}

/**
 * Cleanup test database
 */
function cleanup_test_database() {
    $testDbName = get_test_database_name();
    if ($testDbName) {
        drop_test_database($testDbName);
    }
}

// ============================================================================
// TEST DATA CLEANUP
// ============================================================================

/**
 * Cleanup test data
 * Override this function in component-specific bootstrap
 */
function cleanup_test_data() {
    // Component-specific cleanup
    // Example: Delete test records with 'Test' prefix
}

// Register cleanup on shutdown
register_shutdown_function('cleanup_test_data');
register_shutdown_function('cleanup_test_database');

// ============================================================================
// PRE-TEST VALIDATION
// ============================================================================

// Run pre-test checks
$preTestResults = run_pre_test_checks();
if (!$preTestResults['success']) {
    echo "Pre-test validation failed:\n";
    foreach ($preTestResults['errors'] as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}

// ============================================================================
// TEST ENVIRONMENT READY
// ============================================================================

// Set global test state
$GLOBALS['test_count'] = 0;
$GLOBALS['test_passed'] = 0;
$GLOBALS['test_failed'] = 0;
$GLOBALS['test_start_time'] = microtime(true);

