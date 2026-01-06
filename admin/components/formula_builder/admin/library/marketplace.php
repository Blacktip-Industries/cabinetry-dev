<?php
/**
 * Formula Builder Component - Template Marketplace
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/library.php';

// Get featured templates (most used)
$featuredTemplates = formula_builder_list_templates(['sort' => 'usage', 'limit' => 6]);

// Get highest rated templates
$allTemplates = formula_builder_list_templates(['limit' => 100]);
usort($allTemplates, function($a, $b) {
    return $b['average_rating'] <=> $a['average_rating'];
});
$topRatedTemplates = array_slice($allTemplates, 0, 6);

// Get newest templates
$newTemplates = formula_builder_list_templates(['sort' => 'date', 'limit' => 6]);

// Get categories
$categories = formula_builder_get_categories();

// Get tags
$tags = formula_builder_get_tags();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Template Marketplace - Formula Library</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .header { margin-bottom: 30px; }
        .section { margin-bottom: 40px; }
        .section h2 { border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .template-card { background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px; transition: box-shadow 0.3s; }
        .template-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .template-card h3 { margin-top: 0; }
        .template-card .meta { color: #666; font-size: 12px; margin: 10px 0; }
        .template-card .rating { color: #ffa500; font-weight: bold; }
        .template-card .actions { margin-top: 15px; display: flex; gap: 10px; }
        .btn { display: inline-block; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-secondary { background: #6c757d; }
        .tags { margin-top: 10px; }
        .tag { display: inline-block; background: #e9ecef; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-right: 5px; }
        .categories { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .category-badge { background: #007bff; color: white; padding: 5px 15px; border-radius: 20px; text-decoration: none; }
        .category-badge:hover { background: #0056b3; }
        .tag-cloud { margin-top: 20px; }
        .tag-cloud .tag { font-size: 12px; padding: 5px 10px; margin: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Template Marketplace</h1>
        <div>
            <a href="index.php" class="btn">Browse All Templates</a>
            <a href="save.php" class="btn btn-success">Save New Template</a>
            <a href="../formulas/index.php" class="btn btn-secondary">Back to Formulas</a>
        </div>
    </div>
    
    <div class="section">
        <h2>Featured Templates (Most Used)</h2>
        <?php if (empty($featuredTemplates)): ?>
            <p>No featured templates yet.</p>
        <?php else: ?>
            <div class="templates-grid">
                <?php foreach ($featuredTemplates as $template): ?>
                    <div class="template-card">
                        <h3><?php echo htmlspecialchars($template['formula_name']); ?></h3>
                        <div class="meta">
                            <?php if ($template['category']): ?>
                                <strong>Category:</strong> <?php echo htmlspecialchars($template['category']); ?><br>
                            <?php endif; ?>
                            <?php if ($template['average_rating'] > 0): ?>
                                <span class="rating">★ <?php echo number_format($template['average_rating'], 1); ?> (<?php echo $template['rating_count']; ?>)</span><br>
                            <?php endif; ?>
                            <strong>Used:</strong> <?php echo $template['usage_count']; ?> times
                        </div>
                        <?php if ($template['description']): ?>
                            <p><?php echo htmlspecialchars(substr($template['description'], 0, 100)); ?><?php echo strlen($template['description']) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        <div class="actions">
                            <a href="view.php?id=<?php echo $template['id']; ?>" class="btn">View</a>
                            <a href="../formulas/create.php?template_id=<?php echo $template['id']; ?>" class="btn btn-success">Use</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Highest Rated Templates</h2>
        <?php if (empty($topRatedTemplates)): ?>
            <p>No rated templates yet.</p>
        <?php else: ?>
            <div class="templates-grid">
                <?php foreach ($topRatedTemplates as $template): ?>
                    <?php if ($template['average_rating'] > 0): ?>
                        <div class="template-card">
                            <h3><?php echo htmlspecialchars($template['formula_name']); ?></h3>
                            <div class="meta">
                                <span class="rating">★ <?php echo number_format($template['average_rating'], 1); ?> (<?php echo $template['rating_count']; ?> ratings)</span><br>
                                <strong>Used:</strong> <?php echo $template['usage_count']; ?> times
                            </div>
                            <?php if ($template['description']): ?>
                                <p><?php echo htmlspecialchars(substr($template['description'], 0, 100)); ?><?php echo strlen($template['description']) > 100 ? '...' : ''; ?></p>
                            <?php endif; ?>
                            <div class="actions">
                                <a href="view.php?id=<?php echo $template['id']; ?>" class="btn">View</a>
                                <a href="../formulas/create.php?template_id=<?php echo $template['id']; ?>" class="btn btn-success">Use</a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Newest Templates</h2>
        <?php if (empty($newTemplates)): ?>
            <p>No templates yet. <a href="save.php">Create one</a></p>
        <?php else: ?>
            <div class="templates-grid">
                <?php foreach ($newTemplates as $template): ?>
                    <div class="template-card">
                        <h3><?php echo htmlspecialchars($template['formula_name']); ?></h3>
                        <div class="meta">
                            <strong>Created:</strong> <?php echo date('Y-m-d', strtotime($template['created_at'])); ?><br>
                            <?php if ($template['average_rating'] > 0): ?>
                                <span class="rating">★ <?php echo number_format($template['average_rating'], 1); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($template['description']): ?>
                            <p><?php echo htmlspecialchars(substr($template['description'], 0, 100)); ?><?php echo strlen($template['description']) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        <div class="actions">
                            <a href="view.php?id=<?php echo $template['id']; ?>" class="btn">View</a>
                            <a href="../formulas/create.php?template_id=<?php echo $template['id']; ?>" class="btn btn-success">Use</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($categories)): ?>
        <div class="section">
            <h2>Browse by Category</h2>
            <div class="categories">
                <?php foreach ($categories as $category): ?>
                    <a href="index.php?category=<?php echo urlencode($category); ?>" class="category-badge"><?php echo htmlspecialchars($category); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($tags)): ?>
        <div class="section">
            <h2>Popular Tags</h2>
            <div class="tag-cloud">
                <?php foreach ($tags as $tag): ?>
                    <a href="index.php?tags=<?php echo urlencode($tag); ?>" class="tag"><?php echo htmlspecialchars($tag); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>

