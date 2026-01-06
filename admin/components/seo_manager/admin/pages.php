<?php
/**
 * SEO Manager Component - Pages Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (defined('SEO_MANAGER_NOT_INSTALLED')) {
    die('SEO Manager component is not installed.');
}

$pages = seo_manager_get_pages(['limit' => 50]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Manager - Pages</title>
    <link rel="stylesheet" href="../assets/css/seo_manager.css">
</head>
<body>
    <div class="seo-manager-pages">
        <h1>SEO Pages Management</h1>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
        
        <table class="pages-table">
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Title</th>
                    <th>SEO Score</th>
                    <th>Focus Keyword</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                <tr>
                    <td><?php echo htmlspecialchars($page['url']); ?></td>
                    <td><?php echo htmlspecialchars($page['title'] ?? '-'); ?></td>
                    <td><?php echo $page['seo_score']; ?>%</td>
                    <td><?php echo htmlspecialchars($page['focus_keyword'] ?? '-'); ?></td>
                    <td><a href="?edit=<?php echo $page['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

