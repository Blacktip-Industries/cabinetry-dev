<?php
/**
 * Email Marketing Component - View Lead
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/leads.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$leadId = $_GET['id'] ?? 0;
$lead = email_marketing_get_lead($leadId);

if (!$lead) {
    die('Lead not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        email_marketing_save_lead(['id' => $leadId, 'status' => 'approved', 'company_name' => $lead['company_name']]);
        email_marketing_add_lead_activity($leadId, 'status_changed', ['new_status' => 'approved']);
        header('Location: index.php');
        exit;
    }
    
    if (isset($_POST['convert'])) {
        email_marketing_convert_lead($leadId);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Lead</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Lead Details</h1>
        
        <div class="email-marketing-card">
            <h2><?php echo htmlspecialchars($lead['company_name']); ?></h2>
            <p><strong>Contact:</strong> <?php echo htmlspecialchars($lead['contact_name'] ?? 'N/A'); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($lead['email'] ?? 'N/A'); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($lead['phone'] ?? 'N/A'); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($lead['status']); ?></p>
            <p><strong>Industry:</strong> <?php echo htmlspecialchars($lead['industry'] ?? 'N/A'); ?></p>
        </div>
        
        <form method="POST">
            <?php if ($lead['status'] === 'pending'): ?>
            <button type="submit" name="approve" class="email-marketing-button">Approve Lead</button>
            <?php endif; ?>
            
            <?php if ($lead['status'] === 'approved'): ?>
            <button type="submit" name="convert" class="email-marketing-button">Convert to Account</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>

