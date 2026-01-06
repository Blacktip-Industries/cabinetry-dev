<?php
/**
 * SMS Gateway Component - Create SMS Campaign
 * Create a new SMS campaign
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_campaigns_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaignName = $_POST['campaign_name'] ?? '';
    $templateId = !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null;
    $message = $_POST['message'] ?? '';
    $recipientList = $_POST['recipient_list'] ?? '';
    $scheduledSendAt = $_POST['scheduled_send_at'] ?? null;
    $status = $scheduledSendAt ? 'scheduled' : 'draft';
    
    if (empty($campaignName)) {
        $errors[] = 'Campaign name is required';
    }
    if (empty($message) && !$templateId) {
        $errors[] = 'Message or Template ID is required';
    }
    
    if (empty($errors)) {
        $tableName = sms_gateway_get_table_name('sms_campaigns');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (campaign_name, template_id, message, recipient_list_json, scheduled_send_at, status) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $recipientListJson = json_encode(explode("\n", $recipientList));
            $stmt->bind_param("siss", $campaignName, $templateId, $message, $recipientListJson, $scheduledSendAt, $status);
            if ($stmt->execute()) {
                $success = true;
                $_SESSION['success_message'] = 'Campaign created successfully';
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to create campaign';
            }
            $stmt->close();
        }
    }
}

// Get templates
$templates = [];
$templatesTable = sms_gateway_get_table_name('sms_templates');
$stmt = $conn->prepare("SELECT id, template_name, template_code FROM {$templatesTable} WHERE is_active = 1 ORDER BY template_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Create SMS Campaign';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Campaigns</a>
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
            <label for="campaign_name" class="required">Campaign Name</label>
            <input type="text" name="campaign_name" id="campaign_name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="template_id">Template</label>
            <select name="template_id" id="template_id" class="form-control">
                <option value="">No Template (Use Custom Message)</option>
                <?php foreach ($templates as $template): ?>
                    <option value="<?php echo $template['id']; ?>">
                        <?php echo htmlspecialchars($template['template_name']); ?> (<?php echo htmlspecialchars($template['template_code']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="message">Message</label>
            <textarea name="message" id="message" class="form-control" rows="5"></textarea>
            <small class="form-text text-muted">Required if no template selected</small>
        </div>
        
        <div class="form-group">
            <label for="recipient_list" class="required">Recipient List</label>
            <textarea name="recipient_list" id="recipient_list" class="form-control" rows="10" required></textarea>
            <small class="form-text text-muted">One phone number per line</small>
        </div>
        
        <div class="form-group">
            <label for="scheduled_send_at">Scheduled Send At (Optional)</label>
            <input type="datetime-local" name="scheduled_send_at" id="scheduled_send_at" class="form-control">
            <small class="form-text text-muted">Leave empty to save as draft</small>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Create Campaign</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

