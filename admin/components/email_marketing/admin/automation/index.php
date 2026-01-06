<?php
/**
 * Email Marketing Component - Automation
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
    <title>Automation</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Automation</h1>
        
        <div class="email-marketing-card">
            <h2><a href="rules.php">Automation Rules</a></h2>
            <p>Configure automated campaign triggers</p>
        </div>
        
        <div class="email-marketing-card">
            <h2><a href="schedules.php">Trade Schedules</a></h2>
            <p>Configure trade customer follow-up schedules</p>
        </div>
    </div>
</body>
</html>

