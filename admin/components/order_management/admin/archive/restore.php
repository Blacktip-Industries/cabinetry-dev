<?php
/**
 * Order Management Component - Restore Archived Order
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

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = order_management_restore_archived_order($archiveId);
    
    if ($result['success']) {
        header('Location: ' . order_management_get_component_admin_url() . '/orders/view.php?id=' . $archive['order_id']);
        exit;
    } else {
        $error = $result['error'] ?? 'Failed to restore order';
    }
}

$orderData = json_decode($archive['order_data'], true);
$pageTitle = 'Restore Order: #' . ($orderData['order_number'] ?? $archive['order_id']);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/archive/view.php?id=<?php echo $archiveId; ?>" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="order_management__restore-form">
        <p>Are you sure you want to restore this archived order?</p>
        <p><strong>Order Number:</strong> #<?php echo htmlspecialchars($orderData['order_number'] ?? $archive['order_id']); ?></p>
        <p><strong>Total:</strong> $<?php echo number_format($orderData['total_amount'] ?? 0, 2); ?></p>
        
        <form method="POST">
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-primary">Restore Order</button>
                <a href="<?php echo order_management_get_component_admin_url(); ?>/archive/view.php?id=<?php echo $archiveId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
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

.order_management__alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius-md);
    margin-bottom: var(--spacing-md);
}

.order_management__alert--error {
    background: var(--color-error-light);
    color: var(--color-error-dark);
    border: var(--border-width) solid var(--color-error);
}

.order_management__restore-form {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    max-width: 600px;
}

.order_management__form-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-lg);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

