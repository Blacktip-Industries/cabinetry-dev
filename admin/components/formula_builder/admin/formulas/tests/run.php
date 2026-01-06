<?php
/**
 * Formula Builder Component - Run Tests
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/tests.php';
require_once __DIR__ . '/../../core/test_executor.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$testId = (int)($_GET['test_id'] ?? 0);
$runAll = isset($_GET['run_all']) && $_GET['run_all'] == '1';
$formula = null;
$results = null;

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        header('Location: ../index.php?error=notfound');
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}

// Run tests
if ($runAll) {
    $results = formula_builder_run_tests($formulaId);
} elseif ($testId) {
    $singleResult = formula_builder_run_test($testId);
    $results = [
        'success' => true,
        'total' => 1,
        'passed' => $singleResult['status'] === 'passed' ? 1 : 0,
        'failed' => $singleResult['status'] === 'failed' ? 1 : 0,
        'error' => $singleResult['status'] === 'error' ? 1 : 0,
        'results' => [$singleResult]
    ];
} else {
    header('Location: index.php?formula_id=' . $formulaId);
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Results - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .summary { display: flex; gap: 20px; margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px; }
        .summary-item { flex: 1; text-align: center; }
        .summary-value { font-size: 24px; font-weight: bold; }
        .summary-label { font-size: 12px; color: #666; }
        .summary.passed .summary-value { color: #28a745; }
        .summary.failed .summary-value { color: #dc3545; }
        .summary.error .summary-value { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .status-passed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-error { background: #f8d7da; color: #721c24; }
        .result-details { background: #f8f8f8; padding: 10px; border-radius: 4px; margin-top: 10px; }
        .result-details pre { margin: 5px 0; font-size: 11px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .error-message { color: #dc3545; font-weight: bold; }
        .match-indicator { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
        .match-indicator.match { background: #28a745; }
        .match-indicator.no-match { background: #dc3545; }
    </style>
</head>
<body>
    <h1>Test Results: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <a href="index.php?formula_id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Test Suite</a>
    
    <?php if ($results): ?>
        <div class="summary">
            <div class="summary-item">
                <div class="summary-value"><?php echo $results['total']; ?></div>
                <div class="summary-label">Total Tests</div>
            </div>
            <div class="summary-item passed">
                <div class="summary-value"><?php echo $results['passed']; ?></div>
                <div class="summary-label">Passed</div>
            </div>
            <div class="summary-item failed">
                <div class="summary-value"><?php echo $results['failed']; ?></div>
                <div class="summary-label">Failed</div>
            </div>
            <div class="summary-item error">
                <div class="summary-value"><?php echo $results['error']; ?></div>
                <div class="summary-label">Errors</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Test Name</th>
                    <th>Status</th>
                    <th>Expected</th>
                    <th>Actual</th>
                    <th>Match</th>
                    <th>Execution Time</th>
                    <th>Error</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $test = null;
                foreach ($results['results'] as $result): 
                    if ($testId) {
                        $test = formula_builder_get_test($testId);
                    } else {
                        $test = formula_builder_get_test($result['test_id']);
                    }
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($test['test_name']); ?></strong></td>
                        <td>
                            <span class="status-badge status-<?php echo $result['status']; ?>">
                                <?php echo strtoupper($result['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($result['expected_result'] !== null): ?>
                                <pre style="margin: 0; font-size: 11px; max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(json_encode($result['expected_result'], JSON_PRETTY_PRINT)); ?></pre>
                            <?php else: ?>
                                <em>No expected result</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($result['actual_result'] !== null): ?>
                                <pre style="margin: 0; font-size: 11px; max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(json_encode($result['actual_result'], JSON_PRETTY_PRINT)); ?></pre>
                            <?php else: ?>
                                <em>No result</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($result['match'])): ?>
                                <span class="match-indicator <?php echo $result['match'] ? 'match' : 'no-match'; ?>"></span>
                                <?php echo $result['match'] ? 'Match' : 'No Match'; ?>
                            <?php else: ?>
                                <em>N/A</em>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $result['execution_time_ms'] ?? '-'; ?> ms</td>
                        <td>
                            <?php if (!empty($result['error'])): ?>
                                <span class="error-message"><?php echo htmlspecialchars($result['error']); ?></span>
                            <?php else: ?>
                                <em>None</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?php echo $result['test_id']; ?>&formula_id=<?php echo $formulaId; ?>" class="btn">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No results available.</p>
    <?php endif; ?>
</body>
</html>

