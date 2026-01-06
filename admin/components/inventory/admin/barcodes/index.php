<?php
/**
 * Inventory Component - Barcodes List
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/barcodes.php';
require_once __DIR__ . '/../../core/items.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$barcodes = [];

if ($itemId > 0) {
    $barcodes = inventory_get_item_barcodes($itemId);
    $item = inventory_get_item($itemId);
} else {
    // Get all barcodes
    $conn = inventory_get_db_connection();
    if ($conn) {
        $tableName = inventory_get_table_name('barcodes');
        $itemsTable = inventory_get_table_name('items');
        $result = $conn->query("SELECT b.*, i.item_name, i.item_code 
                                FROM {$tableName} b
                                LEFT JOIN {$itemsTable} i ON b.item_id = i.id
                                WHERE b.is_active = 1
                                ORDER BY b.created_at DESC
                                LIMIT 100");
        while ($row = $result->fetch_assoc()) {
            $barcodes[] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcodes - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Barcodes</h1>
                <div>
                    <a href="generate.php" class="inventory__button inventory__button--primary">Generate Barcode</a>
                    <a href="scan.php" class="inventory__button">Scan Barcode</a>
                </div>
            </div>
            
            <?php if ($itemId > 0 && isset($item)): ?>
            <div class="inventory__section">
                <p><strong>Item:</strong> <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?></p>
                <a href="index.php" class="inventory__link">View All Barcodes</a>
            </div>
            <?php endif; ?>
            
            <!-- Barcodes Table -->
            <table class="inventory__table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Barcode Type</th>
                        <th>Barcode Value</th>
                        <th>Primary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($barcodes)): ?>
                    <tr>
                        <td colspan="5" class="inventory__empty">No barcodes found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($barcodes as $barcode): ?>
                    <tr>
                        <td>
                            <?php if (isset($barcode['item_name'])): ?>
                                <a href="../items/view.php?id=<?php echo $barcode['item_id']; ?>" class="inventory__link">
                                    <?php echo htmlspecialchars($barcode['item_name'] . ' (' . $barcode['item_code'] . ')'); ?>
                                </a>
                            <?php else: ?>
                                Item ID: <?php echo $barcode['item_id']; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($barcode['barcode_type']); ?></td>
                        <td><code><?php echo htmlspecialchars($barcode['barcode_value']); ?></code></td>
                        <td><?php echo $barcode['is_primary'] ? '<span class="inventory__badge inventory__badge--success">Yes</span>' : 'No'; ?></td>
                        <td>
                            <?php if (!$barcode['is_primary']): ?>
                                <a href="set-primary.php?id=<?php echo $barcode['id']; ?>" class="inventory__link">Set Primary</a>
                            <?php endif; ?>
                            <a href="delete.php?id=<?php echo $barcode['id']; ?>" class="inventory__link" onclick="return confirm('Delete this barcode?');">Delete</a>
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

