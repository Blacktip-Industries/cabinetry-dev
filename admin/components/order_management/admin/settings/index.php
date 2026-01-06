<?php
/**
 * Order Management Component - Settings
 * Main settings page with tabs
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$activeTab = $_GET['tab'] ?? 'general';
$pageTitle = 'Settings';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    
    <!-- Tabs -->
    <div class="order_management__tabs">
        <a href="?tab=general" class="order_management__tab <?php echo $activeTab === 'general' ? 'active' : ''; ?>">General</a>
        <a href="?tab=channels" class="order_management__tab <?php echo $activeTab === 'channels' ? 'active' : ''; ?>">Channels</a>
        <a href="?tab=custom-fields" class="order_management__tab <?php echo $activeTab === 'custom-fields' ? 'active' : ''; ?>">Custom Fields</a>
        <a href="?tab=priority" class="order_management__tab <?php echo $activeTab === 'priority' ? 'active' : ''; ?>">Priority Levels</a>
        <a href="?tab=print-templates" class="order_management__tab <?php echo $activeTab === 'print-templates' ? 'active' : ''; ?>">Print Templates</a>
        <a href="?tab=api" class="order_management__tab <?php echo $activeTab === 'api' ? 'active' : ''; ?>">API</a>
        <a href="?tab=webhooks" class="order_management__tab <?php echo $activeTab === 'webhooks' ? 'active' : ''; ?>">Webhooks</a>
        <a href="?tab=permissions" class="order_management__tab <?php echo $activeTab === 'permissions' ? 'active' : ''; ?>">Permissions</a>
        <a href="?tab=migration" class="order_management__tab <?php echo $activeTab === 'migration' ? 'active' : ''; ?>">Migration</a>
    </div>
    
    <!-- Tab Content -->
    <div class="order_management__tab-content">
        <?php if ($activeTab === 'general'): ?>
            <div class="order_management__section">
                <h2>General Settings</h2>
                <form method="POST" class="order_management__form">
                    <div class="order_management__form-group">
                        <label for="default_workflow">Default Workflow</label>
                        <select id="default_workflow" name="default_workflow">
                            <option value="">None</option>
                            <!-- Would load workflows here -->
                        </select>
                    </div>
                    
                    <div class="order_management__form-group">
                        <label for="auto_fulfill">Auto-create Fulfillment</label>
                        <input type="checkbox" id="auto_fulfill" name="auto_fulfill" value="1">
                    </div>
                    
                    <div class="order_management__form-actions">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
            
        <?php elseif ($activeTab === 'channels'): ?>
            <div class="order_management__section">
                <h2>Multi-Channel Settings</h2>
                <p><a href="<?php echo order_management_get_component_admin_url(); ?>/channels/index.php" class="btn btn-primary">Manage Channels</a></p>
            </div>
            
        <?php elseif ($activeTab === 'custom-fields'): ?>
            <div class="order_management__section">
                <h2>Custom Fields</h2>
                <p><a href="<?php echo order_management_get_component_admin_url(); ?>/custom-fields/index.php" class="btn btn-primary">Manage Custom Fields</a></p>
            </div>
            
        <?php elseif ($activeTab === 'priority'): ?>
            <div class="order_management__section">
                <h2>Priority Levels</h2>
                <p><a href="<?php echo order_management_get_component_admin_url(); ?>/priority/index.php" class="btn btn-primary">Manage Priority Levels</a></p>
            </div>
            
        <?php elseif ($activeTab === 'print-templates'): ?>
            <div class="order_management__section">
                <h2>Print Templates</h2>
                <p><a href="<?php echo order_management_get_component_admin_url(); ?>/printing/templates.php" class="btn btn-primary">Manage Print Templates</a></p>
            </div>
            
        <?php elseif ($activeTab === 'api'): ?>
            <div class="order_management__section">
                <h2>API Settings</h2>
                <p>API endpoint: <code><?php echo order_management_get_component_admin_url(); ?>/api/</code></p>
                <p><a href="<?php echo order_management_get_component_admin_url(); ?>/settings/api-keys.php" class="btn btn-primary">Manage API Keys</a></p>
            </div>
            
        <?php elseif ($activeTab === 'webhooks'): ?>
            <div class="order_management__section">
                <h2>Webhooks</h2>
                <p><a href="<?php echo order_management_get_component_admin_url(); ?>/webhooks/index.php" class="btn btn-primary">Manage Webhooks</a></p>
            </div>
            
        <?php elseif ($activeTab === 'permissions'): ?>
            <div class="order_management__section">
                <h2>Permissions</h2>
                <p><a href="<?php echo order_management_get_component_admin_url(); ?>/permissions/index.php" class="btn btn-primary">Manage Permissions</a></p>
            </div>
            
        <?php elseif ($activeTab === 'migration'): ?>
            <div class="order_management__section">
                <h2>Migration</h2>
                <p><a href="<?php echo order_management_get_component_admin_url(); ?>/migration/index.php" class="btn btn-primary">View Migration Status</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.order_management__container {
    padding: var(--spacing-lg);
}

.order_management__header {
    margin-bottom: var(--spacing-lg);
}

.order_management__tabs {
    display: flex;
    gap: var(--spacing-sm);
    border-bottom: var(--border-width) solid var(--color-border);
    margin-bottom: var(--spacing-lg);
    flex-wrap: wrap;
}

.order_management__tab {
    padding: var(--spacing-sm) var(--spacing-md);
    text-decoration: none;
    color: var(--color-text);
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
}

.order_management__tab:hover {
    color: var(--color-primary);
}

.order_management__tab.active {
    color: var(--color-primary);
    border-bottom-color: var(--color-primary);
}

.order_management__tab-content {
    min-height: 300px;
}

.order_management__section {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__section h2 {
    margin: 0 0 var(--spacing-md) 0;
}

.order_management__form-group {
    margin-bottom: var(--spacing-md);
}

.order_management__form-group label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: bold;
}

.order_management__form-group input,
.order_management__form-group select {
    width: 100%;
    max-width: 400px;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
}

.order_management__form-actions {
    margin-top: var(--spacing-lg);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

