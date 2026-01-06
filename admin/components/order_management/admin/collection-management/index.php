<?php
/**
 * Order Management Component - Collection Management Dashboard
 * Main collection management dashboard
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-management.php';

// Check permissions
if (!access_has_permission('order_management_collection_manage')) {
    access_denied();
}

$conn = order_management_get_db_connection();

// Get statistics
$stats = [
    'pending_collections' => 0,
    'confirmed_collections' => 0,
    'today_collections' => 0,
    'upcoming_collections' => 0
];

if ($conn && function_exists('commerce_get_db_connection')) {
    $commerceConn = commerce_get_db_connection();
    if ($commerceConn) {
        $ordersTable = commerce_get_table_name('orders');
        $today = date('Y-m-d');
        
        // Pending collections
        $stmt = $commerceConn->prepare("SELECT COUNT(*) as count FROM {$ordersTable} WHERE collection_status = 'pending' AND collection_window_start IS NOT NULL");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['pending_collections'] = $row['count'] ?? 0;
            $stmt->close();
        }
        
        // Confirmed collections
        $stmt = $commerceConn->prepare("SELECT COUNT(*) as count FROM {$ordersTable} WHERE collection_confirmed_at IS NOT NULL");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['confirmed_collections'] = $row['count'] ?? 0;
            $stmt->close();
        }
        
        // Today's collections
        $stmt = $commerceConn->prepare("SELECT COUNT(*) as count FROM {$ordersTable} WHERE DATE(collection_window_start) = ? AND collection_status = 'pending'");
        if ($stmt) {
            $stmt->bind_param("s", $today);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['today_collections'] = $row['count'] ?? 0;
            $stmt->close();
        }
        
        // Upcoming collections (next 7 days)
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $stmt = $commerceConn->prepare("SELECT COUNT(*) as count FROM {$ordersTable} WHERE DATE(collection_window_start) BETWEEN ? AND ? AND collection_status = 'pending'");
        if ($stmt) {
            $stmt->bind_param("ss", $today, $nextWeek);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['upcoming_collections'] = $row['count'] ?? 0;
            $stmt->close();
        }
    }
}

$pageTitle = 'Collection Management';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="business-hours.php" class="btn btn-secondary">Business Hours</a>
        <a href="custom-office-hours.php" class="btn btn-secondary">Custom Office Hours</a>
        <a href="collection-settings.php" class="btn btn-secondary">Settings</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pending Collections</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['pending_collections']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Confirmed</h5>
                    <h2 class="mb-0 text-success"><?php echo number_format($stats['confirmed_collections']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Today</h5>
                    <h2 class="mb-0 text-warning"><?php echo number_format($stats['today_collections']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Next 7 Days</h5>
                    <h2 class="mb-0 text-info"><?php echo number_format($stats['upcoming_collections']); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="business-hours.php" class="list-group-item list-group-item-action">
                            <strong>Manage Business Hours</strong>
                            <small class="d-block text-muted">Set business hours for each day of the week</small>
                        </a>
                        <a href="custom-office-hours.php" class="list-group-item list-group-item-action">
                            <strong>Custom Office Hours</strong>
                            <small class="d-block text-muted">Set custom hours for specific dates</small>
                        </a>
                        <a href="collection-settings.php" class="list-group-item list-group-item-action">
                            <strong>Collection Settings</strong>
                            <small class="d-block text-muted">Configure collection management settings</small>
                        </a>
                        <a href="manage-capacity.php" class="list-group-item list-group-item-action">
                            <strong>Manage Capacity</strong>
                            <small class="d-block text-muted">Set collection capacity per time slot</small>
                        </a>
                        <a href="staff-assignments.php" class="list-group-item list-group-item-action">
                            <strong>Staff Assignments</strong>
                            <small class="d-block text-muted">Assign staff to collections</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Collections</h5>
                </div>
                <div class="card-body">
                    <?php
                    if (function_exists('commerce_get_db_connection')) {
                        $commerceConn = commerce_get_db_connection();
                        if ($commerceConn) {
                            $ordersTable = commerce_get_table_name('orders');
                            $stmt = $commerceConn->prepare("SELECT id, order_number, customer_name, collection_window_start, collection_status FROM {$ordersTable} WHERE collection_window_start IS NOT NULL ORDER BY collection_window_start DESC LIMIT 10");
                            if ($stmt) {
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0):
                                ?>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Customer</th>
                                                <th>Collection Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($order = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><a href="view-collection.php?order_id=<?php echo $order['id']; ?>"><?php echo htmlspecialchars($order['order_number']); ?></a></td>
                                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($order['collection_window_start'])); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $order['collection_status'] === 'confirmed' ? 'success' : 'warning'; ?>">
                                                            <?php echo htmlspecialchars($order['collection_status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted">No recent collections</p>
                                <?php endif; ?>
                                <?php
                                $stmt->close();
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

