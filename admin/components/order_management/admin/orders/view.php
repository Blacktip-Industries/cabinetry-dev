<?php
/**
 * Order Management Component - View Order
 * Order detail view with tabs
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/tags.php';
require_once __DIR__ . '/../../core/priority.php';
require_once __DIR__ . '/../../core/custom-fields.php';
require_once __DIR__ . '/../../core/fulfillment.php';
require_once __DIR__ . '/../../core/returns.php';
require_once __DIR__ . '/../../core/communication.php';
require_once __DIR__ . '/../../core/attachments.php';
require_once __DIR__ . '/../../core/audit-trail.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$orderId = $_GET['id'] ?? 0;
$activeTab = $_GET['tab'] ?? 'details';

// Get order
$order = null;
if (order_management_is_commerce_available() && $orderId > 0) {
    $conn = order_management_get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
}

if (!$order) {
    header('Location: ' . order_management_get_component_admin_url() . '/orders/index.php');
    exit;
}

// Get order data
$orderItems = [];
$orderTags = order_management_get_order_tags($orderId);
$orderPriority = order_management_get_order_priority($orderId);
$customFields = order_management_get_order_custom_fields($orderId);
$fulfillments = order_management_get_order_fulfillments($orderId);
$returns = order_management_get_order_returns($orderId);
$communications = order_management_get_order_communications($orderId);
$attachments = order_management_get_order_attachments($orderId);
$auditLog = order_management_get_order_audit_log($orderId);

// Get order items
if (order_management_is_commerce_available()) {
    $conn = order_management_get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM commerce_order_items WHERE order_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orderItems[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Order: #' . ($order['order_number'] ?? $orderId);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="order_management__header-actions">
            <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/edit.php?id=<?php echo $orderId; ?>" class="btn btn-primary">Edit Order</a>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/index.php" class="btn btn-secondary">Back to Orders</a>
        </div>
    </div>
    
    <!-- Order Summary -->
    <div class="order_management__order-summary">
        <div class="order_management__summary-card">
            <h3>Status</h3>
            <span class="order_management__badge order_management__badge--<?php echo htmlspecialchars($order['status'] ?? 'pending'); ?>">
                <?php echo ucfirst(htmlspecialchars($order['status'] ?? 'pending')); ?>
            </span>
        </div>
        <div class="order_management__summary-card">
            <h3>Total Amount</h3>
            <p class="order_management__summary-value">$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></p>
        </div>
        <div class="order_management__summary-card">
            <h3>Date</h3>
            <p><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></p>
        </div>
        <?php if ($orderPriority): ?>
            <div class="order_management__summary-card">
                <h3>Priority</h3>
                <span class="order_management__badge" style="background: <?php echo htmlspecialchars($orderPriority['color']); ?>">
                    <?php echo htmlspecialchars($orderPriority['name']); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Tabs -->
    <div class="order_management__tabs">
        <a href="?id=<?php echo $orderId; ?>&tab=details" class="order_management__tab <?php echo $activeTab === 'details' ? 'active' : ''; ?>">Details</a>
        <a href="?id=<?php echo $orderId; ?>&tab=items" class="order_management__tab <?php echo $activeTab === 'items' ? 'active' : ''; ?>">Items</a>
        <a href="?id=<?php echo $orderId; ?>&tab=fulfillment" class="order_management__tab <?php echo $activeTab === 'fulfillment' ? 'active' : ''; ?>">Fulfillment</a>
        <a href="?id=<?php echo $orderId; ?>&tab=returns" class="order_management__tab <?php echo $activeTab === 'returns' ? 'active' : ''; ?>">Returns</a>
        <a href="?id=<?php echo $orderId; ?>&tab=communication" class="order_management__tab <?php echo $activeTab === 'communication' ? 'active' : ''; ?>">Communication</a>
        <a href="?id=<?php echo $orderId; ?>&tab=attachments" class="order_management__tab <?php echo $activeTab === 'attachments' ? 'active' : ''; ?>">Attachments</a>
        <a href="?id=<?php echo $orderId; ?>&tab=custom-fields" class="order_management__tab <?php echo $activeTab === 'custom-fields' ? 'active' : ''; ?>">Custom Fields</a>
        <a href="?id=<?php echo $orderId; ?>&tab=audit" class="order_management__tab <?php echo $activeTab === 'audit' ? 'active' : ''; ?>">Audit Log</a>
    </div>
    
    <!-- Tab Content -->
    <div class="order_management__tab-content">
        <?php if ($activeTab === 'details'): ?>
            <div class="order_management__section">
                <h2>Order Details</h2>
                <dl class="order_management__details-list">
                    <dt>Order Number:</dt>
                    <dd>#<?php echo htmlspecialchars($order['order_number'] ?? $orderId); ?></dd>
                    
                    <dt>Customer ID:</dt>
                    <dd><?php echo $order['customer_id'] ?? 'Guest'; ?></dd>
                    
                    <dt>Status:</dt>
                    <dd>
                        <span class="order_management__badge order_management__badge--<?php echo htmlspecialchars($order['status'] ?? 'pending'); ?>">
                            <?php echo ucfirst(htmlspecialchars($order['status'] ?? 'pending')); ?>
                        </span>
                    </dd>
                    
                    <dt>Total Amount:</dt>
                    <dd>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></dd>
                    
                    <dt>Created:</dt>
                    <dd><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></dd>
                    
                    <?php if (!empty($orderTags)): ?>
                        <dt>Tags:</dt>
                        <dd>
                            <?php foreach ($orderTags as $tag): ?>
                                <span class="order_management__badge" style="background: <?php echo htmlspecialchars($tag['color']); ?>">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </span>
                            <?php endforeach; ?>
                        </dd>
                    <?php endif; ?>
                </dl>
            </div>
            
        <?php elseif ($activeTab === 'items'): ?>
            <div class="order_management__section">
                <h2>Order Items</h2>
                <?php if (empty($orderItems)): ?>
                    <p class="order_management__empty-state">No items in this order</p>
                <?php else: ?>
                    <table class="order_management__table">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?php echo $item['product_id']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
                                    <td>$<?php echo number_format($item['total_price'] ?? 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        <?php elseif ($activeTab === 'fulfillment'): ?>
            <div class="order_management__section">
                <h2>Fulfillments</h2>
                <?php if (empty($fulfillments)): ?>
                    <p class="order_management__empty-state">No fulfillments for this order</p>
                <?php else: ?>
                    <?php foreach ($fulfillments as $fulfillment): ?>
                        <div class="order_management__fulfillment-card">
                            <h3>Fulfillment #<?php echo $fulfillment['id']; ?></h3>
                            <p>Status: <span class="order_management__badge"><?php echo ucfirst($fulfillment['fulfillment_status']); ?></span></p>
                            <p>Created: <?php echo date('Y-m-d H:i', strtotime($fulfillment['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        <?php elseif ($activeTab === 'returns'): ?>
            <div class="order_management__section">
                <h2>Returns</h2>
                <?php if (empty($returns)): ?>
                    <p class="order_management__empty-state">No returns for this order</p>
                <?php else: ?>
                    <?php foreach ($returns as $return): ?>
                        <div class="order_management__return-card">
                            <h3><a href="<?php echo order_management_get_component_admin_url(); ?>/returns/view.php?id=<?php echo $return['id']; ?>">
                                <?php echo htmlspecialchars($return['return_number']); ?>
                            </a></h3>
                            <p>Status: <span class="order_management__badge"><?php echo ucfirst($return['status']); ?></span></p>
                            <p>Type: <?php echo ucfirst($return['return_type']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        <?php elseif ($activeTab === 'communication'): ?>
            <div class="order_management__section">
                <h2>Communication History</h2>
                <?php if (empty($communications)): ?>
                    <p class="order_management__empty-state">No communications for this order</p>
                <?php else: ?>
                    <?php foreach ($communications as $comm): ?>
                        <div class="order_management__communication-card">
                            <h4><?php echo htmlspecialchars($comm['subject']); ?></h4>
                            <p><strong>Type:</strong> <?php echo ucfirst($comm['communication_type']); ?> | 
                               <strong>Direction:</strong> <?php echo ucfirst($comm['direction']); ?></p>
                            <p><?php echo htmlspecialchars($comm['content']); ?></p>
                            <small><?php echo date('Y-m-d H:i:s', strtotime($comm['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        <?php elseif ($activeTab === 'attachments'): ?>
            <div class="order_management__section">
                <h2>Attachments</h2>
                <?php if (empty($attachments)): ?>
                    <p class="order_management__empty-state">No attachments for this order</p>
                <?php else: ?>
                    <table class="order_management__table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attachments as $attachment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attachment['file_name']); ?></td>
                                    <td><?php echo ucfirst($attachment['file_type']); ?></td>
                                    <td><?php echo number_format($attachment['file_size'] / 1024, 2); ?> KB</td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($attachment['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo order_management_get_component_admin_url(); ?>/attachments/view.php?id=<?php echo $attachment['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        <?php elseif ($activeTab === 'custom-fields'): ?>
            <div class="order_management__section">
                <h2>Custom Fields</h2>
                <?php if (empty($customFields)): ?>
                    <p class="order_management__empty-state">No custom fields for this order</p>
                <?php else: ?>
                    <dl class="order_management__details-list">
                        <?php foreach ($customFields as $field): ?>
                            <dt><?php echo htmlspecialchars($field['label']); ?>:</dt>
                            <dd><?php echo htmlspecialchars($field['field_value']); ?></dd>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
            </div>
            
        <?php elseif ($activeTab === 'audit'): ?>
            <div class="order_management__section">
                <h2>Audit Log</h2>
                <?php if (empty($auditLog)): ?>
                    <p class="order_management__empty-state">No audit log entries for this order</p>
                <?php else: ?>
                    <table class="order_management__table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>User</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLog as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['entity_type']); ?> #<?php echo $log['entity_id']; ?></td>
                                    <td><?php echo $log['user_id'] ?? 'System'; ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
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

.order_management__order-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__summary-card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__summary-card h3 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
}

.order_management__summary-value {
    margin: 0;
    font-size: var(--font-size-xl);
    font-weight: bold;
    color: var(--color-primary);
}

.order_management__tabs {
    display: flex;
    gap: var(--spacing-sm);
    border-bottom: var(--border-width) solid var(--color-border);
    margin-bottom: var(--spacing-lg);
}

.order_management__tab {
    padding: var(--spacing-sm) var(--spacing-md);
    text-decoration: none;
    color: var(--color-text);
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
}

.order_management__tab:hover {
    color: var(--color-primary);
}

.order_management__tab.active {
    color: var(--color-primary);
    border-bottom-color: var(--color-primary);
}

.order_management__tab-content {
    min-height: 300px;
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

.order_management__fulfillment-card,
.order_management__return-card,
.order_management__communication-card {
    background: var(--color-background-secondary);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-sm);
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

.order_management__badge--processing {
    background: var(--color-info-light);
    color: var(--color-info-dark);
}

.order_management__badge--completed {
    background: var(--color-success-light);
    color: var(--color-success-dark);
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

