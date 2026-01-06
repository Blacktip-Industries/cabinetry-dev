<?php
/**
 * Inventory Component - Edit Location
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/locations.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$locationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$location = inventory_get_location($locationId);

if (!$location) {
    die('Location not found.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationData = [
        'location_code' => $_POST['location_code'] ?? '',
        'location_name' => $_POST['location_name'] ?? '',
        'location_type' => $_POST['location_type'] ?? 'warehouse',
        'parent_location_id' => !empty($_POST['parent_location_id']) ? (int)$_POST['parent_location_id'] : null,
        'address_line1' => $_POST['address_line1'] ?? '',
        'address_line2' => $_POST['address_line2'] ?? '',
        'city' => $_POST['city'] ?? '',
        'state' => $_POST['state'] ?? '',
        'postcode' => $_POST['postcode'] ?? '',
        'country' => $_POST['country'] ?? '',
        'contact_name' => $_POST['contact_name'] ?? '',
        'contact_email' => $_POST['contact_email'] ?? '',
        'contact_phone' => $_POST['contact_phone'] ?? '',
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'is_default' => isset($_POST['is_default']) ? 1 : 0
    ];
    
    $result = inventory_update_location($locationId, $locationData);
    
    if ($result['success']) {
        header('Location: index.php?updated=1');
        exit;
    } else {
        $errors[] = $result['error'];
    }
}

$allLocations = inventory_get_locations(['is_active' => 1]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Location - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Edit Location: <?php echo htmlspecialchars($location['location_name']); ?></h1>
                <a href="index.php" class="inventory__button">Back to Locations</a>
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
                            <label class="inventory__label">Location Code *</label>
                            <input type="text" name="location_code" class="inventory__input" value="<?php echo htmlspecialchars($location['location_code']); ?>" required>
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Location Name *</label>
                            <input type="text" name="location_name" class="inventory__input" value="<?php echo htmlspecialchars($location['location_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="inventory__form-row">
                        <div class="inventory__form-group">
                            <label class="inventory__label">Location Type *</label>
                            <select name="location_type" class="inventory__select" required>
                                <option value="warehouse" <?php echo $location['location_type'] === 'warehouse' ? 'selected' : ''; ?>>Warehouse</option>
                                <option value="zone" <?php echo $location['location_type'] === 'zone' ? 'selected' : ''; ?>>Zone</option>
                                <option value="bin" <?php echo $location['location_type'] === 'bin' ? 'selected' : ''; ?>>Bin</option>
                                <option value="shelf" <?php echo $location['location_type'] === 'shelf' ? 'selected' : ''; ?>>Shelf</option>
                                <option value="other" <?php echo $location['location_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Parent Location</label>
                            <select name="parent_location_id" class="inventory__select">
                                <option value="">None (Top Level)</option>
                                <?php foreach ($allLocations as $loc): ?>
                                    <?php if ($loc['id'] != $locationId): ?>
                                    <option value="<?php echo $loc['id']; ?>" <?php echo $location['parent_location_id'] == $loc['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc['location_name'] . ' (' . $loc['location_code'] . ')'); ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <h3>Address Information</h3>
                    <div class="inventory__form-group">
                        <label class="inventory__label">Address Line 1</label>
                        <input type="text" name="address_line1" class="inventory__input" value="<?php echo htmlspecialchars($location['address_line1'] ?? ''); ?>">
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">Address Line 2</label>
                        <input type="text" name="address_line2" class="inventory__input" value="<?php echo htmlspecialchars($location['address_line2'] ?? ''); ?>">
                    </div>
                    
                    <div class="inventory__form-row">
                        <div class="inventory__form-group">
                            <label class="inventory__label">City</label>
                            <input type="text" name="city" class="inventory__input" value="<?php echo htmlspecialchars($location['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">State</label>
                            <input type="text" name="state" class="inventory__input" value="<?php echo htmlspecialchars($location['state'] ?? ''); ?>">
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Postcode</label>
                            <input type="text" name="postcode" class="inventory__input" value="<?php echo htmlspecialchars($location['postcode'] ?? ''); ?>">
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Country</label>
                            <input type="text" name="country" class="inventory__input" value="<?php echo htmlspecialchars($location['country'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <h3>Contact Information</h3>
                    <div class="inventory__form-row">
                        <div class="inventory__form-group">
                            <label class="inventory__label">Contact Name</label>
                            <input type="text" name="contact_name" class="inventory__input" value="<?php echo htmlspecialchars($location['contact_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Contact Email</label>
                            <input type="email" name="contact_email" class="inventory__input" value="<?php echo htmlspecialchars($location['contact_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="inventory__form-group">
                            <label class="inventory__label">Contact Phone</label>
                            <input type="text" name="contact_phone" class="inventory__input" value="<?php echo htmlspecialchars($location['contact_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">
                            <input type="checkbox" name="is_active" value="1" <?php echo $location['is_active'] ? 'checked' : ''; ?>>
                            Active
                        </label>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">
                            <input type="checkbox" name="is_default" value="1" <?php echo $location['is_default'] ? 'checked' : ''; ?>>
                            Set as Default Location
                        </label>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Update Location</button>
                        <a href="index.php" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

