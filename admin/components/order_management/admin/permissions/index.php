<?php
/**
 * Order Management Component - Permissions Management
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

$pageTitle = 'Permissions Management';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    
    <div class="order_management__section">
        <h2>Permission Management</h2>
        <p>Manage user permissions and role-based access control for order management features.</p>
        
        <div class="order_management__permission-links">
            <a href="<?php echo order_management_get_component_admin_url(); ?>/permissions/roles.php" class="order_management__permission-card">
                <h3>Roles</h3>
                <p>Manage roles and role permissions</p>
            </a>
            
            <a href="<?php echo order_management_get_component_admin_url(); ?>/permissions/users.php" class="order_management__permission-card">
                <h3>User Permissions</h3>
                <p>Assign permissions to users</p>
            </a>
        </div>
    </div>
</div>

<style>
.order_management__container {
    padding: var(--spacing-lg);
}

.order_management__header {
    margin-bottom: var(--spacing-lg);
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

.order_management__permission-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
}

.order_management__permission-card {
    background: var(--color-background-secondary);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    text-decoration: none;
    color: var(--color-text);
    display: block;
}

.order_management__permission-card:hover {
    background: var(--color-background);
    border-color: var(--color-primary);
}

.order_management__permission-card h3 {
    margin: 0 0 var(--spacing-sm) 0;
    color: var(--color-primary);
}

.order_management__permission-card p {
    margin: 0;
    color: var(--color-text-secondary);
    font-size: var(--font-size-sm);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

