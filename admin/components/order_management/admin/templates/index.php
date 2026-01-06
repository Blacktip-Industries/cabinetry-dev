<?php
/**
 * Order Management Component - Templates List
 * List all order templates
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/templates.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Order Templates';

// Get templates
$templates = order_management_get_templates();

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/templates/create.php" class="btn btn-primary">Create Template</a>
    </div>
    
    <?php if (empty($templates)): ?>
        <div class="order_management__empty-state">
            <p>No templates found. <a href="<?php echo order_management_get_component_admin_url(); ?>/templates/create.php">Create your first template</a>.</p>
        </div>
    <?php else: ?>
        <div class="order_management__grid">
            <?php foreach ($templates as $template): ?>
                <div class="order_management__card">
                    <h3><?php echo htmlspecialchars($template['name']); ?></h3>
                    <?php if ($template['description']): ?>
                        <p><?php echo htmlspecialchars($template['description']); ?></p>
                    <?php endif; ?>
                    <div class="order_management__card-actions">
                        <a href="<?php echo order_management_get_component_admin_url(); ?>/templates/view.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                        <a href="<?php echo order_management_get_component_admin_url(); ?>/templates/edit.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.order_management__container {
    padding: var(--spacing-lg);
}

.order_management__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.order_management__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-md);
}

.order_management__card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__card h3 {
    margin: 0 0 var(--spacing-sm) 0;
}

.order_management__card-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
}

.order_management__empty-state {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-text-secondary);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

