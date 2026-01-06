<?php
/**
 * Order Management Component - Custom Reports Builder
 * Build custom collection reports
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-analytics.php';

// Check permissions
if (!access_has_permission('order_management_collection_analytics')) {
    access_denied();
}

$reportData = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportConfig = [
        'report_type' => $_POST['report_type'] ?? 'summary',
        'date_range' => [
            'start' => $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days')),
            'end' => $_POST['end_date'] ?? date('Y-m-d')
        ],
        'period' => $_POST['period'] ?? 'month',
        'filters' => []
    ];
    
    if (isset($_POST['customer_id']) && !empty($_POST['customer_id'])) {
        $reportConfig['filters']['customer_id'] = (int)$_POST['customer_id'];
    }
    
    $reportData = order_management_build_custom_report($reportConfig);
}

$pageTitle = 'Custom Reports';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Analytics</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Report Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="report_type" class="required">Report Type</label>
                            <select name="report_type" id="report_type" class="form-control" required>
                                <option value="summary">Summary</option>
                                <option value="performance">Performance</option>
                                <option value="forecast">Forecast</option>
                                <option value="roi">ROI</option>
                                <option value="customer_behavior">Customer Behavior</option>
                                <option value="routes">Routes</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" 
                                   value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="period">Period</label>
                            <select name="period" id="period" class="form-control">
                                <option value="day">Day</option>
                                <option value="week">Week</option>
                                <option value="month" selected>Month</option>
                                <option value="year">Year</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_id">Customer ID (Optional)</label>
                            <input type="number" name="customer_id" id="customer_id" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Report Results</h5>
                </div>
                <div class="card-body">
                    <?php if ($reportData === null): ?>
                        <p class="text-muted">Configure and generate a report to see results</p>
                    <?php elseif (empty($reportData)): ?>
                        <div class="alert alert-info">No data available for this report</div>
                    <?php else: ?>
                        <pre><?php print_r($reportData); ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

