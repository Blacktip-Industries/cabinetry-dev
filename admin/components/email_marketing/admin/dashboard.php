<?php
/**
 * Email Marketing Component - Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check if installed
if (!email_marketing_is_installed()) {
    die('Component not installed. Please run install.php first.');
}

// Get dashboard stats
$conn = email_marketing_get_db_connection();
$stats = [
    'campaigns' => 0,
    'templates' => 0,
    'leads' => 0,
    'queue_pending' => 0
];

if ($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM email_marketing_campaigns");
    $stats['campaigns'] = $result->fetch_assoc()['count'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM email_marketing_templates");
    $stats['templates'] = $result->fetch_assoc()['count'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM email_marketing_leads");
    $stats['leads'] = $result->fetch_assoc()['count'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM email_marketing_queue WHERE status = 'pending'");
    $stats['queue_pending'] = $result->fetch_assoc()['count'] ?? 0;
}

// Include admin header if available
if (file_exists(__DIR__ . '/../../../includes/header.php')) {
    require_once __DIR__ . '/../../../includes/header.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Marketing - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/email_marketing.css">
    <link rel="stylesheet" href="../assets/css/variables.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Email Marketing Dashboard</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
            <div class="email-marketing-card">
                <h3>Campaigns</h3>
                <p style="font-size: 32px; font-weight: bold; color: var(--color-primary);"><?php echo $stats['campaigns']; ?></p>
            </div>
            
            <div class="email-marketing-card">
                <h3>Templates</h3>
                <p style="font-size: 32px; font-weight: bold; color: var(--color-primary);"><?php echo $stats['templates']; ?></p>
            </div>
            
            <div class="email-marketing-card">
                <h3>Leads</h3>
                <p style="font-size: 32px; font-weight: bold; color: var(--color-primary);"><?php echo $stats['leads']; ?></p>
            </div>
            
            <div class="email-marketing-card">
                <h3>Pending Queue</h3>
                <p style="font-size: 32px; font-weight: bold; color: var(--color-primary);"><?php echo $stats['queue_pending']; ?></p>
            </div>
        </div>
        
        <div class="email-marketing-card">
            <h2>Quick Actions</h2>
            <p>
                <a href="campaigns/index.php" class="email-marketing-button">Create Campaign</a>
                <a href="templates/index.php" class="email-marketing-button">Create Template</a>
                <a href="leads/index.php" class="email-marketing-button">View Leads</a>
                <a href="data-mining/index.php" class="email-marketing-button">Data Mining</a>
            </p>
        </div>
    </div>
</body>
</html>

