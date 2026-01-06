<?php
/**
 * Order Management Component - Collection Demand Forecasting
 * Forecast collection demand
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-analytics.php';

// Check permissions
if (!access_has_permission('order_management_collection_analytics')) {
    access_denied();
}

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+30 days'));

$forecasts = order_management_forecast_collection_demand(['start' => $startDate, 'end' => $endDate]);

$pageTitle = 'Collection Demand Forecasting';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <form method="GET" class="d-inline-block">
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" class="form-control d-inline-block" style="width: auto;">
            <span>to</span>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" class="form-control d-inline-block" style="width: auto;">
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
        <a href="index.php" class="btn btn-secondary">Back to Analytics</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($forecasts)): ?>
        <div class="alert alert-info">No forecasts available for this date range</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Forecasted Collections</th>
                    <th>Confidence Level</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forecasts as $forecast): ?>
                    <tr>
                        <td><?php echo date('Y-m-d (l)', strtotime($forecast['forecast_date'])); ?></td>
                        <td><?php echo number_format($forecast['forecasted_collections'] ?? 0); ?></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo ($forecast['confidence_level'] ?? 0) * 100; ?>%">
                                    <?php echo number_format(($forecast['confidence_level'] ?? 0) * 100, 0); ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

