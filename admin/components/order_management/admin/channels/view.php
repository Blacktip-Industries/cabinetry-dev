<?php
/**
 * Order Management Component - View Channel
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

$channelId = $_GET['id'] ?? 0;
$channel = order_management_get_channel($channelId);

if (!$channel) {
    header('Location: ' . order_management_get_component_admin_url() . '/channels/index.php');
    exit;
}

// Get channel statistics
$stats = order_management_get_channel_statistics($channelId);

// Get orders for this channel
$orders = order_management_get_orders_by_channel($channelId, ['limit' => 50]);

$pageTitle = 'Channel: ' . htmlspecialchars($channel['name']);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="order_management__header-actions">
            <a href="<?php echo order_management_get_component_admin_url(); ?>/channels/edit.php?id=<?php echo $channelId; ?>" class="btn btn-primary">Edit</a>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/channels/index.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
    
    <div class="order_management__section">
        <h2>Channel Details</h2>
        <dl class="order_management__details-list">
            <dt>Name:</dt>
            <dd><?php echo htmlspecialchars($channel['name']); ?></dd>
            
            <dt>Type:</dt>
            <dd><?php echo ucfirst(htmlspecialchars($channel['channel_type'])); ?></dd>
            
            <dt>Status:</dt>
            <dd>
                <span class="order_management__badge <?php echo $channel['is_active'] ? 'order_management__badge--active' : 'order_management__badge--inactive'; ?>">
                    <?php echo $channel['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </dd>
            
            <dt>Created:</dt>
            <dd><?php echo date('Y-m-d H:i:s', strtotime($channel['created_at'])); ?></dd>
        </dl>
    </div>
    
    <div class="order_management__section">
        <h2>Statistics</h2>
        <div class="order_management__stats-grid">
            <div class="order_management__stat-card">
                <h3>Total Orders</h3>
                <p class="order_management__stat-value"><?php echo number_format($stats['total_orders'] ?? 0); ?></p>
            </div>
            <div class="order_management__stat-card">
                <h3>Total Revenue</h3>
                <p class="order_management__stat-value">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></p>
            </div>
            <div class="order_management__stat-card">
                <h3>Average Order Value</h3>
                <p class="order_management__stat-value">$<?php echo number_format($stats['avg_order_value'] ?? 0, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="order_management__section">
        <h2>Recent Orders</h2>
        <?php if (empty($orders)): ?>
            <p class="order_management__empty-state">No orders for this channel</p>
        <?php else: ?>
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $order['id']; ?>">#<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></a></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                            <td>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                            <td><span class="order_management__badge"><?php echo ucfirst($order['status'] ?? 'pending'); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
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

.order_management__section {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__section h2 {
    margin: 0 0 var(--spacing-md) 0;
}

.order_management__details-list {
    margin: 0;
    padding: 0;
}

.order_management__details-list dt {
    font-weight: bold;
    margin-top: var(--spacing-sm);
    color: var(--color-text-secondary);
}

.order_management__details-list dd {
    margin: var(--spacing-xs) 0 0 0;
    color: var(--color-text);
}

.order_management__stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
}

.order_management__stat-card {
    text-align: center;
}

.order_management__stat-card h3 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
}

.order_management__stat-value {
    margin: 0;
    font-size: var(--font-size-xl);
    font-weight: bold;
    color: var(--color-primary);
}

.order_management__table {
    width: 100%;
    border-collapse: collapse;
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
    color: var(--color-text-secondary);
    padding: var(--spacing-md);
    text-align: center;
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

