<?php
/**
 * Order Management Component - View Collection Details
 * View collection details for an order
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-management.php';

// Check permissions
if (!access_has_permission('order_management_collection_view')) {
    access_denied();
}

$orderId = $_GET['order_id'] ?? null;

if (!$orderId || !function_exists('commerce_get_order')) {
    header('Location: index.php');
    exit;
}

$order = commerce_get_order($orderId);
if (!$order) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Collection Details - ' . htmlspecialchars($order['order_number']);
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="set-collection-window.php?order_id=<?php echo $orderId; ?>" class="btn btn-primary">Set Collection Window</a>
        <a href="index.php" class="btn btn-secondary">Back to Collection Management</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Collection Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Collection Status</th>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $order['collection_status'] === 'completed' ? 'success' : 
                                        ($order['collection_status'] === 'confirmed' ? 'info' : 'warning'); 
                                ?>">
                                    <?php echo htmlspecialchars($order['collection_status'] ?? 'pending'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Collection Window</th>
                            <td>
                                <?php if ($order['collection_window_start']): ?>
                                    <?php echo date('Y-m-d H:i', strtotime($order['collection_window_start'])); ?> - 
                                    <?php echo date('Y-m-d H:i', strtotime($order['collection_window_end'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Confirmed</th>
                            <td>
                                <?php if ($order['collection_confirmed_at']): ?>
                                    <?php echo date('Y-m-d H:i', strtotime($order['collection_confirmed_at'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not confirmed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Early Bird</th>
                            <td>
                                <?php if ($order['collection_early_bird']): ?>
                                    <span class="badge badge-info">Yes</span>
                                    <?php if ($order['collection_early_bird_charge'] > 0): ?>
                                        <br><small>Charge: $<?php echo number_format($order['collection_early_bird_charge'], 2); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>After Hours</th>
                            <td>
                                <?php if ($order['collection_after_hours']): ?>
                                    <span class="badge badge-info">Yes</span>
                                    <?php if ($order['collection_after_hours_charge'] > 0): ?>
                                        <br><small>Charge: $<?php echo number_format($order['collection_after_hours_charge'], 2); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Payment Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Payment Due</th>
                            <td>$<?php echo number_format($order['collection_payment_due'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Payment Received</th>
                            <td>$<?php echo number_format($order['collection_payment_received'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Payment Method</th>
                            <td><?php echo htmlspecialchars($order['collection_payment_method'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php if ($order['collection_payment_receipt_number']): ?>
                        <tr>
                            <th>Receipt Number</th>
                            <td><?php echo htmlspecialchars($order['collection_payment_receipt_number']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

