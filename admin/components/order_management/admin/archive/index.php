<?php
/**
 * Order Management Component - Archived Orders List
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

$pageTitle = 'Archived Orders';

// Get filters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$filters = [];
if ($dateFrom) $filters['date_from'] = $dateFrom;
if ($dateTo) $filters['date_to'] = $dateTo;

// Get archived orders
$archivedOrders = order_management_get_archived_orders($filters);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    
    <!-- Filters -->
    <div class="order_management__filters">
        <form method="GET" class="order_management__filter-form">
            <div class="order_management__form-group">
                <label for="date_from">From Date</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="order_management__form-group">
                <label for="date_to">To Date</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo order_management_get_component_admin_url(); ?>/archive/index.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Archived Orders List -->
    <?php if (empty($archivedOrders)): ?>
        <div class="order_management__empty-state">
            <p>No archived orders found</p>
        </div>
    <?php else: ?>
        <div class="order_management__table-container">
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Number</th>
                        <th>Total</th>
                        <th>Archived Date</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archivedOrders as $archive): ?>
                        <?php $orderData = $archive['order_data']; ?>
                        <tr>
                            <td><?php echo $archive['order_id']; ?></td>
                            <td>#<?php echo htmlspecialchars($orderData['order_number'] ?? $archive['order_id']); ?></td>
                            <td>$<?php echo number_format($orderData['total_amount'] ?? 0, 2); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($archive['archived_at'])); ?></td>
                            <td><?php echo htmlspecialchars($archive['archive_reason'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/archive/view.php?id=<?php echo $archive['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/archive/restore.php?id=<?php echo $archive['id']; ?>" class="btn btn-sm btn-primary">Restore</a>
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
    margin-bottom: var(--spacing-lg);
}

.order_management__filters {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    align-items: end;
}

.order_management__form-group {
    display: flex;
    flex-direction: column;
}

.order_management__form-group label {
    margin-bottom: var(--spacing-xs);
    font-weight: bold;
    font-size: var(--font-size-sm);
}

.order_management__form-group input {
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
}

.order_management__form-actions {
    display: flex;
    gap: var(--spacing-sm);
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

.order_management__empty-state {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-text-secondary);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

