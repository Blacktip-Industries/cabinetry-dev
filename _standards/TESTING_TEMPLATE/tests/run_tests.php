<?php
/**
 * {COMPONENT_NAME} Component - Test Suite Runner
 * Comprehensive test runner with auto-discovery, parallel execution, and reporting
 * 
 * Usage:
 *   php run_tests.php                    # Run all tests
 *   php run_tests.php --filter=unit       # Run only unit tests
 *   php run_tests.php --workers=4         # Run with 4 parallel workers
 *   php run_tests.php --format=json       # Output JSON format
 *   php run_tests.php --watch             # Watch mode
 */

require_once __DIR__ . '/bootstrap.php';

// Parse command line arguments
$options = parse_command_line_args($argv ?? []);

// Component name
$componentName = get_component_name();
echo "{$componentName} Component - Test Suite\n";
echo str_repeat("=", 60) . "\n\n";

// Auto-discover test files
$testFiles = auto_discover_tests($options['filter'] ?? null);

if (empty($testFiles)) {
    echo "No tests found.\n";
    exit(0);
}

echo "Found " . count($testFiles) . " test file(s)\n\n";

// Run tests
if ($options['watch'] ?? false) {
    // Watch mode
    run_watch_mode($testFiles, $options);
} elseif ($options['workers'] > 1) {
    // Parallel execution
    $results = run_tests_parallel($testFiles, $options);
} else {
    // Sequential execution
    $results = run_tests_sequential($testFiles, $options);
}

// Generate reports
generate_reports($results, $options);

// Exit with appropriate code
exit($results['failed'] > 0 ? 1 : 0);

// ============================================================================
// FUNCTIONS
// ============================================================================

/**
 * Parse command line arguments
 */
function parse_command_line_args($argv) {
    $options = [
        'filter' => null,
        'workers' => 1,
        'format' => 'console',
        'watch' => false,
        'verbose' => false,
        'coverage' => false
    ];
    
    foreach ($argv as $arg) {
        if (strpos($arg, '--filter=') === 0) {
            $options['filter'] = substr($arg, 9);
        } elseif (strpos($arg, '--workers=') === 0) {
            $options['workers'] = (int)substr($arg, 10);
        } elseif (strpos($arg, '--format=') === 0) {
            $options['format'] = substr($arg, 9);
        } elseif ($arg === '--watch' || $arg === '-w') {
            $options['watch'] = true;
        } elseif ($arg === '--verbose' || $arg === '-v') {
            $options['verbose'] = true;
        } elseif ($arg === '--coverage') {
            $options['coverage'] = true;
        }
    }
    
    return $options;
}

/**
 * Auto-discover test files
 */
function auto_discover_tests($filter = null) {
    $testFiles = [];
    $testDirs = ['unit', 'integration', 'functional', 'performance'];
    
    foreach ($testDirs as $dir) {
        $dirPath = __DIR__ . '/' . $dir;
        if (!is_dir($dirPath)) {
            continue;
        }
        
        // Apply filter
        if ($filter && $dir !== $filter) {
            continue;
        }
        
        // Find all test_*.php files
        $files = glob($dirPath . '/test_*.php');
        foreach ($files as $file) {
            $testFiles[] = [
                'path' => $file,
                'type' => $dir,
                'name' => basename($file, '.php')
            ];
        }
    }
    
    return $testFiles;
}

/**
 * Run tests sequentially
 */
function run_tests_sequential($testFiles, $options) {
    $results = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'tests' => []
    ];
    
    foreach ($testFiles as $testFile) {
        echo "Running: {$testFile['type']}/{$testFile['name']}\n";
        
        $testResult = run_test_file($testFile['path']);
        
        $results['total'] += $testResult['total'];
        $results['passed'] += $testResult['passed'];
        $results['failed'] += $testResult['failed'];
        $results['tests'][] = array_merge($testFile, $testResult);
        
        if ($options['verbose']) {
            echo "   ✓ {$testResult['passed']}/{$testResult['total']} tests passed\n";
        }
    }
    
    return $results;
}

/**
 * Run tests in parallel
 */
function run_tests_parallel($testFiles, $options) {
    $workers = $options['workers'];
    $results = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'tests' => []
    ];
    
    echo "Running tests with {$workers} parallel workers...\n\n";
    
    // Split tests into chunks for each worker
    $chunks = array_chunk($testFiles, ceil(count($testFiles) / $workers));
    $processes = [];
    
    foreach ($chunks as $chunkIndex => $chunk) {
        $processId = $chunkIndex + 1;
        $process = start_test_process($chunk, $processId, $options);
        $processes[] = $process;
    }
    
    // Wait for all processes and collect results
    foreach ($processes as $process) {
        $processResults = wait_for_process($process);
        $results['total'] += $processResults['total'];
        $results['passed'] += $processResults['passed'];
        $results['failed'] += $processResults['failed'];
        $results['tests'] = array_merge($results['tests'], $processResults['tests']);
    }
    
    return $results;
}

/**
 * Run a single test file
 */
function run_test_file($filePath) {
    // Reset test counters
    $GLOBALS['test_count'] = 0;
    $GLOBALS['test_passed'] = 0;
    $GLOBALS['test_failed'] = 0;
    
    $startTime = microtime(true);
    $errors = [];
    
    try {
        ob_start();
        require $filePath;
        $output = ob_get_clean();
        
        $result = [
            'total' => $GLOBALS['test_count'] ?? 0,
            'passed' => $GLOBALS['test_passed'] ?? 0,
            'failed' => $GLOBALS['test_failed'] ?? 0,
            'output' => $output,
            'duration' => microtime(true) - $startTime,
            'errors' => $errors
        ];
    } catch (Exception $e) {
        $result = [
            'total' => 1,
            'passed' => 0,
            'failed' => 1,
            'output' => '',
            'duration' => microtime(true) - $startTime,
            'errors' => [$e->getMessage()]
        ];
    }
    
    return $result;
}

/**
 * Start a test process (for parallel execution)
 */
function start_test_process($testFiles, $processId, $options) {
    // Create temporary script for this process
    $script = create_test_script($testFiles, $processId, $options);
    $scriptPath = sys_get_temp_dir() . '/test_process_' . $processId . '_' . getmypid() . '.php';
    file_put_contents($scriptPath, $script);
    
    // Start process
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    
    $process = proc_open("php {$scriptPath}", $descriptorspec, $pipes);
    
    return [
        'process' => $process,
        'pipes' => $pipes,
        'script' => $scriptPath
    ];
}

/**
 * Wait for process to complete
 */
function wait_for_process($processInfo) {
    $status = proc_get_status($processInfo['process']);
    while ($status['running']) {
        usleep(100000); // 100ms
        $status = proc_get_status($processInfo['process']);
    }
    
    // Read output
    $output = stream_get_contents($processInfo['pipes'][1]);
    $errors = stream_get_contents($processInfo['pipes'][2]);
    
    // Close pipes
    foreach ($processInfo['pipes'] as $pipe) {
        fclose($pipe);
    }
    
    // Close process
    proc_close($processInfo['process']);
    
    // Cleanup script
    if (file_exists($processInfo['script'])) {
        unlink($processInfo['script']);
    }
    
    // Parse output (JSON format)
    $results = json_decode($output, true) ?? [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'tests' => []
    ];
    
    return $results;
}

/**
 * Create test script for parallel execution
 */
function create_test_script($testFiles, $processId, $options) {
    $filesJson = json_encode($testFiles);
    return <<<PHP
<?php
require_once __DIR__ . '/../../bootstrap.php';
\$testFiles = json_decode('{$filesJson}', true);
\$results = ['total' => 0, 'passed' => 0, 'failed' => 0, 'tests' => []];
foreach (\$testFiles as \$testFile) {
    \$result = run_test_file(\$testFile['path']);
    \$results['total'] += \$result['total'];
    \$results['passed'] += \$result['passed'];
    \$results['failed'] += \$result['failed'];
    \$results['tests'][] = array_merge(\$testFile, \$result);
}
echo json_encode(\$results);
PHP;
}

/**
 * Run watch mode
 */
function run_watch_mode($testFiles, $options) {
    require_once __DIR__ . '/watch/watcher.php';
    start_watcher($testFiles, $options);
}

/**
 * Generate reports
 */
function generate_reports($results, $options) {
    $format = $options['format'] ?? 'console';
    
    switch ($format) {
        case 'json':
            generate_json_report($results);
            break;
        case 'html':
            generate_html_report($results);
            break;
        case 'xml':
            generate_xml_report($results);
            break;
        case 'junit':
            generate_junit_report($results);
            break;
        case 'markdown':
            generate_markdown_report($results);
            break;
        default:
            generate_console_report($results);
    }
}

/**
 * Generate console report
 */
function generate_console_report($results) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "TEST SUMMARY\n";
    echo str_repeat("=", 60) . "\n";
    echo "Total Tests: {$results['total']}\n";
    echo "Passed: {$results['passed']}\n";
    echo "Failed: {$results['failed']}\n";
    
    if ($results['total'] > 0) {
        $successRate = round(($results['passed'] / $results['total']) * 100, 2);
        echo "Success Rate: {$successRate}%\n";
    }
    
    echo "\n";
    
    if ($results['failed'] === 0 && $results['total'] > 0) {
        echo "✓ All tests passed!\n";
    } else {
        echo "✗ Some tests failed\n";
    }
}

/**
 * Generate JSON report
 */
function generate_json_report($results) {
    echo json_encode($results, JSON_PRETTY_PRINT);
}

/**
 * Generate HTML report (placeholder - full implementation needed)
 */
function generate_html_report($results) {
    // TODO: Implement HTML report generation
    generate_console_report($results);
}

/**
 * Generate XML report (placeholder)
 */
function generate_xml_report($results) {
    // TODO: Implement XML report generation
    generate_console_report($results);
}

/**
 * Generate JUnit XML report (placeholder)
 */
function generate_junit_report($results) {
    // TODO: Implement JUnit XML report generation
    generate_console_report($results);
}

/**
 * Generate Markdown report (placeholder)
 */
function generate_markdown_report($results) {
    // TODO: Implement Markdown report generation
    generate_console_report($results);
}

