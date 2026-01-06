<?php
/**
 * Order Management Component - Workflows List
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/workflows.php';

// Check if installed
if (!order_management_is_installed()) {
    die('Order Management component is not installed. Please run the installer.');
}

// Get all workflows
$workflows = order_management_get_workflows();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Workflows - Order Management</title>
    <link rel="stylesheet" href="../../../assets/css/order_management.css">
    <style>
        body { font-family: sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .workflow-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .workflow-card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #fff; }
        .workflow-card h3 { margin-top: 0; }
        .workflow-card .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin: 5px 5px 5px 0; }
        .badge-default { background: #007bff; color: white; }
        .badge-active { background: #28a745; color: white; }
        .badge-inactive { background: #6c757d; color: white; }
        .actions { margin-top: 15px; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; margin-right: 10px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>
    <h1>Order Workflows</h1>
    
    <div style="margin-bottom: 20px;">
        <a href="create.php" class="btn btn-primary">Create New Workflow</a>
    </div>
    
    <?php if (empty($workflows)): ?>
        <p>No workflows found. <a href="create.php">Create your first workflow</a>.</p>
    <?php else: ?>
        <div class="workflow-list">
            <?php foreach ($workflows as $workflow): ?>
                <div class="workflow-card">
                    <h3><?php echo htmlspecialchars($workflow['workflow_name']); ?></h3>
                    <p><?php echo htmlspecialchars($workflow['description'] ?? 'No description'); ?></p>
                    
                    <div>
                        <?php if ($workflow['is_default']): ?>
                            <span class="badge badge-default">Default</span>
                        <?php endif; ?>
                        <?php if ($workflow['is_active']): ?>
                            <span class="badge badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge badge-inactive">Inactive</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="actions">
                        <a href="edit.php?id=<?php echo $workflow['id']; ?>" class="btn btn-secondary">Edit</a>
                        <a href="assign.php?id=<?php echo $workflow['id']; ?>" class="btn btn-secondary">Assign</a>
                        <?php if (!$workflow['is_default']): ?>
                            <a href="delete.php?id=<?php echo $workflow['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>

