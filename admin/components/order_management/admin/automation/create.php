<?php
/**
 * Order Management Component - Create Automation Rule
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/automation.php';

// Check if installed
if (!order_management_is_installed()) {
    die('Order Management component is not installed. Please run the installer.');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse trigger conditions
    $triggerConditions = [];
    if (!empty($_POST['trigger_event'])) {
        $triggerConditions[] = [
            'event' => $_POST['trigger_event'],
            'type' => $_POST['trigger_type'] ?? '',
            'operator' => $_POST['trigger_operator'] ?? '=',
            'value' => $_POST['trigger_value'] ?? ''
        ];
    }
    
    // Parse actions
    $actions = [];
    if (!empty($_POST['action_type'])) {
        $actions[] = [
            'type' => $_POST['action_type'],
            'params' => json_decode($_POST['action_params'] ?? '{}', true)
        ];
    }
    
    $data = [
        'rule_name' => $_POST['rule_name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'trigger_conditions' => $triggerConditions,
        'actions' => $actions,
        'priority' => $_POST['priority'] ?? 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (empty($data['rule_name'])) {
        $errors[] = 'Rule name is required';
    }
    
    if (empty($triggerConditions)) {
        $errors[] = 'At least one trigger condition is required';
    }
    
    if (empty($actions)) {
        $errors[] = 'At least one action is required';
    }
    
    if (empty($errors)) {
        $result = order_management_create_automation_rule($data);
        if ($result['success']) {
            $success = true;
            header('Location: edit.php?id=' . $result['rule_id']);
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to create rule';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Automation Rule - Order Management</title>
    <link rel="stylesheet" href="../../../assets/css/order_management.css">
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { min-height: 100px; }
        .error { color: #dc3545; margin-top: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #0056b3; }
        .help-text { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <h1>Create Automation Rule</h1>
    
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
            <label for="rule_name">Rule Name *</label>
            <input type="text" id="rule_name" name="rule_name" required value="<?php echo htmlspecialchars($_POST['rule_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="priority">Priority</label>
            <input type="number" id="priority" name="priority" value="<?php echo $_POST['priority'] ?? 0; ?>" min="0" max="100">
            <div class="help-text">Higher priority rules execute first (0-100)</div>
        </div>
        
        <h2>Trigger Conditions</h2>
        <div class="form-group">
            <label for="trigger_event">Trigger Event *</label>
            <select id="trigger_event" name="trigger_event" required>
                <option value="">Select event...</option>
                <option value="order_created">Order Created</option>
                <option value="status_changed">Status Changed</option>
                <option value="payment_received">Payment Received</option>
                <option value="payment_failed">Payment Failed</option>
                <option value="fulfillment_created">Fulfillment Created</option>
                <option value="fulfillment_shipped">Fulfillment Shipped</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="trigger_type">Condition Type</label>
            <select id="trigger_type" name="trigger_type">
                <option value="order_status">Order Status</option>
                <option value="payment_status">Payment Status</option>
                <option value="total_amount">Total Amount</option>
                <option value="customer_email">Customer Email</option>
                <option value="has_tag">Has Tag</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="trigger_operator">Operator</label>
            <select id="trigger_operator" name="trigger_operator">
                <option value="=">=</option>
                <option value="!=">!=</option>
                <option value=">">&gt;</option>
                <option value="<">&lt;</option>
                <option value=">=">&gt;=</option>
                <option value="<=">&lt;=</option>
                <option value="in">In</option>
                <option value="contains">Contains</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="trigger_value">Value</label>
            <input type="text" id="trigger_value" name="trigger_value" value="<?php echo htmlspecialchars($_POST['trigger_value'] ?? ''); ?>">
        </div>
        
        <h2>Actions</h2>
        <div class="form-group">
            <label for="action_type">Action Type *</label>
            <select id="action_type" name="action_type" required>
                <option value="">Select action...</option>
                <option value="update_status">Update Status</option>
                <option value="assign_workflow">Assign Workflow</option>
                <option value="assign_priority">Assign Priority</option>
                <option value="add_tag">Add Tag</option>
                <option value="send_notification">Send Notification</option>
                <option value="create_fulfillment">Create Fulfillment</option>
                <option value="update_custom_field">Update Custom Field</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="action_params">Action Parameters (JSON)</label>
            <textarea id="action_params" name="action_params" placeholder='{"status": "processing"}'><?php echo htmlspecialchars($_POST['action_params'] ?? '{}'); ?></textarea>
            <div class="help-text">Enter action parameters as JSON. Example: {"status": "processing"} for update_status action</div>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo !isset($_POST['is_active']) || $_POST['is_active'] ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit" class="btn">Create Rule</button>
            <a href="index.php" class="btn" style="background: #6c757d;">Cancel</a>
        </div>
    </form>
</body>
</html>

