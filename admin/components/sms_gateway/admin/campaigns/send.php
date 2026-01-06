<?php
/**
 * SMS Gateway Component - Send SMS Campaign
 * Send a campaign
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
$success = false;
$sentCount = 0;

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

// Handle send
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipients = json_decode($campaign['recipient_list_json'] ?? '[]', true);
    $message = $campaign['message'] ?? '';
    
    if (empty($recipients)) {
        $errors[] = 'No recipients in campaign';
    } elseif (empty($message) && !$campaign['template_id']) {
        $errors[] = 'No message or template set';
    } else {
        // Send SMS to each recipient
        foreach ($recipients as $phoneNumber) {
            $phoneNumber = trim($phoneNumber);
            if (!empty($phoneNumber)) {
                if (function_exists('sms_gateway_send')) {
                    $result = sms_gateway_send($phoneNumber, $message, [
                        'component_name' => 'sms_gateway',
                        'component_reference_id' => $campaignId,
                        'campaign_id' => $campaignId
                    ]);
                    if ($result['success']) {
                        $sentCount++;
                    }
                }
            }
        }
        
        // Update campaign
        $stmt = $conn->prepare("UPDATE {$tableName} SET sent_count = sent_count + ?, status = 'sending', sent_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $sentCount, $campaignId);
            $stmt->execute();
            $stmt->close();
        }
        
        $success = true;
    }
}

$pageTitle = 'Send SMS Campaign';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Campaigns</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Campaign sent successfully. <?php echo $sentCount; ?> SMS sent.</div>
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
    
    <div class="card mb-3">
        <div class="card-header">
            <h5>Campaign Information</h5>
        </div>
        <div class="card-body">
            <p><strong>Campaign Name:</strong> <?php echo htmlspecialchars($campaign['campaign_name']); ?></p>
            <p><strong>Message:</strong> <?php echo htmlspecialchars(substr($campaign['message'] ?? '', 0, 100)); ?>...</p>
            <?php
            $recipients = json_decode($campaign['recipient_list_json'] ?? '[]', true);
            $recipientCount = is_array($recipients) ? count($recipients) : 0;
            ?>
            <p><strong>Recipients:</strong> <?php echo number_format($recipientCount); ?></p>
        </div>
    </div>
    
    <form method="POST">
        <div class="alert alert-warning">
            <strong>Warning:</strong> This will send SMS to all recipients in the campaign. Are you sure you want to proceed?
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Send campaign to all recipients?')">Send Campaign</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

