<?php
/**
 * Formula Builder Component - View Test Details
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/tests.php';
require_once __DIR__ . '/../../core/test_executor.php';

$testId = (int)($_GET['id'] ?? 0);
$formulaId = (int)($_GET['formula_id'] ?? 0);
$test = null;
$formula = null;
$comparison = null;

if ($testId) {
    $test = formula_builder_get_test($testId);
    if (!$test) {
        header('Location: index.php?formula_id=' . $formulaId . '&error=notfound');
        exit;
    }
    
    if ($formulaId) {
        $formula = formula_builder_get_formula_by_id($formulaId);
    } else {
        $formula = formula_builder_get_formula_by_id($test['formula_id']);
        $formulaId = $formula['id'];
    }
    
    // Compare results if both exist
    if ($test['expected_result'] !== null && $test['actual_result'] !== null) {
        $comparison = formula_builder_compare_results($test['expected_result'], $test['actual_result']);
    }
} else {
    header('Location: index.php');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Details - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .test-info { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .result-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .result-section h3 { margin-top: 0; }
        .code-block { background: #f8f8f8; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 10px 0; }
        .code-block pre { margin: 0; font-family: monospace; white-space: pre-wrap; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .status-passed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .comparison { background: #d1ecf1; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .comparison.match { background: #d4edda; }
        .comparison.no-match { background: #f8d7da; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>
    <h1>Test Details: <?php echo htmlspecialchars($test['test_name']); ?></h1>
    <div>
        <a href="index.php?formula_id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Test Suite</a>
        <a href="edit.php?id=<?php echo $testId; ?>&formula_id=<?php echo $formulaId; ?>" class="btn">Edit</a>
        <a href="run.php?test_id=<?php echo $testId; ?>&formula_id=<?php echo $formulaId; ?>" class="btn btn-success">Run Test</a>
    </div>
    
    <div class="test-info">
        <p><strong>Status:</strong> <span class="status-badge status-<?php echo $test['status']; ?>"><?php echo strtoupper($test['status']); ?></span></p>
        <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($test['created_at'])); ?></p>
        <?php if ($test['last_run_at']): ?>
            <p><strong>Last Run:</strong> <?php echo date('Y-m-d H:i:s', strtotime($test['last_run_at'])); ?></p>
        <?php endif; ?>
        <?php if ($test['execution_time_ms']): ?>
            <p><strong>Execution Time:</strong> <?php echo $test['execution_time_ms']; ?> ms</p>
        <?php endif; ?>
    </div>
    
    <div class="result-section">
        <h3>Input Data</h3>
        <div class="code-block">
            <pre><?php echo htmlspecialchars(json_encode($test['input_data'], JSON_PRETTY_PRINT)); ?></pre>
        </div>
    </div>
    
    <?php if ($test['expected_result'] !== null): ?>
        <div class="result-section">
            <h3>Expected Result</h3>
            <div class="code-block">
                <pre><?php echo htmlspecialchars(json_encode($test['expected_result'], JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($test['actual_result'] !== null): ?>
        <div class="result-section">
            <h3>Actual Result</h3>
            <div class="code-block">
                <pre><?php echo htmlspecialchars(json_encode($test['actual_result'], JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($comparison): ?>
        <div class="comparison <?php echo $comparison['match'] ? 'match' : 'no-match'; ?>">
            <h3>Comparison Result</h3>
            <p><strong>Match:</strong> <?php echo $comparison['match'] ? 'Yes ✓' : 'No ✗'; ?></p>
            <p><strong>Reason:</strong> <?php echo htmlspecialchars($comparison['reason']); ?></p>
            <?php if (isset($comparison['difference'])): ?>
                <p><strong>Difference:</strong> <?php echo $comparison['difference']; ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($test['status'] === 'error' && $test['actual_result'] === null): ?>
        <div class="result-section" style="background: #f8d7da; color: #721c24;">
            <h3>Error</h3>
            <p>This test encountered an error during execution. Check the formula code and input data.</p>
        </div>
    <?php endif; ?>
    
    <div class="result-section">
        <h3>Formula Code</h3>
        <div class="code-block">
            <pre><?php echo htmlspecialchars($formula['formula_code']); ?></pre>
        </div>
    </div>
</body>
</html>

