<?php
/**
 * SEO Manager Component - Settings
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (defined('SEO_MANAGER_NOT_INSTALLED')) {
    die('SEO Manager component is not installed.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Manager - Settings</title>
    <link rel="stylesheet" href="../assets/css/seo_manager.css">
</head>
<body>
    <div class="seo-manager-settings">
        <h1>Settings</h1>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
        
        <p>Configure SEO Manager settings and automation modes.</p>
    </div>
</body>
</html>

