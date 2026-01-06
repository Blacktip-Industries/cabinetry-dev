<?php
/**
 * Order Management Component - View Template
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

$templateId = $_GET['id'] ?? 0;
$template = order_management_get_template($templateId);

if (!$template) {
    header('Location: ' . order_management_get_component_admin_url() . '/templates/index.php');
    exit;
}

$pageTitle = 'Template: ' . htmlspecialchars($template['name']);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="order_management__header-actions">
            <a href="<?php echo order_management_get_component_admin_url(); ?>/templates/edit.php?id=<?php echo $templateId; ?>" class="btn btn-primary">Edit</a>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/templates/index.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
    
    <div class="order_management__section">
        <h2>Template Details</h2>
        <dl class="order_management__details-list">
            <dt>Name:</dt>
            <dd><?php echo htmlspecialchars($template['name']); ?></dd>
            
            <?php if ($template['description']): ?>
                <dt>Description:</dt>
                <dd><?php echo htmlspecialchars($template['description']); ?></dd>
            <?php endif; ?>
            
            <dt>Created:</dt>
            <dd><?php echo date('Y-m-d H:i:s', strtotime($template['created_at'])); ?></dd>
        </dl>
    </div>
    
    <div class="order_management__section">
        <h2>Template Data</h2>
        <pre class="order_management__code-block"><?php echo htmlspecialchars(json_encode($template['template_data'] ?? [], JSON_PRETTY_PRINT)); ?></pre>
    </div>
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

.order_management__header-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.order_management__section {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__section h2 {
    margin: 0 0 var(--spacing-md) 0;
}

.order_management__details-list {
    margin: 0;
    padding: 0;
}

.order_management__details-list dt {
    font-weight: bold;
    margin-top: var(--spacing-sm);
    color: var(--color-text-secondary);
}

.order_management__details-list dd {
    margin: var(--spacing-xs) 0 0 0;
    color: var(--color-text);
}

.order_management__code-block {
    background: var(--color-background-secondary);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    padding: var(--spacing-md);
    overflow-x: auto;
    font-family: monospace;
    font-size: var(--font-size-sm);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

