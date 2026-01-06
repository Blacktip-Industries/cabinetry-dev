<?php
/**
 * Customer Queue Visibility Dashboard
 * Frontend page for customers to view their order queue position
 */

require_once __DIR__ . '/includes/frontend_layout.php';
require_once __DIR__ . '/admin/components/order_management/core/production-queue.php';
require_once __DIR__ . '/admin/components/order_management/core/customer-display-rules.php';
require_once __DIR__ . '/config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['account_id'])) {
    header('Location: login.php');
    exit;
}

$accountId = $_SESSION['account_id'];
$orderId = $_GET['order_id'] ?? null;

// Get customer's orders in queue
$queueItems = [];
if ($orderId) {
    $queueItem = order_management_get_queue_item_by_order($orderId);
    if ($queueItem && $queueItem['account_id'] == $accountId) {
        $queueItems[] = $queueItem;
    }
} else {
    // Get all orders for this customer
    $queueItems = order_management_get_customer_queue_items($accountId);
}

// Apply display rules to determine what information to show
$displayConfig = [];
foreach ($queueItems as $item) {
    $displayConfig[$item['order_id']] = order_management_get_customer_display_config($item['order_id'], $accountId);
}

startFrontendLayout('My Orders Queue', 'customer_queue');
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>My Orders Queue</h2>
        <p class="text-muted">Track your orders in the production queue</p>
    </div>
</div>

<div class="customer_queue__container">
    <?php if (empty($queueItems)): ?>
        <div class="alert alert-info">
            <p>You have no orders in the production queue.</p>
        </div>
    <?php else: ?>
        <?php foreach ($queueItems as $item): ?>
            <?php $config = $displayConfig[$item['order_id']] ?? []; ?>
            <div class="card customer_queue__item">
                <div class="card-header">
                    <h5>Order #<?php echo htmlspecialchars($item['order_number'] ?? $item['order_id']); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($config['show_queue_position'] ?? true): ?>
                        <div class="customer_queue__position">
                            <strong>Queue Position:</strong> 
                            <span class="badge badge-primary"><?php echo htmlspecialchars($item['queue_position']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($config['show_estimated_completion'] ?? true && $item['estimated_completion_date']): ?>
                        <div class="customer_queue__completion">
                            <strong>Estimated Completion:</strong> 
                            <?php echo date('F j, Y', strtotime($item['estimated_completion_date'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($config['show_need_by_date'] ?? true && $item['need_by_date']): ?>
                        <div class="customer_queue__need_by">
                            <strong>Need By Date:</strong> 
                            <?php echo date('F j, Y', strtotime($item['need_by_date'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($config['show_rush_order'] ?? true && $item['is_rush_order']): ?>
                        <div class="customer_queue__rush">
                            <span class="badge badge-warning">Rush Order</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($config['show_status'] ?? true): ?>
                        <div class="customer_queue__status">
                            <strong>Status:</strong> 
                            <span class="badge badge-info"><?php echo htmlspecialchars(str_replace('_', ' ', $item['status'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($config['show_delays'] ?? true): ?>
                        <?php
                        $delays = order_management_get_queue_delays($item['queue_id']);
                        if (!empty($delays)):
                        ?>
                            <div class="customer_queue__delays">
                                <strong>Delays:</strong>
                                <ul>
                                    <?php foreach ($delays as $delay): ?>
                                        <li>
                                            <?php echo htmlspecialchars($delay['reason'] ?? 'Delay'); ?>
                                            (<?php echo $delay['delay_days']; ?> days)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php endFrontendLayout(); ?>

