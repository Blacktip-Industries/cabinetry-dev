<?php
/**
 * Order Management Component - Production Queue Management
 * Main production queue view with drag-and-drop reordering
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/production-queue.php';

// Check permissions
if (!access_has_permission('order_management_queue_manage')) {
    access_denied();
}

$queueType = $_GET['type'] ?? 'all'; // 'rush', 'normal', or 'all'
$conn = order_management_get_db_connection();

// Get queues
$rushQueue = [];
$normalQueue = [];

if ($conn) {
    if ($queueType === 'all' || $queueType === 'rush') {
        $rushQueue = order_management_get_production_queue('rush');
    }
    if ($queueType === 'all' || $queueType === 'normal') {
        $normalQueue = order_management_get_production_queue('normal');
    }
}

$pageTitle = 'Production Queue';
include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <select id="queue_type_filter" onchange="filterQueue(this.value)">
            <option value="all" <?php echo $queueType === 'all' ? 'selected' : ''; ?>>All Queues</option>
            <option value="rush" <?php echo $queueType === 'rush' ? 'selected' : ''; ?>>Rush Orders</option>
            <option value="normal" <?php echo $queueType === 'normal' ? 'selected' : ''; ?>>Normal Orders</option>
        </select>
        <a href="delay-reasons.php" class="btn btn-secondary">Manage Delay Reasons</a>
        <a href="ordering-rules.php" class="btn btn-secondary">Queue Ordering Rules</a>
    </div>
</div>

<div class="content-body">
    <?php if ($queueType === 'all' || $queueType === 'rush'): ?>
        <div class="queue-section">
            <h2>Rush Orders Queue</h2>
            <?php if (empty($rushQueue)): ?>
                <div class="alert alert-info">No rush orders in queue</div>
            <?php else: ?>
                <table class="table table-striped queue-table" id="rush_queue_table">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Need By Date</th>
                            <th>Paid At</th>
                            <th>Payment Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rush_queue_body">
                        <?php foreach ($rushQueue as $item): ?>
                            <?php
                            // Get order details
                            $order = null;
                            if (function_exists('commerce_get_order')) {
                                $order = commerce_get_order($item['order_id']);
                            }
                            ?>
                            <tr data-queue-id="<?php echo $item['id']; ?>" data-order-id="<?php echo $item['order_id']; ?>" data-position="<?php echo $item['queue_position']; ?>">
                                <td>
                                    <span class="position-badge"><?php echo $item['queue_position']; ?></span>
                                    <?php if ($item['is_locked']): ?>
                                        <span class="badge badge-warning" title="Locked at position <?php echo $item['locked_position']; ?>">🔒</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($order && $order['need_by_date']): ?>
                                        <?php echo date('Y-m-d H:i', strtotime($order['need_by_date'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($item['paid_at'])); ?></td>
                                <td>
                                    <span class="payment-order-badge"><?php echo $item['payment_order_position']; ?></span>
                                    <?php if ($item['queue_position'] != $item['payment_order_position']): ?>
                                        <span class="badge badge-info" title="Moved from payment order position <?php echo $item['payment_order_position']; ?>">
                                            <?php echo $item['queue_position'] < $item['payment_order_position'] ? '↑' : '↓'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    // Check for delays
                                    $delayStmt = $conn->prepare("SELECT * FROM order_management_queue_delays WHERE queue_id = ? AND delay_resolved_at IS NULL LIMIT 1");
                                    if ($delayStmt) {
                                        $delayStmt->bind_param("i", $item['id']);
                                        $delayStmt->execute();
                                        $delayResult = $delayStmt->get_result();
                                        $delay = $delayResult->fetch_assoc();
                                        $delayStmt->close();
                                        
                                        if ($delay) {
                                            echo '<span class="badge badge-warning">Delayed</span>';
                                        } else {
                                            echo '<span class="badge badge-success">Active</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="move-job.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">Move</a>
                                    <?php if ($item['is_locked']): ?>
                                        <a href="lock-order.php?unlock=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Unlock this order?')">Unlock</a>
                                    <?php else: ?>
                                        <a href="lock-order.php?lock=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">Lock</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($queueType === 'all' || $queueType === 'normal'): ?>
        <div class="queue-section">
            <h2>Normal Orders Queue</h2>
            <?php if (empty($normalQueue)): ?>
                <div class="alert alert-info">No normal orders in queue</div>
            <?php else: ?>
                <table class="table table-striped queue-table" id="normal_queue_table">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Need By Date</th>
                            <th>Paid At</th>
                            <th>Payment Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="normal_queue_body">
                        <?php foreach ($normalQueue as $item): ?>
                            <?php
                            // Get order details
                            $order = null;
                            if (function_exists('commerce_get_order')) {
                                $order = commerce_get_order($item['order_id']);
                            }
                            ?>
                            <tr data-queue-id="<?php echo $item['id']; ?>" data-order-id="<?php echo $item['order_id']; ?>" data-position="<?php echo $item['queue_position']; ?>">
                                <td>
                                    <span class="position-badge"><?php echo $item['queue_position']; ?></span>
                                    <?php if ($item['is_locked']): ?>
                                        <span class="badge badge-warning" title="Locked at position <?php echo $item['locked_position']; ?>">🔒</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($order && $order['need_by_date']): ?>
                                        <?php echo date('Y-m-d H:i', strtotime($order['need_by_date'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($item['paid_at'])); ?></td>
                                <td>
                                    <span class="payment-order-badge"><?php echo $item['payment_order_position']; ?></span>
                                    <?php if ($item['queue_position'] != $item['payment_order_position']): ?>
                                        <span class="badge badge-info" title="Moved from payment order position <?php echo $item['payment_order_position']; ?>">
                                            <?php echo $item['queue_position'] < $item['payment_order_position'] ? '↑' : '↓'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    // Check for delays
                                    $delayStmt = $conn->prepare("SELECT * FROM order_management_queue_delays WHERE queue_id = ? AND delay_resolved_at IS NULL LIMIT 1");
                                    if ($delayStmt) {
                                        $delayStmt->bind_param("i", $item['id']);
                                        $delayStmt->execute();
                                        $delayResult = $delayStmt->get_result();
                                        $delay = $delayResult->fetch_assoc();
                                        $delayStmt->close();
                                        
                                        if ($delay) {
                                            echo '<span class="badge badge-warning">Delayed</span>';
                                        } else {
                                            echo '<span class="badge badge-success">Active</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="move-job.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">Move</a>
                                    <?php if ($item['is_locked']): ?>
                                        <a href="lock-order.php?unlock=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Unlock this order?')">Unlock</a>
                                    <?php else: ?>
                                        <a href="lock-order.php?lock=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">Lock</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function filterQueue(type) {
    window.location.href = 'index.php?type=' + type;
}
</script>

<style>
.queue-section {
    margin-bottom: var(--spacing-xl);
}
.position-badge {
    display: inline-block;
    width: 30px;
    height: 30px;
    line-height: 30px;
    text-align: center;
    background: var(--color-primary);
    color: white;
    border-radius: 50%;
    font-weight: bold;
}
.payment-order-badge {
    display: inline-block;
    padding: 2px 8px;
    background: var(--color-secondary);
    color: white;
    border-radius: var(--border-radius-sm);
    font-size: 0.85em;
}
</style>

<?php
include __DIR__ . '/../../includes/footer.php';
?>

