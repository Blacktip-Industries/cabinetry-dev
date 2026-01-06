<?php
/**
 * Formula Builder Component - Template Library List
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/library.php';

// Check if installed
if (!formula_builder_is_installed()) {
    header('Location: ../../install.php');
    exit;
}

// Get filters
$category = $_GET['category'] ?? '';
$tags = $_GET['tags'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'usage';
$isPublic = isset($_GET['public']) ? (int)$_GET['public'] : null;

$filters = [
    'category' => $category,
    'tags' => $tags,
    'is_public' => $isPublic,
    'sort' => $sort,
    'limit' => 50
];

// Get templates
if (!empty($search)) {
    $templates = formula_builder_search_templates($search, $filters);
} else {
    $templates = formula_builder_list_templates($filters);
}

// Get categories and tags for filters
$categories = formula_builder_get_categories();
$allTags = formula_builder_get_tags();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Formula Library - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filters { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .filters form { display: flex; gap: 10px; flex-wrap: wrap; align-items: end; }
        .filters .form-group { display: flex; flex-direction: column; }
        .filters label { font-size: 12px; margin-bottom: 5px; }
        .filters input, .filters select { padding: 5px; }
        .templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .template-card { background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px; }
        .template-card h3 { margin-top: 0; }
        .template-card .meta { color: #666; font-size: 12px; margin: 10px 0; }
        .template-card .rating { color: #ffa500; }
        .template-card .actions { margin-top: 15px; display: flex; gap: 10px; }
        .btn { display: inline-block; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-success { background: #28a745; }
        .tags { margin-top: 10px; }
        .tag { display: inline-block; background: #e9ecef; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Formula Library</h1>
        <div>
            <a href="save.php" class="btn btn-success">Save New Template</a>
            <a href="marketplace.php" class="btn">Marketplace</a>
            <a href="../formulas/index.php" class="btn btn-secondary">Back to Formulas</a>
        </div>
    </div>
    
    <div class="filters">
        <form method="GET">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search templates...">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sort By</label>
                <select name="sort">
                    <option value="usage" <?php echo $sort === 'usage' ? 'selected' : ''; ?>>Most Used</option>
                    <option value="date" <?php echo $sort === 'date' ? 'selected' : ''; ?>>Newest</option>
                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                </select>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn">Filter</button>
            </div>
        </form>
    </div>
    
    <?php if (empty($templates)): ?>
        <p>No templates found. <a href="save.php">Create one</a></p>
    <?php else: ?>
        <div class="templates-grid">
            <?php foreach ($templates as $template): ?>
                <div class="template-card">
                    <h3><?php echo htmlspecialchars($template['formula_name']); ?></h3>
                    <div class="meta">
                        <?php if ($template['category']): ?>
                            <strong>Category:</strong> <?php echo htmlspecialchars($template['category']); ?><br>
                        <?php endif; ?>
                        <?php if ($template['average_rating'] > 0): ?>
                            <span class="rating">★ <?php echo number_format($template['average_rating'], 1); ?> (<?php echo $template['rating_count']; ?> ratings)</span><br>
                        <?php endif; ?>
                        <strong>Used:</strong> <?php echo $template['usage_count']; ?> times<br>
                        <strong>Created:</strong> <?php echo date('Y-m-d', strtotime($template['created_at'])); ?>
                    </div>
                    <?php if ($template['description']): ?>
                        <p><?php echo htmlspecialchars(substr($template['description'], 0, 100)); ?><?php echo strlen($template['description']) > 100 ? '...' : ''; ?></p>
                    <?php endif; ?>
                    <?php if ($template['tags']): ?>
                        <div class="tags">
                            <?php 
                            $tagArray = explode(',', $template['tags']);
                            foreach ($tagArray as $tag): 
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                                <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="actions">
                        <a href="view.php?id=<?php echo $template['id']; ?>" class="btn">View</a>
                        <a href="../formulas/create.php?template_id=<?php echo $template['id']; ?>" class="btn btn-success">Use</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>

