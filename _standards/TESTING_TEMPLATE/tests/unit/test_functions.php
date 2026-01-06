<?php
/**
 * {COMPONENT_NAME} Component - Unit Tests Example
 * Test individual functions
 * 
 * Replace {COMPONENT_NAME} with your component name
 */

require_once __DIR__ . '/../bootstrap.php';

$GLOBALS['test_count'] = 0;
$GLOBALS['test_passed'] = 0;

function run_test($name, $callback) {
    $GLOBALS['test_count']++;
    try {
        $callback();
        $GLOBALS['test_passed']++;
        echo "  ✓ {$name}\n";
    } catch (Exception $e) {
        echo "  ✗ {$name}: " . $e->getMessage() . "\n";
    }
}

echo "Unit Tests - Functions\n";
echo str_repeat("-", 40) . "\n";

// Example Test 1: Basic assertion
run_test('Test basic assertion', function() {
    assert_true(true, 'Should be true');
    assert_false(false, 'Should be false');
});

// Example Test 2: Equality checks
run_test('Test equality assertions', function() {
    assert_equals(2, 1 + 1, 'Math should work');
    assert_not_equals(1, 2, 'Values should not be equal');
});

// Example Test 3: Null checks
run_test('Test null assertions', function() {
    $value = 'test';
    assert_not_null($value, 'Value should not be null');
    
    $nullValue = null;
    assert_null($nullValue, 'Value should be null');
});

// Example Test 4: Array checks
run_test('Test array assertions', function() {
    $array = ['key' => 'value', 'other' => 'data'];
    assert_array_has_key('key', $array, 'Array should have key');
    assert_equals('value', $array['key'], 'Array value should match');
});

// Example Test 5: String checks
run_test('Test string assertions', function() {
    $string = 'Hello World';
    assert_contains('World', $string, 'String should contain substring');
    assert_not_contains('Goodbye', $string, 'String should not contain substring');
});

// Example Test 6: Database connection
run_test('Test database connection', function() {
    $conn = get_test_db_connection();
    assert_not_null($conn, 'Database connection should exist');
});

// Example Test 7: Component function (customize for your component)
// run_test('Test component function', function() {
//     $result = {component_name}_some_function('input');
//     assert_equals('expected', $result, 'Function should return expected value');
// });

echo "\n";

