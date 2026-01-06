<?php
/**
 * Inventory Component - Admin Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/items.php';
require_once __DIR__ . '/../core/locations.php';
require_once __DIR__ . '/../core/stock.php';
require_once __DIR__ . '/../core/movements.php';
require_once __DIR__ . '/../core/transfers.php';
require_once __DIR__ . '/../core/adjustments.php';
require_once __DIR__ . '/../core/alerts.php';
require_once __DIR__ . '/../core/costing.php';

// Check if installed
if (!inventory_is_installed()) {
    die('Inventory component is not installed. Please run the installer.');
}

// Get dashboard stats
$conn = inventory_get_db_connection();
$stats = [];

// Total items
$itemsTable = inventory_get_table_name('items');
$result = $conn->query("SELECT COUNT(*) as count FROM {$itemsTable} WHERE is_active = 1");
$stats['total_items'] = $result->fetch_assoc()['count'];

// Total locations
$locationsTable = inventory_get_table_name('locations');
$result = $conn->query("SELECT COUNT(*) as count FROM {$locationsTable} WHERE is_active = 1");
$stats['total_locations'] = $result->fetch_assoc()['count'];

// Total stock value
$stats['total_valuation'] = inventory_calculate_valuation();

// Pending transfers
$transfersTable = inventory_get_table_name('transfers');
$result = $conn->query("SELECT COUNT(*) as count FROM {$transfersTable} WHERE status = 'pending'");
$stats['pending_transfers'] = $result->fetch_assoc()['count'];

// Pending adjustments
$adjustmentsTable = inventory_get_table_name('adjustments');
$result = $conn->query("SELECT COUNT(*) as count FROM {$adjustmentsTable} WHERE status = 'pending'");
$stats['pending_adjustments'] = $result->fetch_assoc()['count'];

// Active alerts
$triggeredAlerts = inventory_check_all_alerts();
$stats['active_alerts'] = count($triggeredAlerts);

// Recent movements
$recentMovements = inventory_get_movements([], 10, 0);

// Low stock items
$lowStockItems = inventory_generate_stock_level_report(['low_stock_only' => true]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__dashboard">
            <h1>Inventory Dashboard</h1>
            
            <!-- Stats Cards -->
            <div class="inventory__stats">
                <div class="inventory__stat-card">
                    <h3>Total Items</h3>
                    <p class="inventory__stat-value"><?php echo number_format($stats['total_items']); ?></p>
                </div>
                <div class="inventory__stat-card">
                    <h3>Locations</h3>
                    <p class="inventory__stat-value"><?php echo number_format($stats['total_locations']); ?></p>
                </div>
                <div class="inventory__stat-card">
                    <h3>Total Valuation</h3>
                    <p class="inventory__stat-value"><?php echo inventory_format_currency($stats['total_valuation']); ?></p>
                </div>
                <div class="inventory__stat-card">
                    <h3>Pending Transfers</h3>
                    <p class="inventory__stat-value"><?php echo number_format($stats['pending_transfers']); ?></p>
                </div>
                <div class="inventory__stat-card">
                    <h3>Pending Adjustments</h3>
                    <p class="inventory__stat-value"><?php echo number_format($stats['pending_adjustments']); ?></p>
                </div>
                <div class="inventory__stat-card inventory__stat-card--alert">
                    <h3>Active Alerts</h3>
                    <p class="inventory__stat-value"><?php echo number_format($stats['active_alerts']); ?></p>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <?php if (!empty($lowStockItems)): ?>
            <div class="inventory__section">
                <h2>Low Stock Items</h2>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Location</th>
                            <th>Available</th>
                            <th>Reorder Point</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($lowStockItems, 0, 10) as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['location_name']); ?></td>
                            <td><?php echo number_format($item['quantity_available']); ?></td>
                            <td><?php echo number_format($item['reorder_point']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Recent Movements -->
            <div class="inventory__section">
                <h2>Recent Movements</h2>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMovements as $movement): ?>
                        <tr>
                            <td><?php echo inventory_format_date($movement['created_at'], 'Y-m-d H:i'); ?></td>
                            <td><?php echo htmlspecialchars($movement['item_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($movement['location_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($movement['movement_type']); ?></td>
                            <td><?php echo number_format($movement['quantity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../includes/footer.php'; ?>
</body>
</html>

