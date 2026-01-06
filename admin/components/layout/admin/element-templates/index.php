<?php
/**
 * Layout Component - Element Templates Index
 * List and manage element templates
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Element Templates', true, 'layout_element_templates');
} else {
    // Minimal layout if base system not available
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Element Templates</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $templateId = (int)($_POST['template_id'] ?? 0);
    if ($templateId > 0) {
        $result = layout_element_template_delete($templateId);
        if ($result['success']) {
            $success = 'Template deleted successfully';
        } else {
            $error = 'Failed to delete template: ' . ($result['error'] ?? 'Unknown error');
        }
    }
}

// Get filters
$filters = [
    'element_type' => $_GET['element_type'] ?? '',
    'category' => $_GET['category'] ?? '',
    'is_published' => isset($_GET['is_published']) ? (int)$_GET['is_published'] : null,
    'search' => $_GET['search'] ?? '',
    'limit' => 50
];

// Get all templates
$templates = layout_element_template_get_all($filters);

// Get element type counts
$elementTypes = [
    'button', 'card', 'input', 'label', 'badge', 'date_picker', 'color_picker', 
    'select', 'checkbox', 'radio', 'table', 'table_tabs', 'pagination', 
    'breadcrumbs', 'tabs', 'alert', 'toast', 'modal', 'tooltip', 'progress', 
    'grid', 'container', 'section', 'sidebar', 'header', 'footer'
];

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Element Templates</h1>
        <div class="layout__actions">
            <a href="create.php" class="btn btn-primary">Create New Template</a>
            <a href="upload-image.php" class="btn btn-secondary">Upload Image</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="layout__filters">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Element Type:</label>
                <select name="element_type">
                    <option value="">All Types</option>
                    <?php foreach ($elementTypes as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filters['element_type'] === $type) ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Category:</label>
                <input type="text" name="category" value="<?php echo htmlspecialchars($filters['category']); ?>" placeholder="Category">
            </div>
            <div class="filter-group">
                <label>Status:</label>
                <select name="is_published">
                    <option value="">All</option>
                    <option value="1" <?php echo ($filters['is_published'] === 1) ? 'selected' : ''; ?>>Published</option>
                    <option value="0" <?php echo ($filters['is_published'] === 0) ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Search:</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Search templates...">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="index.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- Templates Grid -->
    <div class="layout__templates-grid">
        <?php if (empty($templates)): ?>
            <div class="layout__empty-state">
                <p>No templates found. <a href="create.php">Create your first template</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($templates as $template): ?>
                <div class="layout__template-card">
                    <div class="template-card__header">
                        <h3><?php echo htmlspecialchars($template['name']); ?></h3>
                        <span class="template-badge template-badge--<?php echo htmlspecialchars($template['element_type']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $template['element_type'])); ?>
                        </span>
                    </div>
                    <div class="template-card__body">
                        <?php if ($template['description']): ?>
                            <p class="template-description"><?php echo htmlspecialchars(substr($template['description'], 0, 100)); ?><?php echo strlen($template['description']) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        <div class="template-meta">
                            <span>Category: <?php echo htmlspecialchars($template['category'] ?? 'Uncategorized'); ?></span>
                            <span>Status: <?php echo $template['is_published'] ? 'Published' : 'Draft'; ?></span>
                        </div>
                    </div>
                    <div class="template-card__actions">
                        <a href="edit.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="versions.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-secondary">Versions</a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this template?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.layout__container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.layout__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.layout__header h1 {
    margin: 0;
}

.layout__actions {
    display: flex;
    gap: 10px;
}

.layout__filters {
    background: var(--color-surface, #f8f9fa);
    padding: 20px;
    border-radius: var(--border-radius-md, 8px);
    margin-bottom: 30px;
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 600;
    font-size: 14px;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid var(--color-border, #ddd);
    border-radius: var(--border-radius-sm, 4px);
    font-size: 14px;
}

.layout__templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.layout__template-card {
    background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, #ddd);
    border-radius: var(--border-radius-md, 8px);
    padding: 20px;
    transition: box-shadow 0.2s;
}

.layout__template-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.template-card__header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.template-card__header h3 {
    margin: 0;
    font-size: 18px;
}

.template-badge {
    padding: 4px 8px;
    border-radius: var(--border-radius-sm, 4px);
    font-size: 12px;
    font-weight: 600;
    background: var(--color-primary, #007bff);
    color: white;
}

.template-description {
    color: var(--color-text-secondary, #666);
    margin-bottom: 10px;
}

.template-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 12px;
    color: var(--color-text-secondary, #666);
}

.template-card__actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid var(--color-border, #ddd);
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: var(--border-radius-sm, 4px);
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
    transition: background 0.2s;
}

.btn-primary {
    background: var(--color-primary, #007bff);
    color: white;
}

.btn-primary:hover {
    background: var(--color-primary-dark, #0056b3);
}

.btn-secondary {
    background: var(--color-secondary, #6c757d);
    color: white;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.btn-danger {
    background: var(--color-danger, #dc3545);
    color: white;
}

.alert {
    padding: 12px 16px;
    border-radius: var(--border-radius-sm, 4px);
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.layout__empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--color-text-secondary, #666);
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

