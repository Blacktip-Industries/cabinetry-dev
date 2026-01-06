<?php
/**
 * Order Management Component - Create Template
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

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = order_management_sanitize($_POST['name'] ?? '');
    $description = order_management_sanitize($_POST['description'] ?? '');
    $templateData = json_decode($_POST['template_data'] ?? '{}', true);
    
    if (empty($name)) {
        $error = 'Template name is required';
    } else {
        $result = order_management_create_template([
            'name' => $name,
            'description' => $description,
            'template_data' => $templateData
        ]);
        
        if ($result['success']) {
            header('Location: ' . order_management_get_component_admin_url() . '/templates/view.php?id=' . $result['template_id']);
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to create template';
        }
    }
}

$pageTitle = 'Create Order Template';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/templates/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="order_management__form">
        <div class="order_management__form-group">
            <label for="name">Template Name *</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="order_management__form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"></textarea>
        </div>
        
        <div class="order_management__form-group">
            <label for="template_data">Template Data (JSON)</label>
            <textarea id="template_data" name="template_data" rows="10" placeholder='{"customer_id": null, "status": "pending", ...}'></textarea>
            <small>Enter order data as JSON. This will be used as default values when creating orders from this template.</small>
        </div>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Create Template</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/templates/index.php" class="btn btn-secondary">Cancel</a>
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
    max-width: 800px;
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
.order_management__form-group textarea {
    width: 100%;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
    font-family: monospace;
}

.order_management__form-group small {
    display: block;
    margin-top: var(--spacing-xs);
    color: var(--color-text-secondary);
    font-size: var(--font-size-sm);
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

