<?php
/**
 * Order Management Component - Edit Workflow
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/workflows.php';

// Check if installed
if (!order_management_is_installed()) {
    die('Order Management component is not installed. Please run the installer.');
}

$workflowId = $_GET['id'] ?? 0;
$workflow = order_management_get_workflow($workflowId);

if (!$workflow) {
    die('Workflow not found.');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'workflow_name' => $_POST['workflow_name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'is_default' => isset($_POST['is_default']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (empty($data['workflow_name'])) {
        $errors[] = 'Workflow name is required';
    }
    
    if (empty($errors)) {
        $result = order_management_update_workflow($workflowId, $data);
        if ($result['success']) {
            $success = true;
            $workflow = order_management_get_workflow($workflowId); // Refresh
        } else {
            $errors[] = $result['error'] ?? 'Failed to update workflow';
        }
    }
}

$steps = order_management_get_workflow_steps($workflowId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Workflow - Order Management</title>
    <link rel="stylesheet" href="../../../assets/css/order_management.css">
    <style>
        body { font-family: sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { min-height: 100px; }
        .error { color: #dc3545; margin-top: 5px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; margin-right: 10px; }
        .btn:hover { opacity: 0.8; }
        .btn-secondary { background: #6c757d; }
        .steps-section { margin-top: 30px; border-top: 2px solid #ddd; padding-top: 20px; }
        .step-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Edit Workflow: <?php echo htmlspecialchars($workflow['workflow_name']); ?></h1>
    
    <?php if ($success): ?>
        <div class="success">Workflow updated successfully!</div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="workflow_name">Workflow Name *</label>
            <input type="text" id="workflow_name" name="workflow_name" required value="<?php echo htmlspecialchars($workflow['workflow_name']); ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($workflow['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="is_default" value="1" <?php echo $workflow['is_default'] ? 'checked' : ''; ?>>
                Set as default workflow
            </label>
        </div>
        
        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo $workflow['is_active'] ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit" class="btn">Update Workflow</button>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </form>
    
    <div class="steps-section">
        <h2>Workflow Steps</h2>
        <a href="step-create.php?workflow_id=<?php echo $workflowId; ?>" class="btn">Add Step</a>
        
        <?php if (empty($steps)): ?>
            <p>No steps defined. <a href="step-create.php?workflow_id=<?php echo $workflowId; ?>">Add your first step</a>.</p>
        <?php else: ?>
            <?php foreach ($steps as $step): ?>
                <div class="step-item">
                    <strong>Step <?php echo $step['step_order']; ?>: <?php echo htmlspecialchars($step['status_name']); ?></strong>
                    <?php if ($step['requires_approval']): ?>
                        <span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 10px;">Requires Approval</span>
                    <?php endif; ?>
                    <div style="margin-top: 10px;">
                        <a href="step-edit.php?id=<?php echo $step['id']; ?>" class="btn btn-secondary" style="font-size: 14px; padding: 5px 10px;">Edit</a>
                        <a href="step-delete.php?id=<?php echo $step['id']; ?>" class="btn" style="background: #dc3545; font-size: 14px; padding: 5px 10px;" onclick="return confirm('Are you sure?')">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

