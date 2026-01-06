<?php
/**
 * Order Management Component - View Communication Thread
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/communication.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? 0;

// Get communications for order
$communications = order_management_get_order_communications($orderId);

$pageTitle = 'Communication: Order #' . $orderId;

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="order_management__header-actions">
            <a href="<?php echo order_management_get_component_admin_url(); ?>/communication/create.php?order_id=<?php echo $orderId; ?>" class="btn btn-primary">Add Communication</a>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Back to Order</a>
        </div>
    </div>
    
    <?php if (empty($communications)): ?>
        <div class="order_management__empty-state">
            <p>No communications for this order</p>
        </div>
    <?php else: ?>
        <div class="order_management__communications-thread">
            <?php foreach ($communications as $comm): ?>
                <div class="order_management__communication-card order_management__communication-card--<?php echo $comm['direction']; ?>">
                    <div class="order_management__communication-header">
                        <h3><?php echo htmlspecialchars($comm['subject']); ?></h3>
                        <div class="order_management__communication-meta">
                            <span class="order_management__badge"><?php echo ucfirst($comm['communication_type']); ?></span>
                            <span class="order_management__badge"><?php echo ucfirst($comm['direction']); ?></span>
                        </div>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($comm['content'])); ?></p>
                    <small><?php echo date('Y-m-d H:i:s', strtotime($comm['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
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

.order_management__header-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.order_management__communications-thread {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.order_management__communication-card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__communication-card--inbound {
    border-left: 4px solid var(--color-primary);
}

.order_management__communication-card--outbound {
    border-left: 4px solid var(--color-success);
}

.order_management__communication-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: var(--spacing-sm);
}

.order_management__communication-header h3 {
    margin: 0;
}

.order_management__communication-meta {
    display: flex;
    gap: var(--spacing-xs);
    align-items: center;
    font-size: var(--font-size-sm);
}

.order_management__badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 500;
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

