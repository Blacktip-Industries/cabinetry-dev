<?php
/**
 * Order Management Component - Collection Performance Metrics
 * Detailed performance metrics
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
$waitTimeMetrics = order_management_get_wait_time_metrics($period);
$qualityMetrics = order_management_get_quality_metrics($period);

$pageTitle = 'Collection Performance';
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
        <a href="index.php" class="btn btn-secondary">Back to Analytics</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Collection Metrics</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Total Collections</th>
                            <td><?php echo number_format($metrics['total_collections'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <th>Completed</th>
                            <td><?php echo number_format($metrics['completed_collections'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <th>Cancelled</th>
                            <td><?php echo number_format($metrics['cancelled_collections'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <th>Completion Rate</th>
                            <td><?php echo number_format($metrics['completion_rate'] ?? 0, 2); ?>%</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Wait Time Metrics</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Average Wait Time</th>
                            <td><?php echo number_format($waitTimeMetrics['average_wait_time'] ?? 0, 2); ?> minutes</td>
                        </tr>
                        <tr>
                            <th>Minimum Wait Time</th>
                            <td><?php echo number_format($waitTimeMetrics['min_wait_time'] ?? 0, 2); ?> minutes</td>
                        </tr>
                        <tr>
                            <th>Maximum Wait Time</th>
                            <td><?php echo number_format($waitTimeMetrics['max_wait_time'] ?? 0, 2); ?> minutes</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Quality Metrics</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Completion Rate</th>
                            <td><?php echo number_format($qualityMetrics['completion_rate'] ?? 0, 2); ?>%</td>
                        </tr>
                        <tr>
                            <th>Customer Satisfaction</th>
                            <td><?php echo number_format($qualityMetrics['customer_satisfaction'] ?? 0, 1); ?>/5</td>
                        </tr>
                        <tr>
                            <th>On-Time Rate</th>
                            <td><?php echo number_format($qualityMetrics['on_time_rate'] ?? 0, 2); ?>%</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterPeriod(period) {
    window.location.href = 'performance.php?period=' + encodeURIComponent(period);
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

