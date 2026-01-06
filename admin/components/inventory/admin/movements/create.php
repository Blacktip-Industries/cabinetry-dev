<?php
/**
 * Inventory Component - Create Movement
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/items.php';
require_once __DIR__ . '/../../core/locations.php';
require_once __DIR__ . '/../../core/stock.php';
require_once __DIR__ . '/../../core/movements.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $locationId = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;
    $movementType = $_POST['movement_type'] ?? 'adjustment';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $unitCost = !empty($_POST['unit_cost']) ? (float)$_POST['unit_cost'] : null;
    $notes = $_POST['notes'] ?? null;
    
    if ($itemId <= 0 || $locationId <= 0) {
        $errors[] = 'Item and Location are required';
    }
    
    if ($quantity == 0) {
        $errors[] = 'Quantity cannot be zero';
    }
    
    // Adjust quantity based on movement type
    $quantityChange = $quantity;
    if (in_array($movementType, ['out', 'reservation'])) {
        $quantityChange = -abs($quantity);
    } elseif (in_array($movementType, ['in', 'release'])) {
        $quantityChange = abs($quantity);
    }
    
    if (empty($errors)) {
        $result = inventory_update_stock($itemId, $locationId, $quantityChange, $movementType, null, null, $notes);
        
        if ($result['success']) {
            // Record cost if provided
            if ($unitCost !== null && $movementType === 'in') {
                require_once __DIR__ . '/../../core/costing.php';
                inventory_record_cost($itemId, $locationId, $unitCost, abs($quantity), 'movement', null, date('Y-m-d'));
            }
            
            header('Location: index.php?created=1');
            exit;
        } else {
            $errors[] = $result['error'];
        }
    }
}

$items = inventory_get_items(['is_active' => 1], 1000, 0);
$locations = inventory_get_locations(['is_active' => 1]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Movement - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Record Movement</h1>
                <a href="index.php" class="inventory__button">Back to Movements</a>
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
                    <div class="inventory__form-row">
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
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Location *</label>
                            <select name="location_id" class="inventory__select" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" <?php echo (isset($_POST['location_id']) && $_POST['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['location_name'] . ' (' . $location['location_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="inventory__form-row">
                        <div class="inventory__form-group">
                            <label class="inventory__label">Movement Type *</label>
                            <select name="movement_type" class="inventory__select" required>
                                <option value="in" <?php echo ($_POST['movement_type'] ?? '') === 'in' ? 'selected' : ''; ?>>In (Stock In)</option>
                                <option value="out" <?php echo ($_POST['movement_type'] ?? '') === 'out' ? 'selected' : ''; ?>>Out (Stock Out)</option>
                                <option value="adjustment" <?php echo ($_POST['movement_type'] ?? 'adjustment') === 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                                <option value="reservation" <?php echo ($_POST['movement_type'] ?? '') === 'reservation' ? 'selected' : ''; ?>>Reservation</option>
                                <option value="release" <?php echo ($_POST['movement_type'] ?? '') === 'release' ? 'selected' : ''; ?>>Release</option>
                            </select>
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Quantity *</label>
                            <input type="number" name="quantity" class="inventory__input" value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>" required min="1">
                        </div>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Unit Cost</label>
                        <input type="number" name="unit_cost" class="inventory__input" value="<?php echo htmlspecialchars($_POST['unit_cost'] ?? ''); ?>" step="0.01" min="0">
                        <small class="inventory__help">Required for "In" movements to track costing</small>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Notes</label>
                        <textarea name="notes" class="inventory__textarea" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Record Movement</button>
                        <a href="index.php" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

