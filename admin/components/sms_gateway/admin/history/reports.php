<?php
/**
 * SMS Gateway Component - SMS Reports
 * Generate SMS reports
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_history_view')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_history');

// Get report data
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Calculate statistics
$stats = [
    'total_sent' => 0,
    'total_delivered' => 0,
    'total_failed' => 0,
    'total_cost' => 0.00,
    'by_provider' => [],
    'by_component' => [],
    'by_status' => []
];

$sql = "SELECT 
    COUNT(*) as total_sent,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
    SUM(cost) as total_cost
    FROM {$tableName} 
    WHERE sent_at BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total_sent'] = (int)($row['total_sent'] ?? 0);
    $stats['total_delivered'] = (int)($row['delivered'] ?? 0);
    $stats['total_failed'] = (int)($row['failed'] ?? 0);
    $stats['total_cost'] = (float)($row['total_cost'] ?? 0);
    $stmt->close();
}

$deliveryRate = $stats['total_sent'] > 0 ? ($stats['total_delivered'] / $stats['total_sent']) * 100 : 0;

$pageTitle = 'SMS Reports';
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
        <a href="index.php" class="btn btn-secondary">Back to History</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Sent</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['total_sent']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Delivered</h5>
                    <h2 class="mb-0 text-success"><?php echo number_format($stats['total_delivered']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Failed</h5>
                    <h2 class="mb-0 text-danger"><?php echo number_format($stats['total_failed']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Cost</h5>
                    <h2 class="mb-0">$<?php echo number_format($stats['total_cost'], 2); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Delivery Rate</h5>
                </div>
                <div class="card-body">
                    <h2><?php echo number_format($deliveryRate, 2); ?>%</h2>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $deliveryRate; ?>%">
                            <?php echo number_format($deliveryRate, 1); ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Average Cost per SMS</h5>
                </div>
                <div class="card-body">
                    <h2>$<?php echo $stats['total_sent'] > 0 ? number_format($stats['total_cost'] / $stats['total_sent'], 4) : '0.0000'; ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

