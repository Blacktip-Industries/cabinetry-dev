<?php
/**
 * Order Management Component - Create Channel
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/multichannel.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = order_management_sanitize($_POST['name'] ?? '');
    $channelType = order_management_sanitize($_POST['channel_type'] ?? 'web');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $config = json_decode($_POST['config'] ?? '{}', true);
    
    if (empty($name)) {
        $error = 'Channel name is required';
    } else {
        $result = order_management_create_channel([
            'name' => $name,
            'channel_type' => $channelType,
            'is_active' => $isActive,
            'config' => $config
        ]);
        
        if ($result['success']) {
            header('Location: ' . order_management_get_component_admin_url() . '/channels/view.php?id=' . $result['channel_id']);
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to create channel';
        }
    }
}

$pageTitle = 'Create Channel';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/channels/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="order_management__form">
        <div class="order_management__form-group">
            <label for="name">Channel Name *</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="order_management__form-group">
            <label for="channel_type">Channel Type *</label>
            <select id="channel_type" name="channel_type" required>
                <option value="web">Web</option>
                <option value="phone">Phone</option>
                <option value="in_store">In-Store</option>
                <option value="marketplace">Marketplace</option>
                <option value="api">API</option>
            </select>
        </div>
        
        <div class="order_management__form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" checked> Active
            </label>
        </div>
        
        <div class="order_management__form-group">
            <label for="config">Configuration (JSON)</label>
            <textarea id="config" name="config" rows="6" placeholder='{"api_key": "...", "endpoint": "..."}'></textarea>
        </div>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Create Channel</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/channels/index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
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

.order_management__alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius-md);
    margin-bottom: var(--spacing-md);
}

.order_management__alert--error {
    background: var(--color-error-light);
    color: var(--color-error-dark);
    border: var(--border-width) solid var(--color-error);
}

.order_management__form {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    max-width: 600px;
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
.order_management__form-group select,
.order_management__form-group textarea {
    width: 100%;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
}

.order_management__form-group textarea {
    font-family: monospace;
}

.order_management__form-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-lg);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

