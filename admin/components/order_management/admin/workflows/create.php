<?php
/**
 * Order Management Component - Create Workflow
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/workflows.php';

// Check if installed
if (!order_management_is_installed()) {
    die('Order Management component is not installed. Please run the installer.');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'workflow_name' => $_POST['workflow_name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'trigger_conditions' => !empty($_POST['trigger_conditions']) ? json_decode($_POST['trigger_conditions'], true) : null,
        'is_default' => isset($_POST['is_default']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 1
    ];
    
    if (empty($data['workflow_name'])) {
        $errors[] = 'Workflow name is required';
    }
    
    if (empty($errors)) {
        $result = order_management_create_workflow($data);
        if ($result['success']) {
            $success = true;
            header('Location: edit.php?id=' . $result['workflow_id']);
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to create workflow';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Workflow - Order Management</title>
    <link rel="stylesheet" href="../../../assets/css/order_management.css">
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { min-height: 100px; }
        .error { color: #dc3545; margin-top: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #0056b3; }
        .checkbox-group { margin: 10px 0; }
        .checkbox-group input[type="checkbox"] { margin-right: 5px; }
    </style>
</head>
<body>
    <h1>Create New Workflow</h1>
    
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
            <input type="text" id="workflow_name" name="workflow_name" required value="<?php echo htmlspecialchars($_POST['workflow_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="is_default" value="1" <?php echo isset($_POST['is_default']) ? 'checked' : ''; ?>>
                Set as default workflow
            </label>
        </div>
        
        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo !isset($_POST['is_active']) || $_POST['is_active'] ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit" class="btn">Create Workflow</button>
            <a href="index.php" class="btn" style="background: #6c757d;">Cancel</a>
        </div>
    </form>
</body>
</html>

