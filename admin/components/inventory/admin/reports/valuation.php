<?php
/**
 * Inventory Component - Inventory Valuation Report
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

$report = inventory_generate_valuation_report($filters);
$locations = inventory_get_locations(['is_active' => 1]);
$categories = inventory_get_item_categories();
$costingMethod = inventory_get_costing_method();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Valuation Report - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Inventory Valuation Report</h1>
                <div>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="inventory__button">Export CSV</a>
                    <a href="index.php" class="inventory__button">Back to Reports</a>
                </div>
            </div>
            
            <div class="inventory__section">
                <p><strong>Costing Method:</strong> <?php echo htmlspecialchars($costingMethod); ?></p>
                <p><strong>Total Valuation:</strong> <span style="font-size: 1.5em; font-weight: bold; color: var(--inventory-primary-color);"><?php echo inventory_format_currency($report['total_valuation']); ?></span></p>
                <p><strong>Items Counted:</strong> <?php echo number_format($report['item_count']); ?></p>
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
                    <button type="submit" class="inventory__button">Filter</button>
                </form>
            </div>
            
            <!-- Report Table -->
            <table class="inventory__table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Location</th>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report['items'])): ?>
                    <tr>
                        <td colspan="5" class="inventory__empty">No items found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($report['items'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?></td>
                        <td><?php echo htmlspecialchars($item['location_name']); ?></td>
                        <td><?php echo number_format($item['quantity_available']); ?></td>
                        <td><?php echo inventory_format_currency($item['unit_cost']); ?></td>
                        <td><strong><?php echo inventory_format_currency($item['total_cost']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background: var(--bg-secondary, #f8f9fa); font-weight: bold;">
                        <td colspan="4" style="text-align: right;">Total Valuation:</td>
                        <td><?php echo inventory_format_currency($report['total_valuation']); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (isset($_GET['export']) && $_GET['export'] === 'csv'): ?>
            <?php
            $csv = inventory_export_report_csv($report['items']);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="valuation-' . date('Y-m-d') . '.csv"');
            echo $csv;
            exit;
            ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

