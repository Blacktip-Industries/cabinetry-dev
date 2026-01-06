<?php
/**
 * Inventory Component - Alerts Report
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/reports.php';
require_once __DIR__ . '/../../core/alerts.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$report = inventory_generate_alert_report([]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts Report - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Alerts Report</h1>
                <div>
                    <a href="../alerts/index.php" class="inventory__button">Manage Alerts</a>
                    <a href="index.php" class="inventory__button">Back to Reports</a>
                </div>
            </div>
            
            <div class="inventory__section">
                <h2>Triggered Alerts (<?php echo $report['triggered_count']; ?>)</h2>
                <?php if (empty($report['triggered_alerts'])): ?>
                <p class="inventory__empty">No alerts currently triggered.</p>
                <?php else: ?>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Alert Type</th>
                            <th>Item</th>
                            <th>Location</th>
                            <th>Current Value</th>
                            <th>Threshold</th>
                            <th>Last Triggered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['triggered_alerts'] as $alert): ?>
                        <tr>
                            <td><span class="inventory__badge inventory__badge--warning"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $alert['alert_type']))); ?></span></td>
                            <td><?php echo htmlspecialchars($alert['item_name'] ?? 'All Items'); ?></td>
                            <td><?php echo htmlspecialchars($alert['location_name'] ?? 'All Locations'); ?></td>
                            <td>
                                <?php if (isset($alert['quantity_available'])): ?>
                                    <?php echo number_format($alert['quantity_available']); ?>
                                <?php elseif (isset($alert['expiry_date'])): ?>
                                    <?php echo htmlspecialchars($alert['expiry_date']); ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($alert['threshold_quantity'] ?? $alert['threshold_value'] ?? 'N/A'); ?></td>
                            <td><?php echo $alert['last_triggered_at'] ? inventory_format_date($alert['last_triggered_at'], 'Y-m-d H:i') : 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <div class="inventory__section">
                <h2>Configured Alert Rules (<?php echo count($report['configured_alerts']); ?>)</h2>
                <?php if (empty($report['configured_alerts'])): ?>
                <p class="inventory__empty">No alert rules configured.</p>
                <?php else: ?>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Alert Type</th>
                            <th>Item</th>
                            <th>Location</th>
                            <th>Threshold</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['configured_alerts'] as $alert): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $alert['alert_type']))); ?></td>
                            <td><?php echo htmlspecialchars($alert['item_id'] ? 'Item ID: ' . $alert['item_id'] : 'All Items'); ?></td>
                            <td><?php echo htmlspecialchars($alert['location_id'] ? 'Location ID: ' . $alert['location_id'] : 'All Locations'); ?></td>
                            <td><?php echo htmlspecialchars($alert['threshold_quantity'] ?? $alert['threshold_value'] ?? 'N/A'); ?></td>
                            <td><?php echo $alert['is_active'] ? '<span class="inventory__badge inventory__badge--success">Active</span>' : '<span class="inventory__badge inventory__badge--inactive">Inactive</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

