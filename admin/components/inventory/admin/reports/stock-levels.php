<?php
/**
 * Inventory Component - Stock Levels Report
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/reports.php';
require_once __DIR__ . '/../../core/locations.php';
require_once __DIR__ . '/../../core/items.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$filters = [];
if (isset($_GET['location_id']) && $_GET['location_id'] !== '') {
    $filters['location_id'] = (int)$_GET['location_id'];
}
if (isset($_GET['category']) && $_GET['category'] !== '') {
    $filters['category'] = $_GET['category'];
}
if (isset($_GET['low_stock_only']) && $_GET['low_stock_only'] === '1') {
    $filters['low_stock_only'] = true;
}

$report = inventory_generate_stock_level_report($filters);
$locations = inventory_get_locations(['is_active' => 1]);
$categories = inventory_get_item_categories();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Levels Report - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Stock Levels Report</h1>
                <div>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="inventory__button">Export CSV</a>
                    <a href="index.php" class="inventory__button">Back to Reports</a>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="inventory__filters">
                <form method="GET" class="inventory__filter-form">
                    <select name="location_id" class="inventory__select">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>" <?php echo (isset($_GET['location_id']) && $_GET['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($location['location_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="category" class="inventory__select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="inventory__label">
                        <input type="checkbox" name="low_stock_only" value="1" <?php echo isset($_GET['low_stock_only']) ? 'checked' : ''; ?>>
                        Low Stock Only
                    </label>
                    <button type="submit" class="inventory__button">Filter</button>
                </form>
            </div>
            
            <!-- Report Table -->
            <table class="inventory__table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Location</th>
                        <th>Available</th>
                        <th>Reserved</th>
                        <th>On Order</th>
                        <th>Reorder Point</th>
                        <th>Reorder Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report)): ?>
                    <tr>
                        <td colspan="8" class="inventory__empty">No stock records found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($report as $stock): ?>
                    <tr>
                        <td>
                            <a href="../items/view.php?id=<?php echo $stock['item_id']; ?>" class="inventory__link">
                                <?php echo htmlspecialchars($stock['item_name'] . ' (' . $stock['item_code'] . ')'); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($stock['location_name']); ?></td>
                        <td><?php echo number_format($stock['quantity_available']); ?></td>
                        <td><?php echo number_format($stock['quantity_reserved']); ?></td>
                        <td><?php echo number_format($stock['quantity_on_order']); ?></td>
                        <td><?php echo number_format($stock['reorder_point']); ?></td>
                        <td><?php echo number_format($stock['reorder_quantity']); ?></td>
                        <td>
                            <?php if ($stock['quantity_available'] <= $stock['reorder_point']): ?>
                                <span class="inventory__badge inventory__badge--warning">Low Stock</span>
                            <?php elseif ($stock['quantity_available'] <= 0): ?>
                                <span class="inventory__badge inventory__badge--danger">Out of Stock</span>
                            <?php else: ?>
                                <span class="inventory__badge inventory__badge--success">In Stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (isset($_GET['export']) && $_GET['export'] === 'csv'): ?>
            <?php
            $csv = inventory_export_report_csv($report);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="stock-levels-' . date('Y-m-d') . '.csv"');
            echo $csv;
            exit;
            ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

