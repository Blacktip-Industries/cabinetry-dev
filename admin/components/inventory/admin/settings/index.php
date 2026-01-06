<?php
/**
 * Inventory Component - General Settings
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parameters = [
        'default_costing_method' => $_POST['default_costing_method'] ?? 'Average',
        'default_location_id' => !empty($_POST['default_location_id']) ? (int)$_POST['default_location_id'] : '',
        'unit_of_measure_default' => $_POST['unit_of_measure_default'] ?? 'unit',
        'low_stock_threshold' => !empty($_POST['low_stock_threshold']) ? (int)$_POST['low_stock_threshold'] : 10,
        'barcode_type_default' => $_POST['barcode_type_default'] ?? 'CODE128',
        'enable_expiry_tracking' => isset($_POST['enable_expiry_tracking']) ? 'yes' : 'no',
        'alert_email_recipients' => $_POST['alert_email_recipients'] ?? ''
    ];
    
    foreach ($parameters as $name => $value) {
        inventory_set_parameter($name, $value);
    }
    
    $success = true;
}

$defaultLocation = inventory_get_default_location();
$locations = inventory_get_locations(['is_active' => 1]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Settings - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>General Settings</h1>
                <div>
                    <a href="costing.php" class="inventory__button">Costing Settings</a>
                    <a href="integration.php" class="inventory__button">Integration Settings</a>
                </div>
            </div>
            
            <?php if ($success): ?>
            <div class="inventory__alert inventory__alert--success">
                Settings saved successfully!
            </div>
            <?php endif; ?>
            
            <div class="inventory__section">
                <form method="POST" class="inventory__form">
                    <h3>Default Settings</h3>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Default Location</label>
                        <select name="default_location_id" class="inventory__select">
                            <option value="">None</option>
                            <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['id']; ?>" <?php echo ($defaultLocation && $defaultLocation['id'] == $location['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['location_name'] . ' (' . $location['location_code'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Default Unit of Measure</label>
                        <select name="unit_of_measure_default" class="inventory__select">
                            <option value="unit" <?php echo inventory_get_parameter('unit_of_measure_default', 'unit') === 'unit' ? 'selected' : ''; ?>>Unit</option>
                            <option value="kg" <?php echo inventory_get_parameter('unit_of_measure_default', 'unit') === 'kg' ? 'selected' : ''; ?>>Kilogram</option>
                            <option value="g" <?php echo inventory_get_parameter('unit_of_measure_default', 'unit') === 'g' ? 'selected' : ''; ?>>Gram</option>
                            <option value="L" <?php echo inventory_get_parameter('unit_of_measure_default', 'unit') === 'L' ? 'selected' : ''; ?>>Liter</option>
                            <option value="mL" <?php echo inventory_get_parameter('unit_of_measure_default', 'unit') === 'mL' ? 'selected' : ''; ?>>Milliliter</option>
                            <option value="m" <?php echo inventory_get_parameter('unit_of_measure_default', 'unit') === 'm' ? 'selected' : ''; ?>>Meter</option>
                            <option value="cm" <?php echo inventory_get_parameter('unit_of_measure_default', 'unit') === 'cm' ? 'selected' : ''; ?>>Centimeter</option>
                            <option value="box" <?php echo inventory_get_parameter('unit_of_measure_default', 'unit') === 'box' ? 'selected' : ''; ?>>Box</option>
                            <option value="pack" <?php echo inventory_get_parameter('unit_of_measure_default', 'unit') === 'pack' ? 'selected' : ''; ?>>Pack</option>
                        </select>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Default Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" class="inventory__input" 
                               value="<?php echo htmlspecialchars(inventory_get_parameter('low_stock_threshold', '10')); ?>" 
                               min="0" required>
                        <small class="inventory__help">Default threshold for low stock alerts</small>
                    </div>
                    
                    <h3>Barcode Settings</h3>
                    <div class="inventory__form-group">
                        <label class="inventory__label">Default Barcode Type</label>
                        <select name="barcode_type_default" class="inventory__select">
                            <option value="CODE128" <?php echo inventory_get_parameter('barcode_type_default', 'CODE128') === 'CODE128' ? 'selected' : ''; ?>>CODE128</option>
                            <option value="EAN13" <?php echo inventory_get_parameter('barcode_type_default', 'CODE128') === 'EAN13' ? 'selected' : ''; ?>>EAN13</option>
                            <option value="UPC" <?php echo inventory_get_parameter('barcode_type_default', 'CODE128') === 'UPC' ? 'selected' : ''; ?>>UPC</option>
                            <option value="QR" <?php echo inventory_get_parameter('barcode_type_default', 'CODE128') === 'QR' ? 'selected' : ''; ?>>QR Code</option>
                        </select>
                    </div>
                    
                    <h3>Tracking Settings</h3>
                    <div class="inventory__form-group">
                        <label class="inventory__label">
                            <input type="checkbox" name="enable_expiry_tracking" value="1" <?php echo inventory_get_parameter('enable_expiry_tracking', 'no') === 'yes' ? 'checked' : ''; ?>>
                            Enable Expiry Date Tracking
                        </label>
                        <small class="inventory__help">Track expiry dates for inventory items</small>
                    </div>
                    
                    <h3>Alert Settings</h3>
                    <div class="inventory__form-group">
                        <label class="inventory__label">Default Alert Email Recipients</label>
                        <input type="text" name="alert_email_recipients" class="inventory__input" 
                               value="<?php echo htmlspecialchars(inventory_get_parameter('alert_email_recipients', '')); ?>" 
                               placeholder="email1@example.com, email2@example.com">
                        <small class="inventory__help">Comma-separated list of default email recipients for alerts</small>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

