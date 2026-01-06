<?php
/**
 * Formula Builder Component - Monitoring & Alerts
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/monitoring.php';

$errors = [];
$success = false;
$alertRules = [];
$alerts = formula_builder_get_alerts(['limit' => 50]);

// Get alert rules
$conn = formula_builder_get_db_connection();
if ($conn) {
    $tableName = formula_builder_get_table_name('alert_rules');
    $result = $conn->query("SELECT * FROM {$tableName} ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $row['alert_channels'] = json_decode($row['alert_channels'], true) ?: [];
        $alertRules[] = $row;
    }
}

// Handle create alert rule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_rule'])) {
    $ruleName = trim($_POST['rule_name'] ?? '');
    $metricType = $_POST['metric_type'] ?? '';
    $thresholdValue = (float)($_POST['threshold_value'] ?? 0);
    $comparisonOperator = $_POST['comparison_operator'] ?? '>';
    $alertChannels = isset($_POST['alert_channels']) ? $_POST['alert_channels'] : [];
    
    if (empty($ruleName) || empty($metricType)) {
        $errors[] = 'Rule name and metric type are required';
    } else {
        $result = formula_builder_create_alert_rule($ruleName, $metricType, $thresholdValue, $comparisonOperator, $alertChannels);
        if ($result['success']) {
            header('Location: index.php?created=1');
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to create alert rule';
        }
    }
}

// Handle check alerts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_alerts'])) {
    $result = formula_builder_check_alerts();
    if ($result['success']) {
        $success = true;
        $alerts = formula_builder_get_alerts(['limit' => 50]); // Refresh
    }
}

$metricTypes = [
    'execution_time' => 'Average Execution Time (ms)',
    'error_rate' => 'Error Rate (%)',
    'test_failure_rate' => 'Test Failure Rate (%)',
    'quality_score' => 'Quality Score'
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Monitoring & Alerts - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-secondary { background: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .alert-critical { background: #f8d7da; color: #721c24; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-warning { background: #fff3cd; color: #856404; }
        .alert-info { background: #d1ecf1; color: #0c5460; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        .checkbox-group { display: flex; gap: 10px; }
        .checkbox-group label { display: flex; align-items: center; font-weight: normal; }
        .checkbox-group input[type="checkbox"] { width: auto; margin-right: 5px; }
    </style>
</head>
<body>
    <h1>Monitoring & Alerts</h1>
    <a href="../formulas/index.php" class="btn btn-secondary">Back to Formulas</a>
    <form method="POST" style="display: inline;">
        <button type="submit" name="check_alerts" class="btn btn-success">Check Alerts Now</button>
    </form>
    
    <?php if ($success): ?>
        <div style="background: #d4edda; padding: 15px; border-radius: 4px; margin: 20px 0;">
            Alerts checked successfully
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px;">
        <h2>Create Alert Rule</h2>
        <form method="POST">
            <div class="form-group">
                <label for="rule_name">Rule Name *</label>
                <input type="text" id="rule_name" name="rule_name" required>
            </div>
            <div class="form-group">
                <label for="metric_type">Metric Type *</label>
                <select id="metric_type" name="metric_type" required>
                    <option value="">Select metric...</option>
                    <?php foreach ($metricTypes as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="comparison_operator">Comparison *</label>
                <select id="comparison_operator" name="comparison_operator" required>
                    <option value=">">Greater than (>)</option>
                    <option value="<">Less than (<)</option>
                    <option value=">=">Greater than or equal (>=)</option>
                    <option value="<=">Less than or equal (<=)</option>
                    <option value="==">Equal (==)</option>
                    <option value="!=">Not equal (!=)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="threshold_value">Threshold Value *</label>
                <input type="number" id="threshold_value" name="threshold_value" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Alert Channels</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="alert_channels[]" value="email"> Email</label>
                    <label><input type="checkbox" name="alert_channels[]" value="in_app"> In-App</label>
                    <label><input type="checkbox" name="alert_channels[]" value="sms"> SMS</label>
                    <label><input type="checkbox" name="alert_channels[]" value="push"> Push</label>
                </div>
            </div>
            <button type="submit" name="create_rule" class="btn">Create Alert Rule</button>
        </form>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Alert Rules (<?php echo count($alertRules); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Rule Name</th>
                    <th>Metric</th>
                    <th>Condition</th>
                    <th>Channels</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alertRules)): ?>
                    <tr>
                        <td colspan="5">No alert rules found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($alertRules as $rule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rule['rule_name']); ?></td>
                            <td><?php echo htmlspecialchars($metricTypes[$rule['metric_type']] ?? $rule['metric_type']); ?></td>
                            <td><?php echo htmlspecialchars($rule['comparison_operator']); ?> <?php echo $rule['threshold_value']; ?></td>
                            <td><?php echo implode(', ', $rule['alert_channels']); ?></td>
                            <td><?php echo $rule['is_active'] ? 'Active' : 'Inactive'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Recent Alerts (<?php echo count($alerts); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Message</th>
                    <th>Formula ID</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alerts)): ?>
                    <tr>
                        <td colspan="5">No alerts found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($alerts as $alert): ?>
                        <tr class="alert-<?php echo $alert['alert_level']; ?>">
                            <td><?php echo strtoupper($alert['alert_level']); ?></td>
                            <td><?php echo htmlspecialchars($alert['message']); ?></td>
                            <td><?php echo $alert['formula_id'] ?? 'N/A'; ?></td>
                            <td><?php echo $alert['resolved'] ? 'Resolved' : 'Active'; ?></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($alert['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

