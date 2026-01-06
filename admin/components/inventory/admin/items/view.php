<?php
/**
 * Inventory Component - View Item
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/items.php';
require_once __DIR__ . '/../../core/stock.php';
require_once __DIR__ . '/../../core/barcodes.php';
require_once __DIR__ . '/../../core/movements.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = inventory_get_item($itemId);

if (!$item) {
    die('Item not found.');
}

$stock = inventory_get_item_stock($itemId);
$barcodes = inventory_get_item_barcodes($itemId);
$recentMovements = inventory_get_movements(['item_id' => $itemId], 20, 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Item - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1><?php echo htmlspecialchars($item['item_name']); ?></h1>
                <div>
                    <a href="edit.php?id=<?php echo $itemId; ?>" class="inventory__button">Edit</a>
                    <a href="index.php" class="inventory__button">Back to Items</a>
                </div>
            </div>
            
            <?php if (isset($_GET['updated'])): ?>
            <div class="inventory__alert inventory__alert--success">
                Item updated successfully!
            </div>
            <?php endif; ?>
            
            <!-- Item Details -->
            <div class="inventory__section">
                <h2>Item Details</h2>
                <table class="inventory__table inventory__table--details">
                    <tr>
                        <th>Item Code</th>
                        <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                    </tr>
                    <tr>
                        <th>SKU</th>
                        <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Category</th>
                        <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Unit of Measure</th>
                        <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><?php echo $item['is_active'] ? '<span class="inventory__badge inventory__badge--success">Active</span>' : '<span class="inventory__badge inventory__badge--inactive">Inactive</span>'; ?></td>
                    </tr>
                    <?php if ($item['description']): ?>
                    <tr>
                        <th>Description</th>
                        <td><?php echo nl2br(htmlspecialchars($item['description'])); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <!-- Stock Levels -->
            <div class="inventory__section">
                <h2>Stock Levels</h2>
                <?php if (empty($stock)): ?>
                <p class="inventory__empty">No stock records found.</p>
                <?php else: ?>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Available</th>
                            <th>Reserved</th>
                            <th>On Order</th>
                            <th>Reorder Point</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['location_name']); ?></td>
                            <td><?php echo number_format($s['quantity_available']); ?></td>
                            <td><?php echo number_format($s['quantity_reserved']); ?></td>
                            <td><?php echo number_format($s['quantity_on_order']); ?></td>
                            <td><?php echo number_format($s['reorder_point']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Barcodes -->
            <?php if (!empty($barcodes)): ?>
            <div class="inventory__section">
                <h2>Barcodes</h2>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Barcode Value</th>
                            <th>Primary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($barcodes as $barcode): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($barcode['barcode_type']); ?></td>
                            <td><code><?php echo htmlspecialchars($barcode['barcode_value']); ?></code></td>
                            <td><?php echo $barcode['is_primary'] ? 'Yes' : 'No'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Recent Movements -->
            <div class="inventory__section">
                <h2>Recent Movements</h2>
                <?php if (empty($recentMovements)): ?>
                <p class="inventory__empty">No movements found.</p>
                <?php else: ?>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMovements as $movement): ?>
                        <tr>
                            <td><?php echo inventory_format_date($movement['created_at'], 'Y-m-d H:i'); ?></td>
                            <td><?php echo htmlspecialchars($movement['location_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($movement['movement_type']); ?></td>
                            <td><?php echo number_format($movement['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($movement['notes'] ?? ''); ?></td>
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

