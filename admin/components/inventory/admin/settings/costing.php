<?php
/**
 * Inventory Component - Costing Method Settings
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/costing.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $costingMethod = $_POST['default_costing_method'] ?? 'Average';
    
    if (!in_array($costingMethod, ['FIFO', 'LIFO', 'Average'])) {
        $errors[] = 'Invalid costing method';
    } else {
        inventory_set_parameter('default_costing_method', $costingMethod);
        $success = true;
    }
}

$currentMethod = inventory_get_costing_method();
$totalValuation = inventory_calculate_valuation();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Costing Settings - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Costing Method Settings</h1>
                <a href="index.php" class="inventory__button">Back to Settings</a>
            </div>
            
            <?php if ($success): ?>
            <div class="inventory__alert inventory__alert--success">
                Costing method updated successfully!
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
                <h2>Current Settings</h2>
                <table class="inventory__table inventory__table--details">
                    <tr>
                        <th>Current Costing Method</th>
                        <td><strong><?php echo htmlspecialchars($currentMethod); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Total Inventory Valuation</th>
                        <td><strong><?php echo inventory_format_currency($totalValuation); ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="inventory__section">
                <h2>Change Costing Method</h2>
                <form method="POST" class="inventory__form">
                    <div class="inventory__form-group">
                        <label class="inventory__label">Costing Method *</label>
                        <select name="default_costing_method" class="inventory__select" required>
                            <option value="FIFO" <?php echo $currentMethod === 'FIFO' ? 'selected' : ''; ?>>FIFO (First In, First Out)</option>
                            <option value="LIFO" <?php echo $currentMethod === 'LIFO' ? 'selected' : ''; ?>>LIFO (Last In, First Out)</option>
                            <option value="Average" <?php echo $currentMethod === 'Average' ? 'selected' : ''; ?>>Average Cost</option>
                        </select>
                    </div>
                    
                    <div class="inventory__alert inventory__alert--warning">
                        <p><strong>Warning:</strong> Changing the costing method will affect future inventory valuations. Historical cost records will remain unchanged.</p>
                    </div>
                    
                    <h3>Costing Method Descriptions</h3>
                    <div class="inventory__section">
                        <h4>FIFO (First In, First Out)</h4>
                        <p>Assumes the oldest inventory is sold first. Cost is calculated using the oldest purchase prices first.</p>
                        
                        <h4>LIFO (Last In, First Out)</h4>
                        <p>Assumes the newest inventory is sold first. Cost is calculated using the most recent purchase prices first.</p>
                        
                        <h4>Average Cost</h4>
                        <p>Uses a weighted average of all purchase costs. Cost is calculated as total cost divided by total quantity.</p>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Update Costing Method</button>
                        <a href="index.php" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

