<?php
/**
 * Email Marketing Component - Loyalty Points
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loyalty Points</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Loyalty Points</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div class="email-marketing-card">
                <h3><a href="rules.php">Point Rules</a></h3>
                <p>Configure earning rules</p>
            </div>
            
            <div class="email-marketing-card">
                <h3><a href="tiers.php">Tiers/Labels</a></h3>
                <p>Manage loyalty tiers</p>
            </div>
            
            <div class="email-marketing-card">
                <h3><a href="transactions.php">Transactions</a></h3>
                <p>View point transactions</p>
            </div>
            
            <div class="email-marketing-card">
                <h3><a href="notifications.php">Notifications</a></h3>
                <p>Configure notifications</p>
            </div>
        </div>
    </div>
</body>
</html>

