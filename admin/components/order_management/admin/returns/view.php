<?php
/**
 * Order Management Component - View Return
 * Admin interface to view return details
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/returns.php';
require_once __DIR__ . '/../../core/refunds.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$returnId = $_GET['id'] ?? 0;
$return = order_management_get_return($returnId);

if (!$return) {
    header('Location: ' . order_management_get_component_admin_url() . '/returns/index.php');
    exit;
}

$returnItems = order_management_get_return_items($returnId);
$refunds = order_management_get_order_refunds($return['order_id']);

// Get related refund
$relatedRefund = null;
foreach ($refunds as $refund) {
    if ($refund['return_id'] == $returnId) {
        $relatedRefund = $refund;
        break;
    }
}

// Get order info
$orderInfo = null;
if (order_management_is_commerce_available()) {
    $conn = order_management_get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $return['order_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $orderInfo = $result->fetch_assoc();
    $stmt->close();
}

$pageTitle = 'Return: ' . $return['return_number'];

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/index.php" class="btn btn-secondary">Back to Returns</a>
    </div>
    
    <!-- Return Details -->
    <div class="order_management__details-grid">
        <div class="order_management__details-card">
            <h2>Return Information</h2>
            <dl class="order_management__details-list">
                <dt>Return Number:</dt>
                <dd><?php echo htmlspecialchars($return['return_number']); ?></dd>
                
                <dt>Status:</dt>
                <dd>
                    <span class="order_management__badge order_management__badge--<?php echo htmlspecialchars($return['status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($return['status'])); ?>
                    </span>
                </dd>
                
                <dt>Type:</dt>
                <dd>
                    <span class="order_management__badge order_management__badge--<?php echo htmlspecialchars($return['return_type']); ?>">
                        <?php echo ucfirst(htmlspecialchars($return['return_type'])); ?>
                    </span>
                </dd>
                
                <dt>Order:</dt>
                <dd>
                    <?php if ($orderInfo): ?>
                        <a href="<?php echo order_management_get_admin_url(); ?>/components/commerce/orders/view.php?id=<?php echo $return['order_id']; ?>">
                            #<?php echo htmlspecialchars($orderInfo['order_number'] ?? $return['order_id']); ?>
                        </a>
                    <?php else: ?>
                        #<?php echo $return['order_id']; ?>
                    <?php endif; ?>
                </dd>
                
                <dt>Reason:</dt>
                <dd><?php echo htmlspecialchars($return['reason'] ?? 'N/A'); ?></dd>
                
                <dt>Created:</dt>
                <dd><?php echo date('Y-m-d H:i:s', strtotime($return['created_at'])); ?></dd>
                
                <?php if ($return['approved_at']): ?>
                    <dt>Approved:</dt>
                    <dd><?php echo date('Y-m-d H:i:s', strtotime($return['approved_at'])); ?></dd>
                <?php endif; ?>
            </dl>
        </div>
        
        <?php if ($relatedRefund): ?>
            <div class="order_management__details-card">
                <h2>Refund Information</h2>
                <dl class="order_management__details-list">
                    <dt>Refund Amount:</dt>
                    <dd>$<?php echo number_format($relatedRefund['refund_amount'], 2); ?></dd>
                    
                    <dt>Refund Method:</dt>
                    <dd><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($relatedRefund['refund_method']))); ?></dd>
                    
                    <dt>Status:</dt>
                    <dd>
                        <span class="order_management__badge order_management__badge--<?php echo htmlspecialchars($relatedRefund['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($relatedRefund['status'])); ?>
                        </span>
                    </dd>
                    
                    <?php if ($relatedRefund['processed_at']): ?>
                        <dt>Processed:</dt>
                        <dd><?php echo date('Y-m-d H:i:s', strtotime($relatedRefund['processed_at'])); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Return Items -->
    <div class="order_management__section">
        <h2>Return Items</h2>
        <?php if (empty($returnItems)): ?>
            <p class="order_management__empty-state">No items in this return</p>
        <?php else: ?>
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Condition</th>
                        <th>Quantity</th>
                        <th>Disposition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returnItems as $item): ?>
                        <tr>
                            <td>
                                <?php
                                if ($item['product_id']) {
                                    echo "Product ID: " . $item['product_id'];
                                    if ($item['variant_id']) {
                                        echo " (Variant: " . $item['variant_id'] . ")";
                                    }
                                } else {
                                    echo "Order Item ID: " . $item['order_item_id'];
                                }
                                ?>
                            </td>
                            <td><?php echo ucfirst(htmlspecialchars($item['condition'])); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($item['disposition'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Actions -->
    <?php if ($return['status'] === 'pending'): ?>
        <div class="order_management__actions">
            <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/approve.php?id=<?php echo $return['id']; ?>" class="btn btn-success">Approve Return</a>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/reject.php?id=<?php echo $return['id']; ?>" class="btn btn-danger">Reject Return</a>
        </div>
    <?php elseif ($return['status'] === 'approved' && $return['return_type'] === 'refund' && (!$relatedRefund || $relatedRefund['status'] !== 'completed')): ?>
        <div class="order_management__actions">
            <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/process.php?id=<?php echo $return['id']; ?>" class="btn btn-primary">Process Return</a>
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

.order_management__details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__details-card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__details-card h2 {
    margin: 0 0 var(--spacing-md) 0;
    font-size: var(--font-size-lg);
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

.order_management__section {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__section h2 {
    margin: 0 0 var(--spacing-md) 0;
    font-size: var(--font-size-lg);
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

.order_management__badge--pending {
    background: var(--color-warning-light);
    color: var(--color-warning-dark);
}

.order_management__badge--approved {
    background: var(--color-success-light);
    color: var(--color-success-dark);
}

.order_management__badge--processing {
    background: var(--color-info-light);
    color: var(--color-info-dark);
}

.order_management__badge--completed {
    background: var(--color-success-light);
    color: var(--color-success-dark);
}

.order_management__badge--rejected {
    background: var(--color-error-light);
    color: var(--color-error-dark);
}

.order_management__badge--refund {
    background: var(--color-primary-light);
    color: var(--color-primary-dark);
}

.order_management__actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-lg);
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

