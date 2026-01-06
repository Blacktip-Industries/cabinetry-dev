<?php
/**
 * Order Management Component - Route Optimization
 * View and manage collection routes
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-analytics.php';

// Check permissions
if (!access_has_permission('order_management_collection_analytics')) {
    access_denied();
}

$date = $_GET['date'] ?? date('Y-m-d');
$routes = order_management_optimize_collection_routes($date);

$pageTitle = 'Route Optimization';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <form method="GET" class="d-inline-block">
            <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" class="form-control d-inline-block" style="width: auto;">
            <button type="submit" class="btn btn-primary">View Routes</button>
        </form>
        <a href="index.php" class="btn btn-secondary">Back to Analytics</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($routes)): ?>
        <div class="alert alert-info">No routes optimized for this date</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Route Order</th>
                    <th>Order ID</th>
                    <th>Collection Time</th>
                    <th>Estimated Travel Time</th>
                    <th>Distance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $route): ?>
                    <tr>
                        <td><?php echo $route['route_order']; ?></td>
                        <td><?php echo htmlspecialchars($route['order_id'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($route['collection_time'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($route['estimated_travel_time'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($route['distance_km'] ?? 'N/A'); ?> km</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

