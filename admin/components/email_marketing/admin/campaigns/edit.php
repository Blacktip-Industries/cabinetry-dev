<?php
/**
 * Email Marketing Component - Edit Campaign
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$campaignId = $_GET['id'] ?? 0;
$campaign = email_marketing_get_campaign($campaignId);

if (!$campaign) {
    die('Campaign not found.');
}

$templates = email_marketing_list_templates(['is_active' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaignData = [
        'id' => $campaignId,
        'campaign_name' => $_POST['campaign_name'] ?? '',
        'campaign_type' => $_POST['campaign_type'] ?? 'promotional',
        'status' => $_POST['status'] ?? 'draft',
        'template_id' => !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null,
        'subject' => $_POST['subject'] ?? '',
        'from_email' => $_POST['from_email'] ?? '',
        'from_name' => $_POST['from_name'] ?? '',
        'schedule_type' => $_POST['schedule_type'] ?? 'one_time',
        'scheduled_send_at' => !empty($_POST['scheduled_send_at']) ? $_POST['scheduled_send_at'] : null
    ];
    
    if (email_marketing_save_campaign($campaignData)) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Campaign</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Edit Campaign</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Campaign Name:</label><br>
                <input type="text" name="campaign_name" value="<?php echo htmlspecialchars($campaign['campaign_name']); ?>" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Status:</label><br>
                <select name="status" style="width: 100%; padding: 8px;">
                    <option value="draft" <?php echo $campaign['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="scheduled" <?php echo $campaign['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="active" <?php echo $campaign['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                </select>
            </div>
            
            <div class="email-marketing-card">
                <label>Template:</label><br>
                <select name="template_id" style="width: 100%; padding: 8px;">
                    <option value="">Select Template</option>
                    <?php foreach ($templates as $template): ?>
                    <option value="<?php echo $template['id']; ?>" <?php echo $campaign['template_id'] == $template['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($template['template_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="email-marketing-card">
                <label>Subject:</label><br>
                <input type="text" name="subject" value="<?php echo htmlspecialchars($campaign['subject']); ?>" required style="width: 100%; padding: 8px;">
            </div>
            
            <button type="submit" class="email-marketing-button">Save Campaign</button>
        </form>
    </div>
</body>
</html>

