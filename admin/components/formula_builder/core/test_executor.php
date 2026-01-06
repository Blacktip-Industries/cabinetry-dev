<?php
/**
 * Formula Builder Component - Test Execution Engine
 * Executes test cases and compares results
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/executor.php';
require_once __DIR__ . '/tests.php';

/**
 * Run single test case
 * @param int $testId Test ID
 * @return array Result with success status and test results
 */
function formula_builder_run_test($testId) {
    $test = formula_builder_get_test($testId);
    if (!$test) {
        return ['success' => false, 'error' => 'Test not found'];
    }
    
    $formula = formula_builder_get_formula_by_id($test['formula_id']);
    if (!$formula) {
        return ['success' => false, 'error' => 'Formula not found'];
    }
    
    $startTime = microtime(true);
    
    try {
        // Execute formula with test input
        $executionResult = formula_builder_execute_formula($test['formula_id'], $test['input_data']);
        
        $executionTime = round((microtime(true) - $startTime) * 1000); // Convert to milliseconds
        
        $actualResult = $executionResult['success'] ? $executionResult['result'] : null;
        $errorMessage = $executionResult['success'] ? null : ($executionResult['error'] ?? 'Unknown error');
        
        // Determine status
        if (!$executionResult['success']) {
            $status = 'error';
        } elseif ($test['expected_result'] === null) {
            // No expected result - just record the actual result
            $status = 'passed';
        } else {
            // Compare results
            $comparison = formula_builder_compare_results($test['expected_result'], $actualResult);
            $status = $comparison['match'] ? 'passed' : 'failed';
        }
        
        // Update test with results
        $updateData = [
            'actual_result' => $actualResult,
            'status' => $status,
            'execution_time_ms' => $executionTime,
            'last_run_at' => date('Y-m-d H:i:s')
        ];
        
        formula_builder_update_test($testId, $updateData);
        
        // Emit event
        require_once __DIR__ . '/events.php';
        if ($status === 'passed') {
            formula_builder_emit_event('formula.test.passed', $test['formula_id'], $_SESSION['user_id'] ?? null, ['test_id' => $testId, 'test_name' => $test['test_name']]);
        } elseif ($status === 'failed') {
            formula_builder_emit_event('formula.test.failed', $test['formula_id'], $_SESSION['user_id'] ?? null, ['test_id' => $testId, 'test_name' => $test['test_name']]);
        }
        
        return [
            'success' => true,
            'test_id' => $testId,
            'status' => $status,
            'expected_result' => $test['expected_result'],
            'actual_result' => $actualResult,
            'execution_time_ms' => $executionTime,
            'error' => $errorMessage,
            'match' => $status === 'passed' && $test['expected_result'] !== null
        ];
    } catch (Exception $e) {
        $executionTime = round((microtime(true) - $startTime) * 1000);
        
        // Update test with error
        formula_builder_update_test($testId, [
            'status' => 'error',
            'execution_time_ms' => $executionTime,
            'last_run_at' => date('Y-m-d H:i:s'),
            'actual_result' => null
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'test_id' => $testId,
            'status' => 'error',
            'execution_time_ms' => $executionTime
        ];
    }
}

/**
 * Run all tests for a formula
 * @param int $formulaId Formula ID
 * @param array $options Execution options
 * @return array Results for all tests
 */
function formula_builder_run_tests($formulaId, $options = []) {
    $tests = formula_builder_get_tests($formulaId);
    if (empty($tests)) {
        return [
            'success' => true,
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'error' => 0,
            'results' => []
        ];
    }
    
    $results = [];
    $passed = 0;
    $failed = 0;
    $errors = 0;
    
    foreach ($tests as $test) {
        $result = formula_builder_run_test($test['id']);
        $results[] = $result;
        
        if ($result['status'] === 'passed') {
            $passed++;
        } elseif ($result['status'] === 'failed') {
            $failed++;
        } elseif ($result['status'] === 'error') {
            $errors++;
        }
    }
    
    return [
        'success' => true,
        'total' => count($tests),
        'passed' => $passed,
        'failed' => $failed,
        'error' => $errors,
        'results' => $results
    ];
}

/**
 * Compare expected vs actual results
 * @param mixed $expected Expected result
 * @param mixed $actual Actual result
 * @param float $tolerance Tolerance for floating point comparison (default 0.0001)
 * @return array Comparison result
 */
function formula_builder_compare_results($expected, $actual, $tolerance = 0.0001) {
    // Handle null cases
    if ($expected === null && $actual === null) {
        return ['match' => true, 'reason' => 'Both are null'];
    }
    
    if ($expected === null || $actual === null) {
        return ['match' => false, 'reason' => 'One is null, the other is not'];
    }
    
    // Exact match
    if ($expected === $actual) {
        return ['match' => true, 'reason' => 'Exact match'];
    }
    
    // Numeric comparison with tolerance
    if (is_numeric($expected) && is_numeric($actual)) {
        $diff = abs((float)$expected - (float)$actual);
        if ($diff <= $tolerance) {
            return ['match' => true, 'reason' => 'Numeric match within tolerance', 'difference' => $diff];
        } else {
            return ['match' => false, 'reason' => 'Numeric difference exceeds tolerance', 'difference' => $diff];
        }
    }
    
    // Array comparison
    if (is_array($expected) && is_array($actual)) {
        if (count($expected) !== count($actual)) {
            return ['match' => false, 'reason' => 'Array length mismatch'];
        }
        
        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual)) {
                return ['match' => false, 'reason' => "Missing key: {$key}"];
            }
            
            $nested = formula_builder_compare_results($value, $actual[$key], $tolerance);
            if (!$nested['match']) {
                return ['match' => false, 'reason' => "Mismatch at key {$key}: " . $nested['reason']];
            }
        }
        
        return ['match' => true, 'reason' => 'Array match'];
    }
    
    // String comparison (case-insensitive)
    if (is_string($expected) && is_string($actual)) {
        if (strcasecmp($expected, $actual) === 0) {
            return ['match' => true, 'reason' => 'String match (case-insensitive)'];
        }
        return ['match' => false, 'reason' => 'String mismatch'];
    }
    
    // Type mismatch
    return [
        'match' => false,
        'reason' => 'Type mismatch: ' . gettype($expected) . ' vs ' . gettype($actual)
    ];
}

/**
 * Calculate test coverage percentage
 * @param int $formulaId Formula ID
 * @return float Coverage percentage (0-100)
 */
function formula_builder_calculate_test_coverage($formulaId) {
    // This is a simplified coverage calculation
    // A more advanced implementation would analyze the formula code
    // and determine which branches/paths are covered by tests
    
    $stats = formula_builder_get_test_stats($formulaId);
    
    // Simple heuristic: more tests = better coverage
    // Real implementation would need code analysis
    if ($stats['total'] === 0) {
        return 0;
    }
    
    // Base coverage on number of tests and their diversity
    // This is a placeholder - real coverage analysis would be more complex
    $baseCoverage = min(100, $stats['total'] * 15);
    
    // Adjust based on pass rate (failing tests might indicate incomplete coverage)
    $passRateFactor = $stats['pass_rate'] / 100;
    $coverage = $baseCoverage * $passRateFactor;
    
    return round($coverage, 2);
}

