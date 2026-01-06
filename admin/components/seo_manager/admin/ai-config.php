<?php
/**
 * SEO Manager Component - AI Configuration
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
    <title>SEO Manager - AI Configuration</title>
    <link rel="stylesheet" href="../assets/css/seo_manager.css">
</head>
<body>
    <div class="seo-manager-ai-config">
        <h1>AI Configuration</h1>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
        
        <p>Configure AI API providers for content optimization.</p>
    </div>
</body>
</html>

