<?php
/**
 * Inventory Component - Generate Barcode
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/items.php';
require_once __DIR__ . '/../../core/barcodes.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$errors = [];
$success = false;
$generatedBarcode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $barcodeType = $_POST['barcode_type'] ?? 'CODE128';
    $barcodeValue = !empty($_POST['barcode_value']) ? $_POST['barcode_value'] : null;
    $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
    
    if ($itemId <= 0) {
        $errors[] = 'Item is required';
    }
    
    if (empty($errors)) {
        $result = inventory_create_barcode($itemId, $barcodeType, $barcodeValue, $isPrimary);
        
        if ($result['success']) {
            $success = true;
            $generatedBarcode = $result;
        } else {
            $errors[] = $result['error'];
        }
    }
}

$items = inventory_get_items(['is_active' => 1], 1000, 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Barcode - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Generate Barcode</h1>
                <a href="index.php" class="inventory__button">Back to Barcodes</a>
            </div>
            
            <?php if ($success && $generatedBarcode): ?>
            <div class="inventory__alert inventory__alert--success">
                <p><strong>Barcode Generated Successfully!</strong></p>
                <p>Barcode Value: <code><?php echo htmlspecialchars($generatedBarcode['barcode_value']); ?></code></p>
                <p>You can now print or use this barcode for scanning.</p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="inventory__alert inventory__alert--error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="inventory__section">
                <form method="POST" class="inventory__form">
                    <div class="inventory__form-group">
                        <label class="inventory__label">Item *</label>
                        <select name="item_id" class="inventory__select" required>
                            <option value="">Select Item</option>
                            <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>" <?php echo (isset($_POST['item_id']) && $_POST['item_id'] == $item['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="inventory__form-row">
                        <div class="inventory__form-group">
                            <label class="inventory__label">Barcode Type *</label>
                            <select name="barcode_type" class="inventory__select" required>
                                <option value="CODE128" <?php echo ($_POST['barcode_type'] ?? 'CODE128') === 'CODE128' ? 'selected' : ''; ?>>CODE128</option>
                                <option value="EAN13" <?php echo ($_POST['barcode_type'] ?? '') === 'EAN13' ? 'selected' : ''; ?>>EAN13</option>
                                <option value="UPC" <?php echo ($_POST['barcode_type'] ?? '') === 'UPC' ? 'selected' : ''; ?>>UPC</option>
                                <option value="QR" <?php echo ($_POST['barcode_type'] ?? '') === 'QR' ? 'selected' : ''; ?>>QR Code</option>
                            </select>
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Custom Barcode Value</label>
                            <input type="text" name="barcode_value" class="inventory__input" value="<?php echo htmlspecialchars($_POST['barcode_value'] ?? ''); ?>" placeholder="Leave empty to auto-generate">
                            <small class="inventory__help">Leave empty to auto-generate based on item ID</small>
                        </div>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">
                            <input type="checkbox" name="is_primary" value="1" <?php echo isset($_POST['is_primary']) ? 'checked' : ''; ?>>
                            Set as Primary Barcode
                        </label>
                        <small class="inventory__help">Primary barcode will be used as default for this item</small>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Generate Barcode</button>
                        <a href="index.php" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

