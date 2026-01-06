<?php
/**
 * Order Management Component - Custom Fields List
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

$pageTitle = 'Custom Fields';

// Get custom fields
$customFields = order_management_get_custom_fields();

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/custom-fields/create.php" class="btn btn-primary">Create Field</a>
    </div>
    
    <?php if (empty($customFields)): ?>
        <div class="order_management__empty-state">
            <p>No custom fields found. <a href="<?php echo order_management_get_component_admin_url(); ?>/custom-fields/create.php">Create your first field</a>.</p>
        </div>
    <?php else: ?>
        <div class="order_management__table-container">
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customFields as $field): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($field['name']); ?></td>
                            <td><?php echo htmlspecialchars($field['label']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($field['field_type'])); ?></td>
                            <td><?php echo $field['is_required'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <span class="order_management__badge <?php echo $field['is_active'] ? 'order_management__badge--active' : 'order_management__badge--inactive'; ?>">
                                    <?php echo $field['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/custom-fields/edit.php?id=<?php echo $field['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

.order_management__table-container {
    overflow-x: auto;
}

.order_management__table {
    width: 100%;
    border-collapse: collapse;
    background: var(--color-background);
}

.order_management__table th,
.order_management__table td {
    padding: var(--spacing-sm);
    text-align: left;
    border-bottom: var(--border-width) solid var(--color-border);
}

.order_management__table th {
    background: var(--color-background-secondary);
    font-weight: bold;
}

.order_management__badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.order_management__badge--active {
    background: var(--color-success-light);
    color: var(--color-success-dark);
}

.order_management__badge--inactive {
    background: var(--color-error-light);
    color: var(--color-error-dark);
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

