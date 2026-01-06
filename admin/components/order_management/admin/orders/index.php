<?php
/**
 * Order Management Component - Orders List
 * Main orders list with filters and search
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/tags.php';
require_once __DIR__ . '/../../core/priority.php';
require_once __DIR__ . '/../../core/search.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Orders Management';

// Get filters
$searchQuery = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$tag = $_GET['tag'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$limit = intval($_GET['limit'] ?? 50);
$offset = intval($_GET['offset'] ?? 0);

// Build filters
$filters = [];
if ($status) $filters['status'] = $status;
if ($dateFrom) $filters['date_from'] = $dateFrom;
if ($dateTo) $filters['date_to'] = $dateTo;

// Search orders
$orders = [];
$total = 0;
if (order_management_is_commerce_available()) {
    $searchResult = order_management_search_orders($searchQuery, $filters, $limit, $offset);
    if ($searchResult['success']) {
        $orders = $searchResult['data'];
        $total = $searchResult['total'];
    }
}

// Get tags for filter
$tags = order_management_get_tags();

// Get priorities for filter
$priorities = order_management_get_priority_levels();

// Get order tags and priorities
$orderTags = [];
$orderPriorities = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    foreach ($orderIds as $orderId) {
        $orderTags[$orderId] = order_management_get_order_tags($orderId);
        $orderPriorities[$orderId] = order_management_get_order_priority($orderId);
    }
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/create.php" class="btn btn-primary">Create Order</a>
    </div>
    
    <!-- Filters -->
    <div class="order_management__filters">
        <form method="GET" class="order_management__filter-form">
            <div class="order_management__form-group">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Order number, customer, etc.">
            </div>
            
            <div class="order_management__form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <?php if (!empty($priorities)): ?>
                <div class="order_management__form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="">All Priorities</option>
                        <?php foreach ($priorities as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $priority == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($tags)): ?>
                <div class="order_management__form-group">
                    <label for="tag">Tag</label>
                    <select id="tag" name="tag">
                        <option value="">All Tags</option>
                        <?php foreach ($tags as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo $tag == $t['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
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
                <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/index.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Orders List -->
    <div class="order_management__table-container">
        <table class="order_management__table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Tags</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="order_management__empty-state">No orders found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $order['id']; ?>">
                                    #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $customerId = $order['customer_id'] ?? null;
                                echo $customerId ? "Customer #{$customerId}" : 'Guest';
                                ?>
                            </td>
                            <td>
                                <span class="order_management__badge order_management__badge--<?php echo htmlspecialchars($order['status'] ?? 'pending'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'] ?? 'pending')); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($orderPriorities[$order['id']])): ?>
                                    <span class="order_management__badge" style="background: <?php echo htmlspecialchars($orderPriorities[$order['id']]['color'] ?? '#007bff'); ?>">
                                        <?php echo htmlspecialchars($orderPriorities[$order['id']]['name']); ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($orderTags[$order['id']])): ?>
                                    <?php foreach ($orderTags[$order['id']] as $tag): ?>
                                        <span class="order_management__badge" style="background: <?php echo htmlspecialchars($tag['color']); ?>">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/edit.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total > $limit): ?>
        <div class="order_management__pagination">
            <?php
            $currentPage = floor($offset / $limit) + 1;
            $totalPages = ceil($total / $limit);
            $prevOffset = max(0, $offset - $limit);
            $nextOffset = min($total - $limit, $offset + $limit);
            ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => $prevOffset])); ?>" class="btn btn-secondary" <?php echo $offset <= 0 ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>Previous</a>
            <span>Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?> (<?php echo $total; ?> total)</span>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => $nextOffset])); ?>" class="btn btn-secondary" <?php echo $offset >= $total - $limit ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>Next</a>
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

.order_management__form-group input,
.order_management__form-group select {
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
    margin-bottom: var(--spacing-lg);
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
    margin-right: var(--spacing-xs);
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

.order_management__badge--cancelled {
    background: var(--color-error-light);
    color: var(--color-error-dark);
}

.order_management__empty-state {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-text-secondary);
}

.order_management__pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
}

.btn-sm {
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: var(--font-size-sm);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

