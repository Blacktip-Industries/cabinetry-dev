<?php
/**
 * Order Management Component - Priority Levels List
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/priority.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Priority Levels';

// Get priority levels
$priorities = order_management_get_priority_levels();

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/priority/create.php" class="btn btn-primary">Create Priority</a>
    </div>
    
    <?php if (empty($priorities)): ?>
        <div class="order_management__empty-state">
            <p>No priority levels found. <a href="<?php echo order_management_get_component_admin_url(); ?>/priority/create.php">Create your first priority level</a>.</p>
        </div>
    <?php else: ?>
        <div class="order_management__table-container">
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Value</th>
                        <th>Color</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($priorities as $priority): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($priority['name']); ?></td>
                            <td><?php echo $priority['priority_value']; ?></td>
                            <td>
                                <span class="order_management__badge" style="background: <?php echo htmlspecialchars($priority['color'] ?? '#007bff'); ?>">
                                    <?php echo htmlspecialchars($priority['name']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/priority/edit.php?id=<?php echo $priority['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
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
    color: white;
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

