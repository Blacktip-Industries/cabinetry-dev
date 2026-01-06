<?php
/**
 * Formula Builder Component - Test Suite List
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/tests.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;
$tests = [];
$stats = [];
$statusFilter = $_GET['status'] ?? '';

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        header('Location: ../index.php?error=notfound');
        exit;
    }
    
    $filters = [];
    if ($statusFilter) {
        $filters['status'] = $statusFilter;
    }
    $filters['sort_by'] = $_GET['sort_by'] ?? 'created_at';
    $filters['sort_order'] = $_GET['sort_order'] ?? 'DESC';
    
    $tests = formula_builder_get_tests($formulaId, $filters);
    $stats = formula_builder_get_test_stats($formulaId);
} else {
    header('Location: ../index.php');
    exit;
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_test'])) {
    $testId = (int)$_POST['test_id'];
    formula_builder_delete_test($testId);
    header('Location: index.php?formula_id=' . $formulaId);
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Suite - <?php echo htmlspecialchars($formula['formula_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .header { margin-bottom: 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px; }
        .stat { flex: 1; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; }
        .stat-label { font-size: 12px; color: #666; }
        .stat.passed .stat-value { color: #28a745; }
        .stat.failed .stat-value { color: #dc3545; }
        .stat.pending .stat-value { color: #ffc107; }
        .stat.error .stat-value { color: #dc3545; }
        .filters { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .status-passed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 2px; border: none; cursor: pointer; font-size: 12px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-secondary { background: #6c757d; }
        .btn-warning { background: #ffc107; color: #000; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Test Suite: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
        <div>
            <a href="../edit.php?id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Formula</a>
            <a href="create.php?formula_id=<?php echo $formulaId; ?>" class="btn btn-success">Create Test</a>
            <a href="run.php?formula_id=<?php echo $formulaId; ?>&run_all=1" class="btn btn-warning">Run All Tests</a>
        </div>
    </div>
    
    <div class="stats">
        <div class="stat">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Tests</div>
        </div>
        <div class="stat passed">
            <div class="stat-value"><?php echo $stats['passed']; ?></div>
            <div class="stat-label">Passed</div>
        </div>
        <div class="stat failed">
            <div class="stat-value"><?php echo $stats['failed']; ?></div>
            <div class="stat-label">Failed</div>
        </div>
        <div class="stat pending">
            <div class="stat-value"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat error">
            <div class="stat-value"><?php echo $stats['error']; ?></div>
            <div class="stat-label">Errors</div>
        </div>
        <div class="stat">
            <div class="stat-value"><?php echo $stats['pass_rate']; ?>%</div>
            <div class="stat-label">Pass Rate</div>
        </div>
        <div class="stat">
            <div class="stat-value"><?php echo $stats['coverage']; ?>%</div>
            <div class="stat-label">Coverage</div>
        </div>
    </div>
    
    <div class="filters">
        <form method="GET" style="display: inline-block;">
            <input type="hidden" name="formula_id" value="<?php echo $formulaId; ?>">
            <label>Filter by status:
                <select name="status" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="passed" <?php echo $statusFilter === 'passed' ? 'selected' : ''; ?>>Passed</option>
                    <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="error" <?php echo $statusFilter === 'error' ? 'selected' : ''; ?>>Error</option>
                </select>
            </label>
            <label>Sort by:
                <select name="sort_by" onchange="this.form.submit()">
                    <option value="created_at" <?php echo ($_GET['sort_by'] ?? 'created_at') === 'created_at' ? 'selected' : ''; ?>>Created Date</option>
                    <option value="test_name" <?php echo ($_GET['sort_by'] ?? '') === 'test_name' ? 'selected' : ''; ?>>Name</option>
                    <option value="last_run_at" <?php echo ($_GET['sort_by'] ?? '') === 'last_run_at' ? 'selected' : ''; ?>>Last Run</option>
                    <option value="status" <?php echo ($_GET['sort_by'] ?? '') === 'status' ? 'selected' : ''; ?>>Status</option>
                </select>
            </label>
        </form>
    </div>
    
    <?php if (empty($tests)): ?>
        <p>No tests found. <a href="create.php?formula_id=<?php echo $formulaId; ?>">Create your first test</a></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Test Name</th>
                    <th>Status</th>
                    <th>Input Data</th>
                    <th>Expected Result</th>
                    <th>Actual Result</th>
                    <th>Execution Time</th>
                    <th>Last Run</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($test['test_name']); ?></strong></td>
                        <td>
                            <span class="status-badge status-<?php echo $test['status']; ?>">
                                <?php echo strtoupper($test['status']); ?>
                            </span>
                        </td>
                        <td>
                            <pre style="margin: 0; font-size: 11px; max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(json_encode($test['input_data'], JSON_PRETTY_PRINT)); ?></pre>
                        </td>
                        <td>
                            <?php if ($test['expected_result'] !== null): ?>
                                <pre style="margin: 0; font-size: 11px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(json_encode($test['expected_result'], JSON_PRETTY_PRINT)); ?></pre>
                            <?php else: ?>
                                <em>No expected result</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($test['actual_result'] !== null): ?>
                                <pre style="margin: 0; font-size: 11px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(json_encode($test['actual_result'], JSON_PRETTY_PRINT)); ?></pre>
                            <?php else: ?>
                                <em>Not run</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $test['execution_time_ms'] ? $test['execution_time_ms'] . ' ms' : '-'; ?>
                        </td>
                        <td>
                            <?php echo $test['last_run_at'] ? date('Y-m-d H:i:s', strtotime($test['last_run_at'])) : 'Never'; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?php echo $test['id']; ?>&formula_id=<?php echo $formulaId; ?>" class="btn">View</a>
                            <a href="edit.php?id=<?php echo $test['id']; ?>&formula_id=<?php echo $formulaId; ?>" class="btn">Edit</a>
                            <a href="run.php?test_id=<?php echo $test['id']; ?>&formula_id=<?php echo $formulaId; ?>" class="btn btn-success">Run</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this test?');">
                                <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                <button type="submit" name="delete_test" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>

