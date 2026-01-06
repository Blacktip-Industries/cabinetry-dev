<?php
/**
 * Order Management Component - Workflow Builder
 * Visual workflow builder for collection automation
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-automation.php';

// Check permissions
if (!access_has_permission('order_management_collection_automation')) {
    access_denied();
}

$conn = order_management_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_workflow') {
        $workflowName = $_POST['workflow_name'] ?? '';
        $workflowSteps = json_encode($_POST['steps'] ?? []);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($workflowName)) {
            $errors[] = 'Workflow name is required';
        } else {
            $tableName = order_management_get_table_name('collection_workflows');
            $stmt = $conn->prepare("INSERT INTO {$tableName} (workflow_name, workflow_steps_json, is_active) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssi", $workflowName, $workflowSteps, $isActive);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to create workflow';
                }
                $stmt->close();
            }
        }
    }
}

// Get workflows
$workflows = [];
$tableName = order_management_get_table_name('collection_workflows');
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY workflow_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $workflows[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Workflow Builder';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Automation</a>
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
                    <form method="POST" id="workflow_form">
                        <input type="hidden" name="action" value="create_workflow">
                        <input type="hidden" name="steps" id="workflow_steps" value="[]">
                        
                        <div class="form-group">
                            <label for="workflow_name" class="required">Workflow Name</label>
                            <input type="text" name="workflow_name" id="workflow_name" class="form-control" required>
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
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Available Steps</h5>
                </div>
                <div class="card-body">
                    <div id="available_steps">
                        <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="addStep('send_reminder')">Send Reminder</button>
                        <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="addStep('assign_staff')">Assign Staff</button>
                        <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="addStep('check_capacity')">Check Capacity</button>
                        <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="addStep('resolve_conflicts')">Resolve Conflicts</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Workflow Steps</h5>
                </div>
                <div class="card-body">
                    <div id="workflow_steps_container">
                        <p class="text-muted">Add steps to build your workflow</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($workflows)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5>Existing Workflows</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Workflow Name</th>
                            <th>Steps</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workflows as $workflow): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($workflow['workflow_name']); ?></td>
                                <td>
                                    <?php
                                    $steps = json_decode($workflow['workflow_steps_json'] ?? '[]', true);
                                    echo count($steps) . ' step(s)';
                                    ?>
                                </td>
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
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
let workflowSteps = [];

function addStep(stepType) {
    workflowSteps.push({type: stepType, config: {}});
    updateWorkflowDisplay();
}

function removeStep(index) {
    workflowSteps.splice(index, 1);
    updateWorkflowDisplay();
}

function updateWorkflowDisplay() {
    const container = document.getElementById('workflow_steps_container');
    const stepsInput = document.getElementById('workflow_steps');
    stepsInput.value = JSON.stringify(workflowSteps);
    
    if (workflowSteps.length === 0) {
        container.innerHTML = '<p class="text-muted">Add steps to build your workflow</p>';
    } else {
        let html = '<ol>';
        workflowSteps.forEach((step, index) => {
            html += `<li>${step.type} <button type="button" class="btn btn-sm btn-danger" onclick="removeStep(${index})">Remove</button></li>`;
        });
        html += '</ol>';
        container.innerHTML = html;
    }
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

