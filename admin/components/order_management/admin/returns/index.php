<?php
/**
 * Order Management Component - Returns List
 * Admin interface to view and manage returns
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

$pageTitle = 'Returns Management';
$currentStatus = $_GET['status'] ?? 'all';
$currentType = $_GET['type'] ?? 'all';

// Get returns
$returns = [];
if ($currentStatus === 'all') {
    $conn = order_management_get_db_connection();
    $tableName = order_management_get_table_name('returns');
    $where = [];
    $params = [];
    $types = '';
    
    if ($currentType !== 'all') {
        $where[] = "return_type = ?";
        $params[] = $currentType;
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT 100";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $returns[] = $row;
        }
        $stmt->close();
    } else {
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $returns[] = $row;
        }
    }
} else {
    $returns = order_management_get_returns_by_status($currentStatus, ['return_type' => $currentType !== 'all' ? $currentType : null]);
}

// Get statistics
$stats = order_management_get_return_stats();

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/create.php" class="btn btn-primary">Create Return</a>
    </div>
    
    <!-- Statistics -->
    <div class="order_management__stats-grid">
        <div class="order_management__stat-card">
            <h3>Total Returns</h3>
            <p class="order_management__stat-value"><?php echo number_format($stats['total_returns']); ?></p>
        </div>
        <div class="order_management__stat-card">
            <h3>Pending Returns</h3>
            <p class="order_management__stat-value"><?php echo number_format($stats['pending_returns']); ?></p>
        </div>
        <div class="order_management__stat-card">
            <h3>Total Refunded</h3>
            <p class="order_management__stat-value">$<?php echo number_format($stats['total_refund_amount'], 2); ?></p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="order_management__filters">
        <form method="GET" class="order_management__filter-form">
            <select name="status" class="order_management__filter-select">
                <option value="all" <?php echo $currentStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                <option value="pending" <?php echo $currentStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $currentStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="processing" <?php echo $currentStatus === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="completed" <?php echo $currentStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="rejected" <?php echo $currentStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
            
            <select name="type" class="order_management__filter-select">
                <option value="all" <?php echo $currentType === 'all' ? 'selected' : ''; ?>>All Types</option>
                <option value="refund" <?php echo $currentType === 'refund' ? 'selected' : ''; ?>>Refund</option>
                <option value="exchange" <?php echo $currentType === 'exchange' ? 'selected' : ''; ?>>Exchange</option>
                <option value="store_credit" <?php echo $currentType === 'store_credit' ? 'selected' : ''; ?>>Store Credit</option>
            </select>
            
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
    </div>
    
    <!-- Returns List -->
    <div class="order_management__table-container">
        <table class="order_management__table">
            <thead>
                <tr>
                    <th>Return Number</th>
                    <th>Order ID</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr>
                        <td colspan="7" class="order_management__empty-state">No returns found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($returns as $return): ?>
                        <?php
                        // Get refund amount if available
                        $refundAmount = 0;
                        if ($return['return_type'] === 'refund') {
                            $refunds = order_management_get_order_refunds($return['order_id']);
                            foreach ($refunds as $refund) {
                                if ($refund['return_id'] == $return['id']) {
                                    $refundAmount = $refund['refund_amount'];
                                    break;
                                }
                            }
                        }
                        
                        // Get order info
                        $orderInfo = null;
                        if (order_management_is_commerce_available()) {
                            $conn = order_management_get_db_connection();
                            $stmt = $conn->prepare("SELECT order_number, total_amount FROM commerce_orders WHERE id = ? LIMIT 1");
                            $stmt->bind_param("i", $return['order_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $orderInfo = $result->fetch_assoc();
                            $stmt->close();
                        }
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/view.php?id=<?php echo $return['id']; ?>">
                                    <?php echo htmlspecialchars($return['return_number']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($orderInfo): ?>
                                    <a href="<?php echo order_management_get_admin_url(); ?>/components/commerce/orders/view.php?id=<?php echo $return['order_id']; ?>">
                                        #<?php echo htmlspecialchars($orderInfo['order_number'] ?? $return['order_id']); ?>
                                    </a>
                                <?php else: ?>
                                    #<?php echo $return['order_id']; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="order_management__badge order_management__badge--<?php echo htmlspecialchars($return['return_type']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($return['return_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="order_management__badge order_management__badge--<?php echo htmlspecialchars($return['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($return['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($refundAmount > 0): ?>
                                    $<?php echo number_format($refundAmount, 2); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($return['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/view.php?id=<?php echo $return['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <?php if ($return['status'] === 'pending'): ?>
                                    <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/approve.php?id=<?php echo $return['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                    <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/reject.php?id=<?php echo $return['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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

.order_management__stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__stat-card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__stat-card h3 {
    margin: 0 0 var(--spacing-xs) 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
}

.order_management__stat-value {
    margin: 0;
    font-size: var(--font-size-xl);
    font-weight: bold;
    color: var(--color-primary);
}

.order_management__filters {
    margin-bottom: var(--spacing-md);
}

.order_management__filter-form {
    display: flex;
    gap: var(--spacing-sm);
    align-items: center;
}

.order_management__filter-select {
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
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

.order_management__badge--exchange {
    background: var(--color-info-light);
    color: var(--color-info-dark);
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

