<?php
/**
 * Inventory Component - Reports Dashboard
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/reports.php';
require_once __DIR__ . '/../../core/costing.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Inventory Reports</h1>
            </div>
            
            <div class="inventory__stats">
                <div class="inventory__stat-card">
                    <h3>Stock Levels Report</h3>
                    <p>View current stock levels by location, category, or item</p>
                    <a href="stock-levels.php" class="inventory__button">View Report</a>
                </div>
                
                <div class="inventory__stat-card">
                    <h3>Movement History</h3>
                    <p>Track all inventory movements with filters</p>
                    <a href="movements.php" class="inventory__button">View Report</a>
                </div>
                
                <div class="inventory__stat-card">
                    <h3>Inventory Valuation</h3>
                    <p>Calculate total inventory value using costing method</p>
                    <a href="valuation.php" class="inventory__button">View Report</a>
                </div>
                
                <div class="inventory__stat-card">
                    <h3>Transfer Report</h3>
                    <p>View all stock transfers and their status</p>
                    <a href="transfers.php" class="inventory__button">View Report</a>
                </div>
                
                <div class="inventory__stat-card">
                    <h3>Adjustment Report</h3>
                    <p>View all stock adjustments and their impact</p>
                    <a href="adjustments.php" class="inventory__button">View Report</a>
                </div>
                
                <div class="inventory__stat-card">
                    <h3>Alert Report</h3>
                    <p>View configured alerts and triggered notifications</p>
                    <a href="alerts.php" class="inventory__button">View Report</a>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

