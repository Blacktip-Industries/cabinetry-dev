<?php
/**
 * Inventory Component - Create Alert Rule
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/alerts.php';
require_once __DIR__ . '/../../core/items.php';
require_once __DIR__ . '/../../core/locations.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alertData = [
        'alert_type' => $_POST['alert_type'] ?? '',
        'item_id' => !empty($_POST['item_id']) ? (int)$_POST['item_id'] : null,
        'location_id' => !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null,
        'threshold_quantity' => !empty($_POST['threshold_quantity']) ? (int)$_POST['threshold_quantity'] : null,
        'threshold_value' => !empty($_POST['threshold_value']) ? (float)$_POST['threshold_value'] : null,
        'alert_email' => !empty($_POST['alert_email']) ? $_POST['alert_email'] : null,
        'alert_recipients' => !empty($_POST['alert_recipients']) ? explode(',', $_POST['alert_recipients']) : null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    $result = inventory_create_alert($alertData);
    
    if ($result['success']) {
        header('Location: index.php?created=1');
        exit;
    } else {
        $errors[] = $result['error'];
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
    <title>Create Alert Rule - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Create Alert Rule</h1>
                <a href="index.php" class="inventory__button">Back to Alerts</a>
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
                        <label class="inventory__label">Alert Type *</label>
                        <select name="alert_type" id="alert_type" class="inventory__select" required>
                            <option value="">Select Alert Type</option>
                            <option value="low_stock" <?php echo ($_POST['alert_type'] ?? '') === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="high_stock" <?php echo ($_POST['alert_type'] ?? '') === 'high_stock' ? 'selected' : ''; ?>>High Stock</option>
                            <option value="expiry" <?php echo ($_POST['alert_type'] ?? '') === 'expiry' ? 'selected' : ''; ?>>Expiry Date</option>
                            <option value="movement_threshold" <?php echo ($_POST['alert_type'] ?? '') === 'movement_threshold' ? 'selected' : ''; ?>>Movement Threshold</option>
                        </select>
                    </div>
                    
                    <div class="inventory__form-row">
                        <div class="inventory__form-group">
                            <label class="inventory__label">Item</label>
                            <select name="item_id" class="inventory__select">
                                <option value="">All Items</option>
                                <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>" <?php echo (isset($_POST['item_id']) && $_POST['item_id'] == $item['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="inventory__help">Leave empty for all items</small>
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Location</label>
                            <select name="location_id" class="inventory__select">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" <?php echo (isset($_POST['location_id']) && $_POST['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['location_name'] . ' (' . $location['location_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="inventory__help">Leave empty for all locations</small>
                        </div>
                    </div>
                    
                    <div class="inventory__form-group" id="threshold_quantity_group">
                        <label class="inventory__label">Threshold Quantity *</label>
                        <input type="number" name="threshold_quantity" class="inventory__input" 
                               value="<?php echo htmlspecialchars($_POST['threshold_quantity'] ?? ''); ?>" 
                               min="0" required>
                        <small class="inventory__help">Alert will trigger when quantity reaches this threshold</small>
                    </div>
                    
                    <div class="inventory__form-group" id="threshold_value_group" style="display: none;">
                        <label class="inventory__label">Threshold Value *</label>
                        <input type="number" name="threshold_value" class="inventory__input" 
                               value="<?php echo htmlspecialchars($_POST['threshold_value'] ?? ''); ?>" 
                               step="0.01" min="0">
                        <small class="inventory__help">Alert will trigger when value reaches this threshold</small>
                    </div>
                    
                    <h3>Notification Settings</h3>
                    <div class="inventory__form-group">
                        <label class="inventory__label">Alert Email</label>
                        <input type="email" name="alert_email" class="inventory__input" 
                               value="<?php echo htmlspecialchars($_POST['alert_email'] ?? ''); ?>" 
                               placeholder="email@example.com">
                        <small class="inventory__help">Primary email for alerts</small>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Additional Recipients</label>
                        <input type="text" name="alert_recipients" class="inventory__input" 
                               value="<?php echo htmlspecialchars($_POST['alert_recipients'] ?? ''); ?>" 
                               placeholder="email1@example.com, email2@example.com">
                        <small class="inventory__help">Comma-separated list of additional email addresses</small>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">
                            <input type="checkbox" name="is_active" value="1" <?php echo isset($_POST['is_active']) ? 'checked' : 'checked'; ?>>
                            Active
                        </label>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Create Alert Rule</button>
                        <a href="index.php" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
    
    <script>
    document.getElementById('alert_type').addEventListener('change', function() {
        const alertType = this.value;
        const quantityGroup = document.getElementById('threshold_quantity_group');
        const valueGroup = document.getElementById('threshold_value_group');
        
        if (alertType === 'movement_threshold') {
            quantityGroup.style.display = 'none';
            quantityGroup.querySelector('input').removeAttribute('required');
            valueGroup.style.display = 'block';
            valueGroup.querySelector('input').setAttribute('required', 'required');
        } else {
            quantityGroup.style.display = 'block';
            quantityGroup.querySelector('input').setAttribute('required', 'required');
            valueGroup.style.display = 'none';
            valueGroup.querySelector('input').removeAttribute('required');
        }
    });
    </script>
</body>
</html>

