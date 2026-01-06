<?php
/**
 * Order Management Component - Order Audit Log
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/audit-trail.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? 0;

if ($orderId <= 0) {
    header('Location: ' . order_management_get_component_admin_url() . '/orders/index.php');
    exit;
}

// Get audit log for order
$auditLog = order_management_get_order_audit_log($orderId);

$pageTitle = 'Audit Log: Order #' . $orderId;

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Back to Order</a>
    </div>
    
    <?php if (empty($auditLog)): ?>
        <div class="order_management__empty-state">
            <p>No audit log entries for this order</p>
        </div>
    <?php else: ?>
        <div class="order_management__table-container">
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>User</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLog as $log): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['entity_type']); ?> #<?php echo $log['entity_id']; ?></td>
                            <td><?php echo $log['user_id'] ?? 'System'; ?></td>
                            <td>
                                <?php if (!empty($log['changes'])): ?>
                                    <pre class="order_management__code-inline"><?php echo htmlspecialchars(json_encode($log['changes'], JSON_PRETTY_PRINT)); ?></pre>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
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

.order_management__code-inline {
    background: var(--color-background-secondary);
    padding: var(--spacing-xs);
    border-radius: var(--border-radius-sm);
    font-family: monospace;
    font-size: var(--font-size-sm);
    max-width: 400px;
    overflow-x: auto;
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

