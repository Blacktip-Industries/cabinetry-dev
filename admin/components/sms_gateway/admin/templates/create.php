<?php
/**
 * SMS Gateway Component - Create Template
 * Create a new SMS template
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$errors = [];
$tableName = sms_gateway_get_table_name('sms_templates');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateName = $_POST['template_name'] ?? '';
    $templateCode = $_POST['template_code'] ?? '';
    $message = $_POST['message'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($templateName)) {
        $errors[] = 'Template name is required';
    }
    if (empty($templateCode)) {
        $errors[] = 'Template code is required';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $templateCode)) {
        $errors[] = 'Template code must contain only lowercase letters, numbers, and underscores';
    } else {
        // Check if code already exists
        $checkStmt = $conn->prepare("SELECT id FROM {$tableName} WHERE template_code = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("s", $templateCode);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                $errors[] = 'Template code already exists';
            }
            $checkStmt->close();
        }
    }
    if (empty($message)) {
        $errors[] = 'Message is required';
    }
    
    if (empty($errors)) {
        // Extract variables from message
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $message, $matches);
        $variables = array_unique($matches[1] ?? []);
        $variablesJson = json_encode($variables);
        
        // Calculate character count and segments
        $characterCount = mb_strlen($message);
        $segmentCount = sms_gateway_calculate_segments($message);
        
        $createdBy = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (template_name, template_code, message, variables_json, character_count, segment_count, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssiiii", $templateName, $templateCode, $message, $variablesJson, $characterCount, $segmentCount, $isActive, $createdBy);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Template created successfully';
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to create template: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Failed to prepare statement';
        }
    }
}

$pageTitle = 'Create SMS Template';
include __DIR__ . '/../../../includes/header.php';
?>

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
            <label for="template_name" class="required">Template Name</label>
            <input type="text" name="template_name" id="template_name" class="form-control" required>
            <small class="form-text text-muted">A descriptive name for this template</small>
        </div>
        
        <div class="form-group">
            <label for="template_code" class="required">Template Code</label>
            <input type="text" name="template_code" id="template_code" class="form-control" required pattern="[a-z0-9_]+" placeholder="collection_reminder">
            <small class="form-text text-muted">Unique code for referencing this template (lowercase letters, numbers, and underscores only)</small>
        </div>
        
        <div class="form-group">
            <label for="message" class="required">Message</label>
            <textarea name="message" id="message" class="form-control" rows="5" required onkeyup="updateCharacterCount()"></textarea>
            <small class="form-text text-muted">
                Use {variable_name} for dynamic content. Example: "Hello {customer_name}, your order {order_number} is ready for collection."
            </small>
            <div class="mt-2">
                <strong>Character Count:</strong> <span id="char_count">0</span> | 
                <strong>Segments:</strong> <span id="segment_count">1</span>
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                <label for="is_active" class="form-check-label">Active</label>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Create Template</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function updateCharacterCount() {
    const message = document.getElementById('message').value;
    const charCount = message.length;
    const segmentCount = charCount <= 160 ? 1 : Math.ceil(charCount / 153);
    
    document.getElementById('char_count').textContent = charCount;
    document.getElementById('segment_count').textContent = segmentCount;
}

// Initialize on page load
updateCharacterCount();
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

