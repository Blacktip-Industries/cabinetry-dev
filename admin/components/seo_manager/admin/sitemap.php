<?php
/**
 * SEO Manager Component - Sitemap Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sitemap-generator.php';

if (defined('SEO_MANAGER_NOT_INSTALLED')) {
    die('SEO Manager component is not installed.');
}

$baseUrl = defined('SEO_MANAGER_BASE_URL') ? SEO_MANAGER_BASE_URL : 'http://localhost';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Manager - Sitemap</title>
    <link rel="stylesheet" href="../assets/css/seo_manager.css">
</head>
<body>
    <div class="seo-manager-sitemap">
        <h1>Sitemap Management</h1>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
        
        <div class="sitemap-actions">
            <a href="?generate=1" class="btn">Generate Sitemap</a>
            <a href="?download=1" class="btn">Download Sitemap</a>
        </div>
        
        <?php if (isset($_GET['generate'])): ?>
            <?php
            $sitemap = seo_manager_generate_sitemap($baseUrl);
            $saved = seo_manager_save_sitemap(__DIR__ . '/../../sitemap.xml', $baseUrl);
            ?>
            <div class="success">
                <?php if ($saved): ?>
                    Sitemap generated and saved successfully!
                <?php else: ?>
                    Error generating sitemap.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

