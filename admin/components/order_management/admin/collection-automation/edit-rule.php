<?php
/**
 * Order Management Component - Edit Automation Rule
 * Edit an existing automation rule
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-automation.php';

// Check permissions
if (!access_has_permission('order_management_collection_automation')) {
    access_denied();
}

$ruleId = $_GET['id'] ?? null;
$errors = [];
$success = false;

if (!$ruleId) {
    header('Location: index.php');
    exit;
}

$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('collection_automation_rules');
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $ruleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rule = $result->fetch_assoc();
    $stmt->close();
}

if (!$rule) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruleName = $_POST['rule_name'] ?? '';
    $ruleType = $_POST['rule_type'] ?? 'trigger';
    $triggerEvent = $_POST['trigger_event'] ?? '';
    $priority = (int)($_POST['priority'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($ruleName)) {
        $errors[] = 'Rule name is required';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE {$tableName} SET rule_name = ?, rule_type = ?, trigger_event = ?, priority = ?, is_active = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssiii", $ruleName, $ruleType, $triggerEvent, $priority, $isActive, $ruleId);
            if ($stmt->execute()) {
                $success = true;
                $_SESSION['success_message'] = 'Rule updated successfully';
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to update rule';
            }
            $stmt->close();
        }
    }
}

$triggerEvents = [
    'order_completed' => 'Order Completed',
    'collection_window_set' => 'Collection Window Set',
    'collection_confirmed' => 'Collection Confirmed',
    'collection_reschedule_requested' => 'Reschedule Requested',
    'collection_reminder_due' => 'Collection Reminder Due'
];

$pageTitle = 'Edit Automation Rule';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Rules</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Rule updated successfully</div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="form-horizontal">
        <div class="form-group">
            <label for="rule_name" class="required">Rule Name</label>
            <input type="text" name="rule_name" id="rule_name" class="form-control" 
                   value="<?php echo htmlspecialchars($rule['rule_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="rule_type">Rule Type</label>
            <select name="rule_type" id="rule_type" class="form-control">
                <option value="trigger" <?php echo $rule['rule_type'] === 'trigger' ? 'selected' : ''; ?>>Trigger</option>
                <option value="schedule" <?php echo $rule['rule_type'] === 'schedule' ? 'selected' : ''; ?>>Schedule</option>
                <option value="condition" <?php echo $rule['rule_type'] === 'condition' ? 'selected' : ''; ?>>Condition</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="trigger_event" class="required">Trigger Event</label>
            <select name="trigger_event" id="trigger_event" class="form-control" required>
                <option value="">Select Event</option>
                <?php foreach ($triggerEvents as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>" 
                            <?php echo $rule['trigger_event'] === $value ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="priority">Priority</label>
            <input type="number" name="priority" id="priority" class="form-control" 
                   value="<?php echo $rule['priority']; ?>" min="0">
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" 
                       <?php echo $rule['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active" class="form-check-label">Active</label>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Update Rule</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

