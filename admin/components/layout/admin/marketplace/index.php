<?php
/**
 * Layout Component - Marketplace
 * Browse and manage marketplace items
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/marketplace.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../core/design_systems.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Marketplace', true, 'layout_marketplace');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Marketplace</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'publish') {
        $templateId = (int)($_POST['template_id'] ?? 0);
        $templateType = $_POST['template_type'] ?? 'element_template';
        $marketplaceData = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price' => (float)($_POST['price'] ?? 0),
            'category' => trim($_POST['category'] ?? ''),
            'tags' => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))),
            'preview_image' => $_POST['preview_image'] ?? null,
            'is_free' => isset($_POST['is_free']) ? 1 : 0
        ];
        
        if ($templateId > 0 && !empty($marketplaceData['name'])) {
            $result = layout_marketplace_publish($templateId, $templateType, $marketplaceData);
            if ($result['success']) {
                $success = 'Template published to marketplace successfully';
            } else {
                $error = 'Failed to publish: ' . ($result['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Please provide template ID and name';
        }
    } elseif ($action === 'add_review') {
        $marketplaceId = (int)($_POST['marketplace_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        
        if ($marketplaceId > 0 && $rating >= 1 && $rating <= 5) {
            $result = layout_marketplace_add_review($marketplaceId, $rating, $comment);
            if ($result['success']) {
                $success = 'Review added successfully';
            } else {
                $error = 'Failed to add review: ' . ($result['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Please provide valid rating (1-5)';
        }
    }
}

// Get filters
$filters = [
    'category' => $_GET['category'] ?? '',
    'is_free' => isset($_GET['is_free']) ? (int)$_GET['is_free'] : null
];

$marketplaceItems = layout_marketplace_get_items($filters);

// Get templates for publishing
$templates = layout_element_template_get_all(['limit' => 100]);
$designSystems = layout_design_system_get_all(['limit' => 100]);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Marketplace</h1>
        <div class="layout__actions">
            <button onclick="document.getElementById('publish-form').style.display='block'" class="btn btn-primary">Publish Template</button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Publish Form -->
    <div id="publish-form" class="section" style="display: none;">
        <h2>Publish to Marketplace</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="publish">
            
            <div class="form-group">
                <label for="template_type">Template Type</label>
                <select name="template_type" id="template_type" class="form-control" required>
                    <option value="element_template">Element Template</option>
                    <option value="design_system">Design System</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="template_id">Template</label>
                <select name="template_id" id="template_id" class="form-control" required>
                    <option value="0">-- Select template --</option>
                    <?php foreach ($templates as $template): ?>
                    <option value="<?php echo $template['id']; ?>">
                        <?php echo htmlspecialchars($template['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="name">Marketplace Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_free" value="1" checked>
                    Free Template
                </label>
            </div>
            
            <div class="form-group">
                <label for="price">Price (if not free)</label>
                <input type="number" name="price" id="price" class="form-control" value="0" min="0" step="0.01">
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" name="category" id="category" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="tags">Tags (comma-separated)</label>
                <input type="text" name="tags" id="tags" class="form-control" placeholder="tag1, tag2, tag3">
            </div>
            
            <button type="submit" class="btn btn-primary">Publish</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('publish-form').style.display='none'">Cancel</button>
        </form>
    </div>

    <!-- Marketplace Items -->
    <div class="section">
        <h2>Marketplace Items</h2>
        
        <!-- Filters -->
        <form method="get" class="form-inline" style="margin-bottom: 1rem;">
            <select name="category" class="form-control">
                <option value="">All Categories</option>
                <!-- Categories would be populated from database -->
            </select>
            <select name="is_free" class="form-control">
                <option value="">All Items</option>
                <option value="1" <?php echo isset($_GET['is_free']) && $_GET['is_free'] == '1' ? 'selected' : ''; ?>>Free Only</option>
                <option value="0" <?php echo isset($_GET['is_free']) && $_GET['is_free'] == '0' ? 'selected' : ''; ?>>Paid Only</option>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
        
        <?php if (empty($marketplaceItems)): ?>
            <p>No items in marketplace yet.</p>
        <?php else: ?>
        <div class="marketplace-grid">
            <?php foreach ($marketplaceItems as $item): ?>
            <?php
            $rating = layout_marketplace_get_rating($item['id']);
            ?>
            <div class="marketplace-item">
                <?php if ($item['preview_image']): ?>
                <img src="<?php echo htmlspecialchars($item['preview_image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="marketplace-item__image">
                <?php endif; ?>
                <div class="marketplace-item__content">
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                    <div class="marketplace-item__meta">
                        <span class="price"><?php echo $item['is_free'] ? 'FREE' : '$' . number_format($item['price'], 2); ?></span>
                        <span class="rating">⭐ <?php echo number_format($rating['rating'], 1); ?> (<?php echo $rating['count']; ?>)</span>
                    </div>
                    <div class="marketplace-item__actions">
                        <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">View</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.marketplace-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.marketplace-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

.marketplace-item__image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.marketplace-item__content {
    padding: 1rem;
}

.marketplace-item__meta {
    display: flex;
    justify-content: space-between;
    margin: 0.5rem 0;
}

.marketplace-item__actions {
    margin-top: 1rem;
}
</style>

<?php
if ($hasBaseLayout) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

