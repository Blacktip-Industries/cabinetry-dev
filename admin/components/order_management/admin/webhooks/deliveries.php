<?php
/**
 * Order Management Component - Webhook Delivery Logs
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/webhooks.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$webhookId = $_GET['id'] ?? 0;
$webhook = order_management_get_webhook($webhookId);

if (!$webhook) {
    header('Location: ' . order_management_get_component_admin_url() . '/webhooks/index.php');
    exit;
}

// Get delivery logs
$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('webhook_deliveries');
$query = "SELECT * FROM {$tableName} WHERE webhook_id = ? ORDER BY delivered_at DESC LIMIT 100";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $webhookId);
$stmt->execute();
$result = $stmt->get_result();
$deliveries = [];
while ($row = $result->fetch_assoc()) {
    $deliveries[] = $row;
}
$stmt->close();

$pageTitle = 'Webhook Deliveries: ' . htmlspecialchars($webhook['name']);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/webhooks/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if (empty($deliveries)): ?>
        <div class="order_management__empty-state">
            <p>No delivery logs found for this webhook</p>
        </div>
    <?php else: ?>
        <div class="order_management__table-container">
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>HTTP Code</th>
                        <th>Status</th>
                        <th>Delivered</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveries as $delivery): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($delivery['event']); ?></td>
                            <td><?php echo $delivery['http_code']; ?></td>
                            <td>
                                <span class="order_management__badge <?php echo $delivery['status'] === 'success' ? 'order_management__badge--success' : 'order_management__badge--error'; ?>">
                                    <?php echo ucfirst($delivery['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($delivery['delivered_at'])); ?></td>
                            <td>
                                <?php if ($delivery['error']): ?>
                                    <span class="order_management__error-text"><?php echo htmlspecialchars($delivery['error']); ?></span>
                                <?php else: ?>
                                    <span class="order_management__success-text">Success</span>
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

.order_management__badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.order_management__badge--success {
    background: var(--color-success-light);
    color: var(--color-success-dark);
}

.order_management__badge--error {
    background: var(--color-error-light);
    color: var(--color-error-dark);
}

.order_management__error-text {
    color: var(--color-error);
    font-size: var(--font-size-sm);
}

.order_management__success-text {
    color: var(--color-success);
    font-size: var(--font-size-sm);
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

