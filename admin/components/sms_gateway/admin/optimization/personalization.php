<?php
/**
 * SMS Gateway Component - Message Personalization
 * Personalize SMS messages
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-optimization.php';

// Check permissions
if (!access_has_permission('sms_gateway_optimization')) {
    access_denied();
}

$personalizedMessage = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateCode = $_POST['template_code'] ?? '';
    $customerId = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $variables = [];
    
    // Parse variables from form
    if (isset($_POST['variables']) && is_array($_POST['variables'])) {
        foreach ($_POST['variables'] as $key => $value) {
            $variables[$key] = $value;
        }
    }
    
    if (empty($templateCode)) {
        $errors[] = 'Template code is required';
    } else {
        $personalizedMessage = sms_gateway_personalize_message($templateCode, $variables, $customerId);
        if (empty($personalizedMessage)) {
            $errors[] = 'Failed to personalize message or template not found';
        }
    }
}

$pageTitle = 'Message Personalization';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Optimization</a>
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
            <label for="template_code" class="required">Template Code</label>
            <input type="text" name="template_code" id="template_code" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="customer_id">Customer ID (Optional)</label>
            <input type="number" name="customer_id" id="customer_id" class="form-control" min="1">
            <small class="form-text text-muted">If provided, customer-specific variables will be added automatically</small>
        </div>
        
        <div class="form-group">
            <label>Variables</label>
            <div id="variables_container">
                <div class="input-group mb-2">
                    <input type="text" name="variables[key1]" placeholder="Variable Key" class="form-control">
                    <input type="text" name="variables[value1]" placeholder="Variable Value" class="form-control">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="addVariable()">Add Variable</button>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Personalize Message</button>
        </div>
    </form>
    
    <?php if ($personalizedMessage): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h5>Personalized Message</h5>
            </div>
            <div class="card-body">
                <pre><?php echo htmlspecialchars($personalizedMessage); ?></pre>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
let variableCount = 1;
function addVariable() {
    variableCount++;
    const container = document.getElementById('variables_container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="variables[key${variableCount}]" placeholder="Variable Key" class="form-control">
        <input type="text" name="variables[value${variableCount}]" placeholder="Variable Value" class="form-control">
        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(div);
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

