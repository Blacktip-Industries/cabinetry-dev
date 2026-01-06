<?php
/**
 * Email Marketing Component - Create Campaign
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/campaigns.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$templates = email_marketing_list_templates(['is_active' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaignData = [
        'campaign_name' => $_POST['campaign_name'] ?? '',
        'campaign_type' => $_POST['campaign_type'] ?? 'promotional',
        'status' => 'draft',
        'template_id' => !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null,
        'subject' => $_POST['subject'] ?? '',
        'from_email' => $_POST['from_email'] ?? '',
        'from_name' => $_POST['from_name'] ?? '',
        'schedule_type' => $_POST['schedule_type'] ?? 'one_time',
        'scheduled_send_at' => !empty($_POST['scheduled_send_at']) ? $_POST['scheduled_send_at'] : null
    ];
    
    $campaignId = email_marketing_save_campaign($campaignData);
    if ($campaignId) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Campaign</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Create Campaign</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Campaign Name:</label><br>
                <input type="text" name="campaign_name" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Campaign Type:</label><br>
                <select name="campaign_type" style="width: 100%; padding: 8px;">
                    <option value="promotional">Promotional</option>
                    <option value="welcome">Welcome</option>
                    <option value="trade_followup">Trade Follow-up</option>
                </select>
            </div>
            
            <div class="email-marketing-card">
                <label>Template:</label><br>
                <select name="template_id" style="width: 100%; padding: 8px;">
                    <option value="">Select Template</option>
                    <?php foreach ($templates as $template): ?>
                    <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['template_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="email-marketing-card">
                <label>Subject:</label><br>
                <input type="text" name="subject" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>From Email:</label><br>
                <input type="email" name="from_email" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>From Name:</label><br>
                <input type="text" name="from_name" required style="width: 100%; padding: 8px;">
            </div>
            
            <button type="submit" class="email-marketing-button">Create Campaign</button>
        </form>
    </div>
</body>
</html>

