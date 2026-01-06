<?php
/**
 * SMS Gateway Component - Edit SMS Campaign
 * Edit an existing SMS campaign
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_campaigns_manage')) {
    access_denied();
}

$campaignId = $_GET['id'] ?? null;
$errors = [];

if (!$campaignId) {
    header('Location: index.php');
    exit;
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_campaigns');
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $campaignId);
    $stmt->execute();
    $result = $stmt->get_result();
    $campaign = $result->fetch_assoc();
    $stmt->close();
}

if (!$campaign) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaignName = $_POST['campaign_name'] ?? '';
    $templateId = !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null;
    $message = $_POST['message'] ?? '';
    $recipientList = $_POST['recipient_list'] ?? '';
    $scheduledSendAt = $_POST['scheduled_send_at'] ?? null;
    
    if (empty($campaignName)) {
        $errors[] = 'Campaign name is required';
    }
    
    if (empty($errors)) {
        $recipientListJson = json_encode(explode("\n", $recipientList));
        $stmt = $conn->prepare("UPDATE {$tableName} SET campaign_name = ?, template_id = ?, message = ?, recipient_list_json = ?, scheduled_send_at = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sissi", $campaignName, $templateId, $message, $recipientListJson, $scheduledSendAt, $campaignId);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Campaign updated successfully';
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to update campaign';
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

// Parse recipient list
$recipientList = '';
if ($campaign['recipient_list_json']) {
    $recipients = json_decode($campaign['recipient_list_json'], true);
    if (is_array($recipients)) {
        $recipientList = implode("\n", $recipients);
    }
}

$pageTitle = 'Edit SMS Campaign';
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
            <input type="text" name="campaign_name" id="campaign_name" class="form-control" 
                   value="<?php echo htmlspecialchars($campaign['campaign_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="template_id">Template</label>
            <select name="template_id" id="template_id" class="form-control">
                <option value="">No Template</option>
                <?php foreach ($templates as $template): ?>
                    <option value="<?php echo $template['id']; ?>" 
                            <?php echo $campaign['template_id'] == $template['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($template['template_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="message">Message</label>
            <textarea name="message" id="message" class="form-control" rows="5"><?php echo htmlspecialchars($campaign['message'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="recipient_list" class="required">Recipient List</label>
            <textarea name="recipient_list" id="recipient_list" class="form-control" rows="10" required><?php echo htmlspecialchars($recipientList); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="scheduled_send_at">Scheduled Send At</label>
            <input type="datetime-local" name="scheduled_send_at" id="scheduled_send_at" class="form-control" 
                   value="<?php echo $campaign['scheduled_send_at'] ? date('Y-m-d\TH:i', strtotime($campaign['scheduled_send_at'])) : ''; ?>">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Update Campaign</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

