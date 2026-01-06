<?php
/**
 * Order Management Component - Channels List
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

$pageTitle = 'Multi-Channel Management';

// Get channels
$channels = order_management_get_channels();

// Get statistics for each channel
$channelStats = [];
foreach ($channels as $channel) {
    $channelStats[$channel['id']] = order_management_get_channel_statistics($channel['id']);
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/channels/create.php" class="btn btn-primary">Create Channel</a>
    </div>
    
    <?php if (empty($channels)): ?>
        <div class="order_management__empty-state">
            <p>No channels found. <a href="<?php echo order_management_get_component_admin_url(); ?>/channels/create.php">Create your first channel</a>.</p>
        </div>
    <?php else: ?>
        <div class="order_management__table-container">
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Orders</th>
                        <th>Revenue</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($channels as $channel): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($channel['name']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($channel['channel_type'])); ?></td>
                            <td>
                                <span class="order_management__badge <?php echo $channel['is_active'] ? 'order_management__badge--active' : 'order_management__badge--inactive'; ?>">
                                    <?php echo $channel['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo number_format($channelStats[$channel['id']]['total_orders'] ?? 0); ?></td>
                            <td>$<?php echo number_format($channelStats[$channel['id']]['total_revenue'] ?? 0, 2); ?></td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/channels/view.php?id=<?php echo $channel['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/channels/edit.php?id=<?php echo $channel['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
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

