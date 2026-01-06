<?php
/**
 * Order Management Component - Collection Analytics Dashboard
 * Main analytics dashboard
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-analytics.php';

// Check permissions
if (!access_has_permission('order_management_collection_analytics')) {
    access_denied();
}

$period = $_GET['period'] ?? 'month';
$metrics = order_management_get_collection_metrics($period);
$roi = order_management_calculate_collection_roi($period);

$pageTitle = 'Collection Analytics';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <select id="period_filter" onchange="filterPeriod(this.value)" class="form-control d-inline-block" style="width: auto;">
            <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Today</option>
            <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
            <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
            <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Last Year</option>
        </select>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Collections</h5>
                    <h2 class="mb-0"><?php echo number_format($metrics['total_collections'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Completed</h5>
                    <h2 class="mb-0 text-success"><?php echo number_format($metrics['completed_collections'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Completion Rate</h5>
                    <h2 class="mb-0"><?php echo number_format($metrics['completion_rate'] ?? 0, 1); ?>%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Avg Wait Time</h5>
                    <h2 class="mb-0"><?php echo number_format($metrics['average_wait_time'] ?? 0, 1); ?> min</h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Revenue</h5>
                </div>
                <div class="card-body">
                    <p><strong>Total Revenue:</strong> $<?php echo number_format($roi['total_revenue'] ?? 0, 2); ?></p>
                    <p><strong>Early Bird Revenue:</strong> $<?php echo number_format($roi['early_bird_revenue'] ?? 0, 2); ?></p>
                    <p><strong>After Hours Revenue:</strong> $<?php echo number_format($roi['after_hours_revenue'] ?? 0, 2); ?></p>
                    <p><strong>Avg Revenue per Collection:</strong> $<?php echo number_format($roi['average_revenue_per_collection'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="performance.php?period=<?php echo $period; ?>" class="list-group-item list-group-item-action">Performance Metrics</a>
                        <a href="forecasting.php" class="list-group-item list-group-item-action">Demand Forecasting</a>
                        <a href="optimization.php" class="list-group-item list-group-item-action">Optimization Suggestions</a>
                        <a href="customer-behavior.php" class="list-group-item list-group-item-action">Customer Behavior</a>
                        <a href="routes.php" class="list-group-item list-group-item-action">Route Optimization</a>
                        <a href="reports.php" class="list-group-item list-group-item-action">Custom Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterPeriod(period) {
    window.location.href = 'index.php?period=' + encodeURIComponent(period);
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

