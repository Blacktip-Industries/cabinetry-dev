<?php
/**
 * Layout Component - Test Suite Runner
 * Run all tests
 */

require_once __DIR__ . '/bootstrap.php';

echo "Layout Component - Test Suite\n";
echo "============================\n\n";

$testFiles = [
    'unit/test_element_templates.php',
    'unit/test_design_systems.php',
    'unit/test_versioning.php',
    'unit/test_export_import.php',
    'integration/test_template_creation_workflow.php',
    'integration/test_design_system_inheritance.php'
];

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$testResults = [];

foreach ($testFiles as $testFile) {
    $filePath = __DIR__ . '/' . $testFile;
    if (!file_exists($filePath)) {
        echo "⚠ Test file not found: {$testFile}\n";
        continue;
    }
    
    echo "Running: {$testFile}\n";
    
    try {
        ob_start();
        require $filePath;
        $output = ob_get_clean();
        
        if (isset($GLOBALS['test_count']) && isset($GLOBALS['test_passed'])) {
            $testCount = $GLOBALS['test_count'];
            $testPassed = $GLOBALS['test_passed'];
            
            $totalTests += $testCount;
            $passedTests += $testPassed;
            $failedTests += ($testCount - $testPassed);
            
            $testResults[] = [
                'file' => $testFile,
                'total' => $testCount,
                'passed' => $testPassed,
                'failed' => $testCount - $testPassed
            ];
            
            echo "   ✓ {$testPassed}/{$testCount} tests passed\n";
        } else {
            echo "   ⚠ No test results recorded\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
        $failedTests++;
    }
    
    echo "\n";
}

// Summary
echo str_repeat("=", 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Failed: {$failedTests}\n";
echo "Success Rate: " . ($totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0) . "%\n\n";

if ($failedTests === 0 && $totalTests > 0) {
    echo "✓ All tests passed!\n";
    exit(0);
} else {
    echo "✗ Some tests failed\n";
    exit(1);
}

