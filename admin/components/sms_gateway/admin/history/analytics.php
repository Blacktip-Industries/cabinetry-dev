<?php
/**
 * SMS Gateway Component - SMS Analytics
 * Advanced SMS analytics dashboard
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

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get analytics data
$analytics = [
    'by_provider' => [],
    'by_component' => [],
    'by_day' => []
];

// By provider
$sql = "SELECT provider_name, COUNT(*) as count, SUM(cost) as total_cost, 
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered
        FROM {$tableName} 
        WHERE sent_at BETWEEN ? AND ?
        GROUP BY provider_name";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $analytics['by_provider'][] = $row;
    }
    $stmt->close();
}

// By component
$sql = "SELECT component_name, COUNT(*) as count, SUM(cost) as total_cost
        FROM {$tableName} 
        WHERE sent_at BETWEEN ? AND ?
        GROUP BY component_name
        ORDER BY count DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $analytics['by_component'][] = $row;
    }
    $stmt->close();
}

$pageTitle = 'SMS Analytics';
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
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>By Provider</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($analytics['by_provider'])): ?>
                        <p class="text-muted">No data</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Provider</th>
                                    <th>Count</th>
                                    <th>Delivered</th>
                                    <th>Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['by_provider'] as $provider): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($provider['provider_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($provider['count']); ?></td>
                                        <td><?php echo number_format($provider['delivered']); ?></td>
                                        <td>$<?php echo number_format($provider['total_cost'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>By Component</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($analytics['by_component'])): ?>
                        <p class="text-muted">No data</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Count</th>
                                    <th>Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['by_component'] as $component): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($component['component_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($component['count']); ?></td>
                                        <td>$<?php echo number_format($component['total_cost'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

