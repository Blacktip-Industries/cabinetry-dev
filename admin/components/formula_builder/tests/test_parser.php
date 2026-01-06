<?php
/**
 * Formula Builder Component - Parser Tests
 * Test cases for the enhanced formula parser
 */

require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/parser.php';
require_once __DIR__ . '/../core/executor.php';
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/helpers.php';

/**
 * Run all parser tests
 */
function formula_builder_run_parser_tests() {
    $tests = [
        'test_simple_math' => 'Test simple math operations',
        'test_variables' => 'Test variable declarations',
        'test_functions' => 'Test function calls',
        'test_conditionals' => 'Test if/else statements',
        'test_loops' => 'Test for/while loops',
        'test_objects' => 'Test object literals',
        'test_arrays' => 'Test array literals',
        'test_complex' => 'Test complex formula'
    ];
    
    $results = [];
    foreach ($tests as $testName => $description) {
        echo "Running {$testName}...\n";
        try {
            $result = call_user_func($testName);
            $results[$testName] = [
                'success' => $result['success'],
                'message' => $result['message'] ?? '',
                'expected' => $result['expected'] ?? null,
                'actual' => $result['actual'] ?? null
            ];
            if ($result['success']) {
                echo "  ✓ PASSED\n";
            } else {
                echo "  ✗ FAILED: {$result['message']}\n";
            }
        } catch (Exception $e) {
            $results[$testName] = [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
            echo "  ✗ ERROR: {$e->getMessage()}\n";
        }
    }
    
    return $results;
}

/**
 * Test simple math operations
 */
function test_simple_math() {
    $code = "return 10 + 20 * 2;";
    $inputData = [];
    
    $result = formula_builder_execute_formula_code($code, $inputData);
    
    return [
        'success' => $result['success'] && $result['result'] == 50,
        'expected' => 50,
        'actual' => $result['result'],
        'message' => $result['success'] ? '' : $result['error']
    ];
}

/**
 * Test variable declarations
 */
function test_variables() {
    $code = "
        var a = 10;
        var b = 20;
        return a + b;
    ";
    $inputData = [];
    
    $result = formula_builder_execute_formula_code($code, $inputData);
    
    return [
        'success' => $result['success'] && $result['result'] == 30,
        'expected' => 30,
        'actual' => $result['result'],
        'message' => $result['success'] ? '' : $result['error']
    ];
}

/**
 * Test function calls
 */
function test_functions() {
    $code = "
        var width = get_option('width');
        var height = get_option('height');
        return width + height;
    ";
    $inputData = ['width' => 600, 'height' => 800];
    
    $result = formula_builder_execute_formula_code($code, $inputData);
    
    return [
        'success' => $result['success'] && $result['result'] == 1400,
        'expected' => 1400,
        'actual' => $result['result'],
        'message' => $result['success'] ? '' : $result['error']
    ];
}

/**
 * Test conditionals
 */
function test_conditionals() {
    $code = "
        var value = get_option('value');
        if (value > 100) {
            return value * 1.1;
        } else {
            return value;
        }
    ";
    
    $test1 = formula_builder_execute_formula_code($code, ['value' => 150]);
    $test2 = formula_builder_execute_formula_code($code, ['value' => 50]);
    
    $success = $test1['success'] && $test1['result'] == 165 &&
               $test2['success'] && $test2['result'] == 50;
    
    return [
        'success' => $success,
        'expected' => '165 and 50',
        'actual' => ($test1['result'] ?? 'error') . ' and ' . ($test2['result'] ?? 'error'),
        'message' => $success ? '' : 'Conditional logic failed'
    ];
}

/**
 * Test loops
 */
function test_loops() {
    $code = "
        var sum = 0;
        for (var i = 1; i <= 5; i = i + 1) {
            sum = sum + i;
        }
        return sum;
    ";
    $inputData = [];
    
    $result = formula_builder_execute_formula_code($code, $inputData);
    
    return [
        'success' => $result['success'] && $result['result'] == 15,
        'expected' => 15,
        'actual' => $result['result'],
        'message' => $result['success'] ? '' : $result['error']
    ];
}

/**
 * Test object literals
 */
function test_objects() {
    $code = "
        var obj = {
            width: 600,
            height: 800
        };
        return obj.width + obj.height;
    ";
    $inputData = [];
    
    $result = formula_builder_execute_formula_code($code, $inputData);
    
    return [
        'success' => $result['success'] && $result['result'] == 1400,
        'expected' => 1400,
        'actual' => $result['result'],
        'message' => $result['success'] ? '' : $result['error']
    ];
}

/**
 * Test array literals
 */
function test_arrays() {
    $code = "
        var arr = [10, 20, 30];
        return arr[0] + arr[1] + arr[2];
    ";
    $inputData = [];
    
    $result = formula_builder_execute_formula_code($code, $inputData);
    
    return [
        'success' => $result['success'] && $result['result'] == 60,
        'expected' => 60,
        'actual' => $result['result'],
        'message' => $result['success'] ? '' : $result['error']
    ];
}

/**
 * Test complex formula
 */
function test_complex() {
    $code = "
        var width = get_option('width');
        var height = get_option('height');
        var depth = get_option('depth');
        
        var sqm = calculate_sqm(width, height, depth);
        var base_price = get_option('base_price');
        
        var material_cost = sqm * 50;
        var hardware_cost = 25;
        
        var total = base_price + material_cost + hardware_cost;
        
        if (total > 1000) {
            total = total * 0.9;
        }
        
        return total;
    ";
    $inputData = [
        'width' => 600,
        'height' => 800,
        'depth' => 400,
        'base_price' => 100
    ];
    
    $result = formula_builder_execute_formula_code($code, $inputData);
    
    // sqm = calculate_sqm(600, 800, 400) = 2 * (0.6*0.8 + 0.6*0.4 + 0.8*0.4) = 2 * (0.48 + 0.24 + 0.32) = 2 * 1.04 = 2.08
    // material_cost = 2.08 * 50 = 104
    // total = 100 + 104 + 25 = 229
    // Since 229 < 1000, no discount
    
    $expected = 229; // Approximate
    
    return [
        'success' => $result['success'] && abs($result['result'] - $expected) < 1,
        'expected' => $expected,
        'actual' => $result['result'],
        'message' => $result['success'] ? '' : $result['error']
    ];
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Formula Builder Parser Tests\n";
    echo "============================\n\n";
    $results = formula_builder_run_parser_tests();
    
    $passed = 0;
    $failed = 0;
    foreach ($results as $result) {
        if ($result['success']) {
            $passed++;
        } else {
            $failed++;
        }
    }
    
    echo "\n";
    echo "Results: {$passed} passed, {$failed} failed\n";
    exit($failed > 0 ? 1 : 0);
}

