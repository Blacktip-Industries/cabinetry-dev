<?php
/**
 * Order Management Component - Customer Behavior Analysis
 * Analyze customer collection behavior
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-analytics.php';

// Check permissions
if (!access_has_permission('order_management_collection_analytics')) {
    access_denied();
}

$customerId = $_GET['customer_id'] ?? null;
$behaviors = order_management_analyze_customer_behavior($customerId);

$pageTitle = 'Customer Behavior Analysis';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <form method="GET" class="d-inline-block">
            <input type="number" name="customer_id" placeholder="Customer ID" value="<?php echo htmlspecialchars($customerId ?? ''); ?>" class="form-control d-inline-block" style="width: auto;">
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <a href="index.php" class="btn btn-secondary">Back to Analytics</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($behaviors)): ?>
        <div class="alert alert-info">No customer behavior data available</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Customer ID</th>
                    <th>Analysis Date</th>
                    <th>Behavior Pattern</th>
                    <th>Collection Frequency</th>
                    <th>Preferred Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($behaviors as $behavior): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($behavior['customer_id'] ?? 'N/A'); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($behavior['analysis_date'] ?? 'now')); ?></td>
                        <td><?php echo htmlspecialchars($behavior['behavior_pattern'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($behavior['collection_frequency'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($behavior['preferred_time'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

