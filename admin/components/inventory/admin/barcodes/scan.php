<?php
/**
 * Inventory Component - Barcode Scanner
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/barcodes.php';
require_once __DIR__ . '/../../core/items.php';
require_once __DIR__ . '/../../core/stock.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$scannedItem = null;
$barcodeValue = $_GET['barcode'] ?? $_POST['barcode'] ?? '';

if (!empty($barcodeValue)) {
    $scannedItem = inventory_scan_barcode($barcodeValue);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Barcode - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Barcode Scanner</h1>
                <a href="index.php" class="inventory__button">Back to Barcodes</a>
            </div>
            
            <div class="inventory__section">
                <form method="GET" class="inventory__form" id="scan-form">
                    <div class="inventory__form-group">
                        <label class="inventory__label">Scan or Enter Barcode</label>
                        <input type="text" name="barcode" id="barcode-input" class="inventory__input" 
                               value="<?php echo htmlspecialchars($barcodeValue); ?>" 
                               placeholder="Scan barcode or enter manually" 
                               autofocus autocomplete="off">
                        <small class="inventory__help">Use a barcode scanner or enter the barcode value manually</small>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Lookup</button>
                        <button type="button" class="inventory__button" onclick="document.getElementById('barcode-input').value=''; document.getElementById('barcode-input').focus();">Clear</button>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($barcodeValue)): ?>
                <?php if ($scannedItem): ?>
                <div class="inventory__section">
                    <h2>Item Found</h2>
                    <table class="inventory__table inventory__table--details">
                        <tr>
                            <th>Item Code</th>
                            <td><?php echo htmlspecialchars($scannedItem['item_code']); ?></td>
                        </tr>
                        <tr>
                            <th>Item Name</th>
                            <td><?php echo htmlspecialchars($scannedItem['item_name']); ?></td>
                        </tr>
                        <tr>
                            <th>SKU</th>
                            <td><?php echo htmlspecialchars($scannedItem['sku'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Category</th>
                            <td><?php echo htmlspecialchars($scannedItem['category'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Actions</th>
                            <td>
                                <a href="../items/view.php?id=<?php echo $scannedItem['id']; ?>" class="inventory__button">View Item</a>
                                <a href="../items/edit.php?id=<?php echo $scannedItem['id']; ?>" class="inventory__button">Edit Item</a>
                            </td>
                        </tr>
                    </table>
                    
                    <?php
                    $stock = inventory_get_item_stock($scannedItem['id']);
                    if (!empty($stock)):
                    ?>
                    <h3>Stock Levels</h3>
                    <table class="inventory__table">
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Available</th>
                                <th>Reserved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['location_name']); ?></td>
                                <td><?php echo number_format($s['quantity_available']); ?></td>
                                <td><?php echo number_format($s['quantity_reserved']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="inventory__alert inventory__alert--error">
                    <p><strong>Barcode not found:</strong> <?php echo htmlspecialchars($barcodeValue); ?></p>
                    <p>The barcode you scanned does not match any items in the system.</p>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
    
    <script>
    // Auto-submit on barcode scanner input (when Enter is pressed or scanner sends data)
    document.getElementById('barcode-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('scan-form').submit();
        }
    });
    
    // Focus on input when page loads
    document.getElementById('barcode-input').focus();
    </script>
</body>
</html>

