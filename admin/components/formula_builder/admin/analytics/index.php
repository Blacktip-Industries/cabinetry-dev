<?php
/**
 * Formula Builder Component - Analytics Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/analytics.php';

$formulaId = isset($_GET['formula_id']) ? (int)$_GET['formula_id'] : null;
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$formula = null;
if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
}

$executionStats = formula_builder_get_execution_stats($formulaId, $dateFrom, $dateTo);
$performanceMetrics = formula_builder_get_performance_metrics($formulaId);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Analytics Dashboard - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #f5f5f5; padding: 20px; border-radius: 4px; text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; color: #007bff; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
        .filters { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Analytics Dashboard</h1>
    <a href="../formulas/index.php" class="btn btn-secondary">Back to Formulas</a>
    
    <div class="filters">
        <form method="GET" style="display: flex; gap: 15px; align-items: end;">
            <div>
                <label>Formula ID (optional):</label>
                <input type="number" name="formula_id" value="<?php echo $formulaId; ?>" style="padding: 5px; width: 100px;">
            </div>
            <div>
                <label>From Date:</label>
                <input type="date" name="date_from" value="<?php echo $dateFrom; ?>" style="padding: 5px;">
            </div>
            <div>
                <label>To Date:</label>
                <input type="date" name="date_to" value="<?php echo $dateTo; ?>" style="padding: 5px;">
            </div>
            <div>
                <button type="submit" class="btn">Filter</button>
            </div>
        </form>
    </div>
    
    <?php if ($formula): ?>
        <h2>Formula: <?php echo htmlspecialchars($formula['formula_name']); ?></h2>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $executionStats['total_executions']; ?></div>
            <div class="stat-label">Total Executions</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #28a745;"><?php echo $executionStats['successful_executions']; ?></div>
            <div class="stat-label">Successful</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #dc3545;"><?php echo $executionStats['failed_executions']; ?></div>
            <div class="stat-label">Failed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo round($executionStats['average_execution_time'], 2); ?> ms</div>
            <div class="stat-label">Avg Execution Time</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo round($performanceMetrics['average_response_time'], 2); ?> ms</div>
            <div class="stat-label">Avg Response Time</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo round($performanceMetrics['error_rate'], 2); ?>%</div>
            <div class="stat-label">Error Rate</div>
        </div>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Performance Metrics</h2>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Average Response Time</td>
                    <td><?php echo round($performanceMetrics['average_response_time'], 2); ?> ms</td>
                </tr>
                <tr>
                    <td>P95 Response Time</td>
                    <td><?php echo round($performanceMetrics['p95_response_time'], 2); ?> ms</td>
                </tr>
                <tr>
                    <td>P99 Response Time</td>
                    <td><?php echo round($performanceMetrics['p99_response_time'], 2); ?> ms</td>
                </tr>
                <tr>
                    <td>Error Rate</td>
                    <td><?php echo round($performanceMetrics['error_rate'], 2); ?>%</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Execution Statistics</h2>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Executions</td>
                    <td><?php echo $executionStats['total_executions']; ?></td>
                </tr>
                <tr>
                    <td>Successful Executions</td>
                    <td><?php echo $executionStats['successful_executions']; ?></td>
                </tr>
                <tr>
                    <td>Failed Executions</td>
                    <td><?php echo $executionStats['failed_executions']; ?></td>
                </tr>
                <tr>
                    <td>Average Execution Time</td>
                    <td><?php echo round($executionStats['average_execution_time'], 2); ?> ms</td>
                </tr>
                <tr>
                    <td>Min Execution Time</td>
                    <td><?php echo $executionStats['min_execution_time'] ? round($executionStats['min_execution_time'], 2) . ' ms' : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td>Max Execution Time</td>
                    <td><?php echo $executionStats['max_execution_time'] ? round($executionStats['max_execution_time'], 2) . ' ms' : 'N/A'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>

