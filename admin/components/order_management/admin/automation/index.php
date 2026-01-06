<?php
/**
 * Order Management Component - Automation Rules List
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/automation.php';

// Check if installed
if (!order_management_is_installed()) {
    die('Order Management component is not installed. Please run the installer.');
}

// Get all automation rules
$rules = order_management_get_automation_rules();

// Get execution stats
$conn = order_management_get_db_connection();
$logsTable = order_management_get_table_name('automation_logs');
$stats = [];

foreach ($rules as &$rule) {
    // Get execution count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$logsTable} WHERE rule_id = ?");
    $stmt->bind_param("i", $rule['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $rule['execution_count'] = $row['count'] ?? 0;
    $stmt->close();
    
    // Get success rate
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN execution_result = 'success' THEN 1 ELSE 0 END) as success FROM {$logsTable} WHERE rule_id = ?");
    $stmt->bind_param("i", $rule['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $total = $row['total'] ?? 0;
    $success = $row['success'] ?? 0;
    $rule['success_rate'] = $total > 0 ? round(($success / $total) * 100, 1) : 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Rules - Order Management</title>
    <link rel="stylesheet" href="../../../assets/css/order_management.css">
    <style>
        body { font-family: sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .rule-list { display: grid; gap: 20px; margin-top: 20px; }
        .rule-card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #fff; }
        .rule-card h3 { margin-top: 0; }
        .rule-card .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin: 5px 5px 5px 0; }
        .badge-active { background: #28a745; color: white; }
        .badge-inactive { background: #6c757d; color: white; }
        .badge-priority { background: #007bff; color: white; }
        .stats { display: flex; gap: 20px; margin: 10px 0; }
        .stat-item { padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .stat-item strong { display: block; font-size: 20px; }
        .actions { margin-top: 15px; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; margin-right: 10px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>
    <h1>Automation Rules</h1>
    
    <div style="margin-bottom: 20px;">
        <a href="create.php" class="btn btn-primary">Create New Rule</a>
        <a href="logs.php" class="btn btn-secondary">View Execution Logs</a>
    </div>
    
    <?php if (empty($rules)): ?>
        <p>No automation rules found. <a href="create.php">Create your first automation rule</a>.</p>
    <?php else: ?>
        <div class="rule-list">
            <?php foreach ($rules as $rule): ?>
                <div class="rule-card">
                    <h3><?php echo htmlspecialchars($rule['rule_name']); ?></h3>
                    <p><?php echo htmlspecialchars($rule['description'] ?? 'No description'); ?></p>
                    
                    <div>
                        <?php if ($rule['is_active']): ?>
                            <span class="badge badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge badge-inactive">Inactive</span>
                        <?php endif; ?>
                        <span class="badge badge-priority">Priority: <?php echo $rule['priority']; ?></span>
                    </div>
                    
                    <div class="stats">
                        <div class="stat-item">
                            <strong><?php echo $rule['execution_count']; ?></strong>
                            <span>Executions</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo $rule['success_rate']; ?>%</strong>
                            <span>Success Rate</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo count($rule['trigger_conditions'] ?? []); ?></strong>
                            <span>Triggers</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo count($rule['actions'] ?? []); ?></strong>
                            <span>Actions</span>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <a href="edit.php?id=<?php echo $rule['id']; ?>" class="btn btn-secondary">Edit</a>
                        <a href="test.php?id=<?php echo $rule['id']; ?>" class="btn btn-secondary">Test</a>
                        <a href="logs.php?rule_id=<?php echo $rule['id']; ?>" class="btn btn-secondary">View Logs</a>
                        <a href="delete.php?id=<?php echo $rule['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>

