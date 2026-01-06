<?php
/**
 * Inventory Component - Create Adjustment
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/locations.php';
require_once __DIR__ . '/../../core/stock.php';
require_once __DIR__ . '/../../core/adjustments.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationId = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;
    $adjustmentType = $_POST['adjustment_type'] ?? 'count';
    $reason = $_POST['reason'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    if ($locationId <= 0) {
        $errors[] = 'Location is required';
    }
    
    // Parse items from form
    $items = [];
    if (isset($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            if (!empty($item['item_id']) && isset($item['quantity_after'])) {
                $items[] = [
                    'item_id' => (int)$item['item_id'],
                    'quantity_after' => (int)$item['quantity_after'],
                    'unit_cost' => !empty($item['unit_cost']) ? (float)$item['unit_cost'] : null,
                    'reason' => $item['reason'] ?? null
                ];
            }
        }
    }
    
    if (empty($items)) {
        $errors[] = 'At least one item is required';
    }
    
    if (empty($errors)) {
        $adjustmentData = [
            'location_id' => $locationId,
            'adjustment_type' => $adjustmentType,
            'items' => $items,
            'reason' => $reason,
            'notes' => $notes
        ];
        
        $result = inventory_create_adjustment($adjustmentData);
        
        if ($result['success']) {
            header('Location: view.php?id=' . $result['id'] . '&created=1');
            exit;
        } else {
            $errors[] = $result['error'];
        }
    }
}

$locations = inventory_get_locations(['is_active' => 1]);
$selectedLocationId = isset($_POST['location_id']) ? (int)$_POST['location_id'] : (isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0);
$locationStock = [];

if ($selectedLocationId > 0) {
    require_once __DIR__ . '/../../core/items.php';
    $locationStock = inventory_get_location_stock($selectedLocationId);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Adjustment - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
    <script src="../../assets/js/inventory.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Create Stock Adjustment</h1>
                <a href="index.php" class="inventory__button">Back to Adjustments</a>
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
                <form method="POST" class="inventory__form" id="adjustment-form">
                    <div class="inventory__form-row">
                        <div class="inventory__form-group">
                            <label class="inventory__label">Location *</label>
                            <select name="location_id" id="location_id" class="inventory__select" required onchange="loadLocationStock(this.value)">
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" <?php echo $selectedLocationId == $location['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['location_name'] . ' (' . $location['location_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Adjustment Type *</label>
                            <select name="adjustment_type" class="inventory__select" required>
                                <option value="count" <?php echo ($_POST['adjustment_type'] ?? 'count') === 'count' ? 'selected' : ''; ?>>Count</option>
                                <option value="correction" <?php echo ($_POST['adjustment_type'] ?? '') === 'correction' ? 'selected' : ''; ?>>Correction</option>
                                <option value="damage" <?php echo ($_POST['adjustment_type'] ?? '') === 'damage' ? 'selected' : ''; ?>>Damage</option>
                                <option value="expiry" <?php echo ($_POST['adjustment_type'] ?? '') === 'expiry' ? 'selected' : ''; ?>>Expiry</option>
                                <option value="other" <?php echo ($_POST['adjustment_type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Reason</label>
                        <input type="text" name="reason" class="inventory__input" value="<?php echo htmlspecialchars($_POST['reason'] ?? ''); ?>" placeholder="Reason for adjustment">
                    </div>
                    
                    <?php if ($selectedLocationId > 0 && !empty($locationStock)): ?>
                    <h3>Adjustment Items</h3>
                    <div id="adjustment-items">
                        <?php foreach ($locationStock as $index => $stock): ?>
                        <div class="inventory__adjustment-item">
                            <div class="inventory__form-row">
                                <div class="inventory__form-group">
                                    <label class="inventory__label">Item</label>
                                    <input type="hidden" name="items[<?php echo $index; ?>][item_id]" value="<?php echo $stock['item_id']; ?>">
                                    <input type="text" class="inventory__input" value="<?php echo htmlspecialchars($stock['item_name'] . ' (' . $stock['item_code'] . ')'); ?>" readonly>
                                </div>
                                
                                <div class="inventory__form-group">
                                    <label class="inventory__label">Current Quantity</label>
                                    <input type="text" class="inventory__input" value="<?php echo number_format($stock['quantity_available']); ?>" readonly>
                                </div>
                                
                                <div class="inventory__form-group">
                                    <label class="inventory__label">New Quantity *</label>
                                    <input type="number" name="items[<?php echo $index; ?>][quantity_after]" 
                                           class="inventory__input" 
                                           value="<?php echo $stock['quantity_available']; ?>"
                                           min="0" required>
                                </div>
                                
                                <div class="inventory__form-group">
                                    <label class="inventory__label">Unit Cost</label>
                                    <input type="number" name="items[<?php echo $index; ?>][unit_cost]" 
                                           class="inventory__input" 
                                           step="0.01" min="0">
                                </div>
                                
                                <div class="inventory__form-group">
                                    <label class="inventory__label">Reason</label>
                                    <input type="text" name="items[<?php echo $index; ?>][reason]" class="inventory__input">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif ($selectedLocationId > 0): ?>
                    <p class="inventory__empty">No stock found at this location.</p>
                    <?php else: ?>
                    <p class="inventory__empty">Please select a location to view stock items.</p>
                    <?php endif; ?>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Notes</label>
                        <textarea name="notes" class="inventory__textarea" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Create Adjustment</button>
                        <a href="index.php" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
    
    <script>
    function loadLocationStock(locationId) {
        if (locationId) {
            window.location.href = 'create.php?location_id=' + locationId;
        }
    }
    </script>
</body>
</html>

