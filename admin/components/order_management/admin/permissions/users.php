<?php
/**
 * Order Management Component - User Permissions
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/permissions.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'User Permissions';

// Get user permissions
$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('user_permissions');
$permissions = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM {$tableName} ORDER BY user_id ASC LIMIT 100");
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/permissions/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if (empty($permissions)): ?>
        <div class="order_management__empty-state">
            <p>No user permissions found</p>
        </div>
    <?php else: ?>
        <div class="order_management__table-container">
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Permission</th>
                        <th>Granted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $perm): ?>
                        <tr>
                            <td><?php echo $perm['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($perm['permission']); ?></td>
                            <td>
                                <span class="order_management__badge <?php echo $perm['is_granted'] ? 'order_management__badge--active' : 'order_management__badge--inactive'; ?>">
                                    <?php echo $perm['is_granted'] ? 'Yes' : 'No'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/permissions/edit-user.php?user_id=<?php echo $perm['user_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
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

