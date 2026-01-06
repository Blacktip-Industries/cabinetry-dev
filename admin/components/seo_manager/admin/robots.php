<?php
/**
 * SEO Manager Component - Robots.txt Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';

if (defined('SEO_MANAGER_NOT_INSTALLED')) {
    die('SEO Manager component is not installed.');
}

$robotsContent = seo_manager_generate_robots_txt();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Manager - Robots.txt</title>
    <link rel="stylesheet" href="../assets/css/seo_manager.css">
</head>
<body>
    <div class="seo-manager-robots">
        <h1>Robots.txt Management</h1>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
        
        <div class="robots-content">
            <h2>Current robots.txt</h2>
            <pre><?php echo htmlspecialchars($robotsContent); ?></pre>
        </div>
    </div>
</body>
</html>

