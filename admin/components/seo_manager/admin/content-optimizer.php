<?php
/**
 * SEO Manager Component - Content Optimizer
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
    <title>SEO Manager - Content Optimizer</title>
    <link rel="stylesheet" href="../assets/css/seo_manager.css">
</head>
<body>
    <div class="seo-manager-content-optimizer">
        <h1>Content Optimizer</h1>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
        
        <div class="optimizer-form">
            <h2>Optimize Page Content</h2>
            <form method="POST" action="api/optimize-content.php">
                <label>Page URL:</label>
                <input type="text" name="url" required>
                <button type="submit" class="btn">Optimize</button>
            </form>
        </div>
    </div>
</body>
</html>

