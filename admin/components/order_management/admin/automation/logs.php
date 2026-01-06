<?php
/**
 * Order Management Component - Automation Execution Logs
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/automation.php';

// Check if installed
if (!order_management_is_installed()) {
    die('Order Management component is not installed. Please run the installer.');
}

$filters = [];
if (isset($_GET['rule_id'])) {
    $filters['rule_id'] = (int)$_GET['rule_id'];
}
if (isset($_GET['order_id'])) {
    $filters['order_id'] = (int)$_GET['order_id'];
}
if (isset($_GET['result'])) {
    $filters['execution_result'] = $_GET['result'];
}

$logs = order_management_get_automation_logs($filters, 100);

// Get rule names for display
$rules = order_management_get_automation_rules();
$ruleNames = [];
foreach ($rules as $rule) {
    $ruleNames[$rule['id']] = $rule['rule_name'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Logs - Order Management</title>
    <link rel="stylesheet" href="../../../assets/css/order_management.css">
    <style>
        body { font-family: sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .filters { background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .filters form { display: inline-block; margin-right: 20px; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .status-success { color: #28a745; }
        .status-failed { color: #dc3545; }
        .status-partial { color: #ffc107; }
    </style>
</head>
<body>
    <h1>Automation Execution Logs</h1>
    
    <div class="filters">
        <form method="GET">
            <label>Rule ID: <input type="number" name="rule_id" value="<?php echo $_GET['rule_id'] ?? ''; ?>" style="width: 100px;"></label>
            <label>Order ID: <input type="number" name="order_id" value="<?php echo $_GET['order_id'] ?? ''; ?>" style="width: 100px;"></label>
            <label>Result: 
                <select name="result">
                    <option value="">All</option>
                    <option value="success" <?php echo ($_GET['result'] ?? '') === 'success' ? 'selected' : ''; ?>>Success</option>
                    <option value="failed" <?php echo ($_GET['result'] ?? '') === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    <option value="partial" <?php echo ($_GET['result'] ?? '') === 'partial' ? 'selected' : ''; ?>>Partial</option>
                </select>
            </label>
            <button type="submit" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Filter</button>
            <a href="logs.php" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">Clear</a>
        </form>
    </div>
    
    <?php if (empty($logs)): ?>
        <p>No execution logs found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Rule</th>
                    <th>Order ID</th>
                    <th>Trigger Event</th>
                    <th>Result</th>
                    <th>Actions Executed</th>
                    <th>Error</th>
                    <th>Executed At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log['id']; ?></td>
                        <td><?php echo htmlspecialchars($ruleNames[$log['rule_id']] ?? 'Unknown'); ?></td>
                        <td><?php echo $log['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($log['trigger_event']); ?></td>
                        <td class="status-<?php echo $log['execution_result']; ?>">
                            <?php echo ucfirst($log['execution_result']); ?>
                        </td>
                        <td><?php echo count($log['actions_executed'] ?? []); ?></td>
                        <td><?php echo htmlspecialchars($log['error_message'] ?? '-'); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['executed_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <a href="index.php" class="btn" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Back to Rules</a>
    </div>
</body>
</html>

