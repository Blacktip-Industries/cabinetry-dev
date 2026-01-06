<?php
/**
 * Order Management Component - Create Automation Rule
 * Create a new automation rule
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-automation.php';

// Check permissions
if (!access_has_permission('order_management_collection_automation')) {
    access_denied();
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruleData = [
        'rule_name' => $_POST['rule_name'] ?? '',
        'rule_type' => $_POST['rule_type'] ?? 'trigger',
        'trigger_event' => $_POST['trigger_event'] ?? '',
        'conditions' => [],
        'actions' => [],
        'priority' => (int)($_POST['priority'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Parse conditions
    if (isset($_POST['conditions']) && is_array($_POST['conditions'])) {
        $ruleData['conditions'] = $_POST['conditions'];
    }
    
    // Parse actions
    if (isset($_POST['actions']) && is_array($_POST['actions'])) {
        $ruleData['actions'] = $_POST['actions'];
    }
    
    if (empty($ruleData['rule_name'])) {
        $errors[] = 'Rule name is required';
    }
    if (empty($ruleData['trigger_event'])) {
        $errors[] = 'Trigger event is required';
    }
    
    if (empty($errors)) {
        $result = order_management_create_automation_rule($ruleData);
        if ($result['success']) {
            $success = true;
            $_SESSION['success_message'] = 'Automation rule created successfully';
            header('Location: index.php');
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to create rule';
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

$pageTitle = 'Create Automation Rule';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Rules</a>
    </div>
</div>

<div class="content-body">
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
            <input type="text" name="rule_name" id="rule_name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="rule_type">Rule Type</label>
            <select name="rule_type" id="rule_type" class="form-control">
                <option value="trigger">Trigger</option>
                <option value="schedule">Schedule</option>
                <option value="condition">Condition</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="trigger_event" class="required">Trigger Event</label>
            <select name="trigger_event" id="trigger_event" class="form-control" required>
                <option value="">Select Event</option>
                <?php foreach ($triggerEvents as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="priority">Priority</label>
            <input type="number" name="priority" id="priority" class="form-control" value="0" min="0">
            <small class="form-text text-muted">Lower number = higher priority</small>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                <label for="is_active" class="form-check-label">Active</label>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Create Rule</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

