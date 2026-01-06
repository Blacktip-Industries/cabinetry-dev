<?php
/**
 * Inventory Component - Create Transfer
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/items.php';
require_once __DIR__ . '/../../core/locations.php';
require_once __DIR__ . '/../../core/transfers.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromLocationId = isset($_POST['from_location_id']) ? (int)$_POST['from_location_id'] : 0;
    $toLocationId = isset($_POST['to_location_id']) ? (int)$_POST['to_location_id'] : 0;
    $notes = $_POST['notes'] ?? null;
    
    if ($fromLocationId <= 0 || $toLocationId <= 0) {
        $errors[] = 'From and To locations are required';
    }
    
    if ($fromLocationId == $toLocationId) {
        $errors[] = 'From and To locations must be different';
    }
    
    // Parse items from form
    $items = [];
    if (isset($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            if (!empty($item['item_id']) && !empty($item['quantity']) && (int)$item['quantity'] > 0) {
                $items[] = [
                    'item_id' => (int)$item['item_id'],
                    'quantity' => (int)$item['quantity'],
                    'notes' => $item['notes'] ?? null
                ];
            }
        }
    }
    
    if (empty($items)) {
        $errors[] = 'At least one item is required';
    }
    
    if (empty($errors)) {
        $transferData = [
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'items' => $items,
            'notes' => $notes
        ];
        
        $result = inventory_create_transfer($transferData);
        
        if ($result['success']) {
            header('Location: view.php?id=' . $result['id'] . '&created=1');
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
    <title>Create Transfer - Inventory</title>
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
                <h1>Create Stock Transfer</h1>
                <a href="index.php" class="inventory__button">Back to Transfers</a>
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
                <form method="POST" class="inventory__form" id="transfer-form">
                    <div class="inventory__form-row">
                        <div class="inventory__form-group">
                            <label class="inventory__label">From Location *</label>
                            <select name="from_location_id" id="from_location" class="inventory__select" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" <?php echo (isset($_POST['from_location_id']) && $_POST['from_location_id'] == $location['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['location_name'] . ' (' . $location['location_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">To Location *</label>
                            <select name="to_location_id" id="to_location" class="inventory__select" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" <?php echo (isset($_POST['to_location_id']) && $_POST['to_location_id'] == $location['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['location_name'] . ' (' . $location['location_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <h3>Transfer Items</h3>
                    <div id="transfer-items">
                        <div class="inventory__transfer-item">
                            <div class="inventory__form-row">
                                <div class="inventory__form-group">
                                    <label class="inventory__label">Item *</label>
                                    <select name="items[0][item_id]" class="inventory__select" required>
                                        <option value="">Select Item</option>
                                        <?php foreach ($items as $item): ?>
                                        <option value="<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="inventory__form-group">
                                    <label class="inventory__label">Quantity *</label>
                                    <input type="number" name="items[0][quantity]" class="inventory__input" min="1" required>
                                </div>
                                
                                <div class="inventory__form-group">
                                    <label class="inventory__label">Notes</label>
                                    <input type="text" name="items[0][notes]" class="inventory__input">
                                </div>
                                
                                <div class="inventory__form-group">
                                    <label class="inventory__label">&nbsp;</label>
                                    <button type="button" class="inventory__button inventory__button--danger remove-item" style="display: none;">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" id="add-item" class="inventory__button">Add Item</button>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Notes</label>
                        <textarea name="notes" class="inventory__textarea" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Create Transfer</button>
                        <a href="index.php" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
    
    <script>
    let itemIndex = 1;
    document.getElementById('add-item').addEventListener('click', function() {
        const itemsContainer = document.getElementById('transfer-items');
        const newItem = itemsContainer.firstElementChild.cloneNode(true);
        
        // Update input names
        newItem.querySelectorAll('select, input').forEach(function(input) {
            if (input.name) {
                input.name = input.name.replace(/\[0\]/, '[' + itemIndex + ']');
            }
            if (input.value) {
                input.value = '';
            }
        });
        
        // Show remove button
        newItem.querySelector('.remove-item').style.display = 'block';
        newItem.querySelector('.remove-item').addEventListener('click', function() {
            newItem.remove();
        });
        
        itemsContainer.appendChild(newItem);
        itemIndex++;
    });
    
    // Show remove button on first item if there are multiple
    document.querySelectorAll('.remove-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.inventory__transfer-item').remove();
        });
    });
    </script>
</body>
</html>

