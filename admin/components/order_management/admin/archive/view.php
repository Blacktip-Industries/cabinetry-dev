<?php
/**
 * Order Management Component - View Archived Order
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/archiving.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$archiveId = $_GET['id'] ?? 0;

$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('archived_orders');
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $archiveId);
$stmt->execute();
$result = $stmt->get_result();
$archive = $result->fetch_assoc();
$stmt->close();

if (!$archive) {
    header('Location: ' . order_management_get_component_admin_url() . '/archive/index.php');
    exit;
}

$orderData = json_decode($archive['order_data'], true);

$pageTitle = 'Archived Order: #' . ($orderData['order_number'] ?? $archive['order_id']);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="order_management__header-actions">
            <a href="<?php echo order_management_get_component_admin_url(); ?>/archive/restore.php?id=<?php echo $archiveId; ?>" class="btn btn-primary">Restore Order</a>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/archive/index.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
    
    <div class="order_management__section">
        <h2>Archive Information</h2>
        <dl class="order_management__details-list">
            <dt>Archived Date:</dt>
            <dd><?php echo date('Y-m-d H:i:s', strtotime($archive['archived_at'])); ?></dd>
            
            <dt>Archive Reason:</dt>
            <dd><?php echo htmlspecialchars($archive['archive_reason'] ?? 'N/A'); ?></dd>
            
            <dt>Archived By:</dt>
            <dd><?php echo $archive['archived_by'] ?? 'System'; ?></dd>
        </dl>
    </div>
    
    <div class="order_management__section">
        <h2>Order Data</h2>
        <dl class="order_management__details-list">
            <dt>Order Number:</dt>
            <dd>#<?php echo htmlspecialchars($orderData['order_number'] ?? $archive['order_id']); ?></dd>
            
            <dt>Customer ID:</dt>
            <dd><?php echo $orderData['customer_id'] ?? 'Guest'; ?></dd>
            
            <dt>Status:</dt>
            <dd><?php echo ucfirst(htmlspecialchars($orderData['status'] ?? 'unknown')); ?></dd>
            
            <dt>Total Amount:</dt>
            <dd>$<?php echo number_format($orderData['total_amount'] ?? 0, 2); ?></dd>
            
            <dt>Created:</dt>
            <dd><?php echo date('Y-m-d H:i:s', strtotime($orderData['created_at'] ?? $archive['archived_at'])); ?></dd>
        </dl>
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
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

