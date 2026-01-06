<?php
/**
 * Order Management Component - Create Custom Field
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/custom-fields.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = order_management_sanitize($_POST['name'] ?? '');
    $label = order_management_sanitize($_POST['label'] ?? $name);
    $fieldType = order_management_sanitize($_POST['field_type'] ?? 'text');
    $isRequired = isset($_POST['is_required']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $displayOrder = intval($_POST['display_order'] ?? 0);
    $defaultValue = order_management_sanitize($_POST['default_value'] ?? '');
    
    if (empty($name)) {
        $error = 'Field name is required';
    } else {
        $result = order_management_create_custom_field([
            'name' => $name,
            'label' => $label,
            'field_type' => $fieldType,
            'is_required' => $isRequired,
            'is_active' => $isActive,
            'display_order' => $displayOrder,
            'default_value' => $defaultValue
        ]);
        
        if ($result['success']) {
            header('Location: ' . order_management_get_component_admin_url() . '/custom-fields/index.php');
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to create field';
        }
    }
}

$pageTitle = 'Create Custom Field';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/custom-fields/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="order_management__form">
        <div class="order_management__form-group">
            <label for="name">Field Name *</label>
            <input type="text" id="name" name="name" required>
            <small>Internal name (lowercase, no spaces)</small>
        </div>
        
        <div class="order_management__form-group">
            <label for="label">Field Label *</label>
            <input type="text" id="label" name="label" required>
        </div>
        
        <div class="order_management__form-group">
            <label for="field_type">Field Type *</label>
            <select id="field_type" name="field_type" required>
                <option value="text">Text</option>
                <option value="textarea">Textarea</option>
                <option value="number">Number</option>
                <option value="email">Email</option>
                <option value="date">Date</option>
                <option value="select">Select</option>
                <option value="checkbox">Checkbox</option>
            </select>
        </div>
        
        <div class="order_management__form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" value="0">
        </div>
        
        <div class="order_management__form-group">
            <label for="default_value">Default Value</label>
            <input type="text" id="default_value" name="default_value">
        </div>
        
        <div class="order_management__form-group">
            <label>
                <input type="checkbox" name="is_required" value="1"> Required
            </label>
        </div>
        
        <div class="order_management__form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" checked> Active
            </label>
        </div>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Create Field</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/custom-fields/index.php" class="btn btn-secondary">Cancel</a>
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
.order_management__form-group select {
    width: 100%;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
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

