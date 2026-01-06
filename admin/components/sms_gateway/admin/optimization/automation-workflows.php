<?php
/**
 * SMS Gateway Component - Automation Workflows
 * Build SMS automation workflows
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-optimization.php';

// Check permissions
if (!access_has_permission('sms_gateway_optimization')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_workflow') {
        $workflowData = [
            'workflow_name' => $_POST['workflow_name'] ?? '',
            'trigger_event' => $_POST['trigger_event'] ?? '',
            'conditions' => json_decode($_POST['conditions_json'] ?? '[]', true),
            'actions' => json_decode($_POST['actions_json'] ?? '[]', true),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if (empty($workflowData['workflow_name'])) {
            $errors[] = 'Workflow name is required';
        } else {
            $result = sms_gateway_create_automation_workflow($workflowData);
            if ($result['success']) {
                $success = true;
            } else {
                $errors[] = $result['error'] ?? 'Failed to create workflow';
            }
        }
    }
}

// Get workflows
$workflows = [];
$tableName = sms_gateway_get_table_name('sms_automation_workflows');
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY workflow_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $workflows[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Automation Workflows';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Optimization</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Workflow created successfully</div>
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
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Create Workflow</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_workflow">
                        <input type="hidden" name="conditions_json" id="conditions_json" value="[]">
                        <input type="hidden" name="actions_json" id="actions_json" value="[]">
                        
                        <div class="form-group">
                            <label for="workflow_name" class="required">Workflow Name</label>
                            <input type="text" name="workflow_name" id="workflow_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="trigger_event" class="required">Trigger Event</label>
                            <select name="trigger_event" id="trigger_event" class="form-control" required>
                                <option value="">Select Event</option>
                                <option value="sms_sent">SMS Sent</option>
                                <option value="sms_delivered">SMS Delivered</option>
                                <option value="sms_failed">SMS Failed</option>
                                <option value="customer_opt_in">Customer Opt In</option>
                                <option value="customer_opt_out">Customer Opt Out</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Workflow</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Workflows</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($workflows)): ?>
                        <p class="text-muted">No workflows created</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Workflow Name</th>
                                    <th>Trigger Event</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workflows as $workflow): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($workflow['workflow_name']); ?></td>
                                        <td><?php echo htmlspecialchars(str_replace('_', ' ', $workflow['trigger_event'])); ?></td>
                                        <td>
                                            <?php if ($workflow['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

