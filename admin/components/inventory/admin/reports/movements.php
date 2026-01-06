<?php
/**
 * Inventory Component - Movements Report
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/reports.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$filters = [];
if (isset($_GET['item_id']) && $_GET['item_id'] !== '') {
    $filters['item_id'] = (int)$_GET['item_id'];
}
if (isset($_GET['location_id']) && $_GET['location_id'] !== '') {
    $filters['location_id'] = (int)$_GET['location_id'];
}
if (isset($_GET['movement_type']) && $_GET['movement_type'] !== '') {
    $filters['movement_type'] = $_GET['movement_type'];
}
if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
    $filters['date_from'] = $_GET['date_from'];
}
if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
    $filters['date_to'] = $_GET['date_to'];
}

$report = inventory_generate_movement_report($filters);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movements Report - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Movement History Report</h1>
                <div>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="inventory__button">Export CSV</a>
                    <a href="index.php" class="inventory__button">Back to Reports</a>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="inventory__filters">
                <form method="GET" class="inventory__filter-form">
                    <input type="number" name="item_id" placeholder="Item ID" value="<?php echo htmlspecialchars($_GET['item_id'] ?? ''); ?>" class="inventory__input">
                    <input type="number" name="location_id" placeholder="Location ID" value="<?php echo htmlspecialchars($_GET['location_id'] ?? ''); ?>" class="inventory__input">
                    <select name="movement_type" class="inventory__select">
                        <option value="">All Types</option>
                        <option value="in" <?php echo ($_GET['movement_type'] ?? '') === 'in' ? 'selected' : ''; ?>>In</option>
                        <option value="out" <?php echo ($_GET['movement_type'] ?? '') === 'out' ? 'selected' : ''; ?>>Out</option>
                        <option value="adjustment" <?php echo ($_GET['movement_type'] ?? '') === 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                        <option value="transfer" <?php echo ($_GET['movement_type'] ?? '') === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                    </select>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>" class="inventory__input">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>" class="inventory__input">
                    <button type="submit" class="inventory__button">Filter</button>
                </form>
            </div>
            
            <!-- Report Table -->
            <table class="inventory__table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report)): ?>
                    <tr>
                        <td colspan="8" class="inventory__empty">No movements found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($report as $movement): ?>
                    <tr>
                        <td><?php echo inventory_format_date($movement['created_at'], 'Y-m-d H:i'); ?></td>
                        <td><?php echo htmlspecialchars($movement['item_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($movement['location_name'] ?? 'N/A'); ?></td>
                        <td><span class="inventory__badge"><?php echo htmlspecialchars(ucfirst($movement['movement_type'])); ?></span></td>
                        <td><?php echo number_format($movement['quantity']); ?></td>
                        <td><?php echo $movement['unit_cost'] ? inventory_format_currency($movement['unit_cost']) : 'N/A'; ?></td>
                        <td>
                            <?php if ($movement['reference_type'] && $movement['reference_id']): ?>
                                <?php echo htmlspecialchars($movement['reference_type']); ?> #<?php echo $movement['reference_id']; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($movement['notes'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (isset($_GET['export']) && $_GET['export'] === 'csv'): ?>
            <?php
            $csv = inventory_export_report_csv($report);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="movements-' . date('Y-m-d') . '.csv"');
            echo $csv;
            exit;
            ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

