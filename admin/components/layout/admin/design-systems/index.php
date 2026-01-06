<?php
/**
 * Layout Component - Design Systems Index
 * List and manage design systems
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/design_systems.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Design Systems', true, 'layout_design_systems');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Design Systems</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $designSystemId = (int)($_POST['design_system_id'] ?? 0);
    if ($designSystemId > 0) {
        $result = layout_design_system_delete($designSystemId);
        if ($result['success']) {
            $success = 'Design system deleted successfully';
        } else {
            $error = 'Failed to delete design system: ' . ($result['error'] ?? 'Unknown error');
        }
    }
}

// Get filters
$filters = [
    'is_published' => isset($_GET['is_published']) ? (int)$_GET['is_published'] : null,
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? '',
    'limit' => 50
];

// Get all design systems
$designSystems = layout_design_system_get_all($filters);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Design Systems</h1>
        <div class="layout__actions">
            <a href="create.php" class="btn btn-primary">Create New Design System</a>
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
                <label>Status:</label>
                <select name="is_published">
                    <option value="">All</option>
                    <option value="1" <?php echo ($filters['is_published'] === 1) ? 'selected' : ''; ?>>Published</option>
                    <option value="0" <?php echo ($filters['is_published'] === 0) ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Category:</label>
                <input type="text" name="category" value="<?php echo htmlspecialchars($filters['category']); ?>" placeholder="Category">
            </div>
            <div class="filter-group">
                <label>Search:</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Search design systems...">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="index.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- Design Systems Grid -->
    <div class="layout__design-systems-grid">
        <?php if (empty($designSystems)): ?>
            <div class="layout__empty-state">
                <p>No design systems found. <a href="create.php">Create your first design system</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($designSystems as $ds): ?>
                <div class="layout__design-system-card">
                    <div class="design-system-card__header">
                        <h3><?php echo htmlspecialchars($ds['name']); ?></h3>
                        <?php if ($ds['is_default']): ?>
                            <span class="badge badge-primary">Default</span>
                        <?php endif; ?>
                        <?php if ($ds['is_published']): ?>
                            <span class="badge badge-success">Published</span>
                        <?php endif; ?>
                    </div>
                    <div class="design-system-card__body">
                        <?php if ($ds['description']): ?>
                            <p class="design-system-description"><?php echo htmlspecialchars(substr($ds['description'], 0, 100)); ?><?php echo strlen($ds['description']) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        <div class="design-system-meta">
                            <span>Version: <?php echo htmlspecialchars($ds['version']); ?></span>
                            <?php if ($ds['parent_design_system_id']): ?>
                                <span>Parent: #<?php echo $ds['parent_design_system_id']; ?></span>
                            <?php endif; ?>
                            <span>Category: <?php echo htmlspecialchars($ds['category'] ?? 'Uncategorized'); ?></span>
                        </div>
                    </div>
                    <div class="design-system-card__actions">
                        <a href="view.php?id=<?php echo $ds['id']; ?>" class="btn btn-sm btn-primary">View</a>
                        <a href="edit.php?id=<?php echo $ds['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this design system?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="design_system_id" value="<?php echo $ds['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.layout__design-systems-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.layout__design-system-card {
    background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, #ddd);
    border-radius: var(--border-radius-md, 8px);
    padding: 20px;
    transition: box-shadow 0.2s;
}

.layout__design-system-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.design-system-card__header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
    gap: 10px;
}

.design-system-card__header h3 {
    margin: 0;
    font-size: 18px;
    flex: 1;
}

.badge {
    padding: 4px 8px;
    border-radius: var(--border-radius-sm, 4px);
    font-size: 11px;
    font-weight: 600;
}

.badge-primary {
    background: var(--color-primary, #007bff);
    color: white;
}

.badge-success {
    background: var(--color-success, #28a745);
    color: white;
}

.design-system-description {
    color: var(--color-text-secondary, #666);
    margin-bottom: 10px;
}

.design-system-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 12px;
    color: var(--color-text-secondary, #666);
}

.design-system-card__actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid var(--color-border, #ddd);
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

