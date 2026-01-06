<?php
/**
 * Inventory Component - Alerts Configuration
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/alerts.php';
require_once __DIR__ . '/../../core/items.php';
require_once __DIR__ . '/../../core/locations.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$alerts = inventory_get_alerts(['is_active' => 1]);
$triggeredAlerts = inventory_check_all_alerts();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts Configuration - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Alert Configuration</h1>
                <div>
                    <a href="create.php" class="inventory__button inventory__button--primary">Create Alert Rule</a>
                    <a href="../reports/alerts.php" class="inventory__button">View Alert Report</a>
                </div>
            </div>
            
            <?php if (!empty($triggeredAlerts)): ?>
            <div class="inventory__section">
                <h2>Active Alerts (<?php echo count($triggeredAlerts); ?>)</h2>
                <div class="inventory__alert inventory__alert--warning">
                    <p><strong>Warning:</strong> <?php echo count($triggeredAlerts); ?> alert(s) are currently triggered.</p>
                    <a href="../reports/alerts.php" class="inventory__link">View Details</a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Alerts Table -->
            <table class="inventory__table">
                <thead>
                    <tr>
                        <th>Alert Type</th>
                        <th>Item</th>
                        <th>Location</th>
                        <th>Threshold</th>
                        <th>Email Recipients</th>
                        <th>Status</th>
                        <th>Last Triggered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alerts)): ?>
                    <tr>
                        <td colspan="8" class="inventory__empty">No alert rules configured. <a href="create.php">Create one</a> to get started.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $alert['alert_type']))); ?></td>
                        <td>
                            <?php 
                            if ($alert['item_id']) {
                                $item = inventory_get_item($alert['item_id']);
                                echo htmlspecialchars($item ? $item['item_name'] : 'Item ID: ' . $alert['item_id']);
                            } else {
                                echo 'All Items';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($alert['location_id']) {
                                $location = inventory_get_location($alert['location_id']);
                                echo htmlspecialchars($location ? $location['location_name'] : 'Location ID: ' . $alert['location_id']);
                            } else {
                                echo 'All Locations';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($alert['threshold_quantity']) {
                                echo number_format($alert['threshold_quantity']);
                            } elseif ($alert['threshold_value']) {
                                echo number_format($alert['threshold_value'], 2);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $recipients = [];
                            if ($alert['alert_email']) {
                                $recipients[] = $alert['alert_email'];
                            }
                            if ($alert['alert_recipients']) {
                                $recipientList = json_decode($alert['alert_recipients'], true);
                                if (is_array($recipientList)) {
                                    $recipients = array_merge($recipients, $recipientList);
                                }
                            }
                            echo !empty($recipients) ? htmlspecialchars(implode(', ', $recipients)) : 'None';
                            ?>
                        </td>
                        <td><?php echo $alert['is_active'] ? '<span class="inventory__badge inventory__badge--success">Active</span>' : '<span class="inventory__badge inventory__badge--inactive">Inactive</span>'; ?></td>
                        <td><?php echo $alert['last_triggered_at'] ? inventory_format_date($alert['last_triggered_at'], 'Y-m-d H:i') : 'Never'; ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $alert['id']; ?>" class="inventory__link">Edit</a>
                            <a href="delete.php?id=<?php echo $alert['id']; ?>" class="inventory__link" onclick="return confirm('Delete this alert rule?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

