<?php
/**
 * SEO Manager Component - Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check if installed
if (defined('SEO_MANAGER_NOT_INSTALLED')) {
    die('SEO Manager component is not installed. Please run the installer.');
}

// Get statistics
$conn = seo_manager_get_db_connection();
$stats = [
    'total_pages' => 0,
    'total_keywords' => 0,
    'pending_suggestions' => 0,
    'avg_seo_score' => 0
];

if ($conn) {
    $pagesTable = seo_manager_get_table_name('pages');
    $result = $conn->query("SELECT COUNT(*) as count, AVG(seo_score) as avg_score FROM {$pagesTable} WHERE is_active = 1");
    if ($row = $result->fetch_assoc()) {
        $stats['total_pages'] = $row['count'];
        $stats['avg_seo_score'] = round($row['avg_score'] ?? 0);
    }
    
    $keywordsTable = seo_manager_get_table_name('keywords');
    $result = $conn->query("SELECT COUNT(*) as count FROM {$keywordsTable} WHERE is_tracked = 1");
    if ($row = $result->fetch_assoc()) {
        $stats['total_keywords'] = $row['count'];
    }
    
    $suggestionsTable = seo_manager_get_table_name('content_suggestions');
    $result = $conn->query("SELECT COUNT(*) as count FROM {$suggestionsTable} WHERE status = 'pending'");
    if ($row = $result->fetch_assoc()) {
        $stats['pending_suggestions'] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Manager - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/seo_manager.css">
</head>
<body>
    <div class="seo-manager-dashboard">
        <h1>SEO Manager Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Pages</h3>
                <p class="stat-value"><?php echo $stats['total_pages']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Tracked Keywords</h3>
                <p class="stat-value"><?php echo $stats['total_keywords']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Suggestions</h3>
                <p class="stat-value"><?php echo $stats['pending_suggestions']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Average SEO Score</h3>
                <p class="stat-value"><?php echo $stats['avg_seo_score']; ?>%</p>
            </div>
        </div>
        
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <a href="pages.php" class="btn">Manage Pages</a>
            <a href="keywords.php" class="btn">Manage Keywords</a>
            <a href="content-optimizer.php" class="btn">Content Optimizer</a>
            <a href="sitemap.php" class="btn">Sitemap</a>
        </div>
    </div>
</body>
</html>

