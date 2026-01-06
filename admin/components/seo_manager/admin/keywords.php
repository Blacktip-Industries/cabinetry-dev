<?php
/**
 * SEO Manager Component - Keywords Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (defined('SEO_MANAGER_NOT_INSTALLED')) {
    die('SEO Manager component is not installed.');
}

$keywords = seo_manager_get_keywords(['limit' => 50]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Manager - Keywords</title>
    <link rel="stylesheet" href="../assets/css/seo_manager.css">
</head>
<body>
    <div class="seo-manager-keywords">
        <h1>Keywords Management</h1>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
        
        <table class="keywords-table">
            <thead>
                <tr>
                    <th>Keyword</th>
                    <th>Search Volume</th>
                    <th>Difficulty</th>
                    <th>Competition</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($keywords as $keyword): ?>
                <tr>
                    <td><?php echo htmlspecialchars($keyword['keyword']); ?></td>
                    <td><?php echo number_format($keyword['search_volume']); ?></td>
                    <td><?php echo $keyword['difficulty_score']; ?></td>
                    <td><?php echo htmlspecialchars($keyword['competition_level']); ?></td>
                    <td><a href="?view=<?php echo $keyword['id']; ?>">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

