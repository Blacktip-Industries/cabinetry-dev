<?php
/**
 * Inventory Component - Edit Item
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/items.php';
require_once __DIR__ . '/../../core/integrations.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = inventory_get_item($itemId);

if (!$item) {
    die('Item not found.');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemData = [
        'item_code' => $_POST['item_code'] ?? '',
        'item_name' => $_POST['item_name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'sku' => $_POST['sku'] ?? '',
        'category' => $_POST['category'] ?? '',
        'unit_of_measure' => $_POST['unit_of_measure'] ?? 'unit',
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'commerce_product_id' => !empty($_POST['commerce_product_id']) ? (int)$_POST['commerce_product_id'] : null,
        'commerce_variant_id' => !empty($_POST['commerce_variant_id']) ? (int)$_POST['commerce_variant_id'] : null
    ];
    
    $result = inventory_update_item($itemId, $itemData);
    
    if ($result['success']) {
        header('Location: view.php?id=' . $itemId . '&updated=1');
        exit;
    } else {
        $errors[] = $result['error'];
    }
}

$categories = inventory_get_item_categories();
$commerceAvailable = inventory_is_commerce_integration_active();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Edit Item: <?php echo htmlspecialchars($item['item_name']); ?></h1>
                <a href="view.php?id=<?php echo $itemId; ?>" class="inventory__button">View Item</a>
            </div>
            
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
                        <label class="inventory__label">Item Code *</label>
                        <input type="text" name="item_code" class="inventory__input" value="<?php echo htmlspecialchars($item['item_code']); ?>" required>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Item Name *</label>
                        <input type="text" name="item_name" class="inventory__input" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Description</label>
                        <textarea name="description" class="inventory__textarea" rows="4"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">SKU</label>
                        <input type="text" name="sku" class="inventory__input" value="<?php echo htmlspecialchars($item['sku'] ?? ''); ?>">
                    </div>
                    
                    <div class="inventory__form-row">
                        <div class="inventory__form-group">
                            <label class="inventory__label">Category</label>
                            <input type="text" name="category" class="inventory__input" value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" list="categories">
                            <datalist id="categories">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Unit of Measure</label>
                            <select name="unit_of_measure" class="inventory__select">
                                <option value="unit" <?php echo ($item['unit_of_measure'] ?? 'unit') === 'unit' ? 'selected' : ''; ?>>Unit</option>
                                <option value="kg" <?php echo ($item['unit_of_measure'] ?? '') === 'kg' ? 'selected' : ''; ?>>Kilogram</option>
                                <option value="g" <?php echo ($item['unit_of_measure'] ?? '') === 'g' ? 'selected' : ''; ?>>Gram</option>
                                <option value="L" <?php echo ($item['unit_of_measure'] ?? '') === 'L' ? 'selected' : ''; ?>>Liter</option>
                                <option value="mL" <?php echo ($item['unit_of_measure'] ?? '') === 'mL' ? 'selected' : ''; ?>>Milliliter</option>
                                <option value="m" <?php echo ($item['unit_of_measure'] ?? '') === 'm' ? 'selected' : ''; ?>>Meter</option>
                                <option value="cm" <?php echo ($item['unit_of_measure'] ?? '') === 'cm' ? 'selected' : ''; ?>>Centimeter</option>
                                <option value="box" <?php echo ($item['unit_of_measure'] ?? '') === 'box' ? 'selected' : ''; ?>>Box</option>
                                <option value="pack" <?php echo ($item['unit_of_measure'] ?? '') === 'pack' ? 'selected' : ''; ?>>Pack</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if ($commerceAvailable): ?>
                    <div class="inventory__form-group">
                        <label class="inventory__label">Commerce Integration</label>
                        <div class="inventory__form-row">
                            <div class="inventory__form-group">
                                <label class="inventory__label">Commerce Product ID</label>
                                <input type="number" name="commerce_product_id" class="inventory__input" value="<?php echo $item['commerce_product_id'] ?? ''; ?>" min="1">
                            </div>
                            <div class="inventory__form-group">
                                <label class="inventory__label">Commerce Variant ID</label>
                                <input type="number" name="commerce_variant_id" class="inventory__input" value="<?php echo $item['commerce_variant_id'] ?? ''; ?>" min="1">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">
                            <input type="checkbox" name="is_active" value="1" <?php echo $item['is_active'] ? 'checked' : ''; ?>>
                            Active
                        </label>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Update Item</button>
                        <a href="view.php?id=<?php echo $itemId; ?>" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

