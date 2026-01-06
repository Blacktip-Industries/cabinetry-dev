<?php
/**
 * Inventory Component - Integration Settings
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/integrations.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enableIntegration = isset($_POST['enable_commerce_integration']) ? 'yes' : 'no';
    inventory_set_parameter('enable_commerce_integration', $enableIntegration);
    
    $requireAdjustmentApproval = isset($_POST['require_adjustment_approval']) ? 'yes' : 'no';
    inventory_set_parameter('require_adjustment_approval', $requireAdjustmentApproval);
    
    $requireTransferApproval = isset($_POST['require_transfer_approval']) ? 'yes' : 'no';
    inventory_set_parameter('require_transfer_approval', $requireTransferApproval);
    
    $success = true;
}

$commerceAvailable = inventory_is_commerce_available();
$commerceIntegrationEnabled = inventory_is_commerce_integration_enabled();
$requireAdjustmentApproval = inventory_requires_adjustment_approval();
$requireTransferApproval = inventory_requires_transfer_approval();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integration Settings - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Integration Settings</h1>
                <a href="index.php" class="inventory__button">Back to Settings</a>
            </div>
            
            <?php if ($success): ?>
            <div class="inventory__alert inventory__alert--success">
                Settings saved successfully!
            </div>
            <?php endif; ?>
            
            <div class="inventory__section">
                <form method="POST" class="inventory__form">
                    <h3>Commerce Component Integration</h3>
                    
                    <?php if ($commerceAvailable): ?>
                    <div class="inventory__alert inventory__alert--success">
                        <p><strong>Commerce component detected!</strong> Integration is available.</p>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">
                            <input type="checkbox" name="enable_commerce_integration" value="1" <?php echo $commerceIntegrationEnabled ? 'checked' : ''; ?>>
                            Enable Commerce Integration
                        </label>
                        <small class="inventory__help">Link inventory items to commerce products and sync stock levels</small>
                    </div>
                    
                    <div class="inventory__section">
                        <h4>Integration Features</h4>
                        <ul>
                            <li>Link inventory items to commerce products</li>
                            <li>Sync stock levels between inventory and commerce</li>
                            <li>Use commerce product data when linked</li>
                            <li>Automatic stock updates on commerce orders</li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="inventory__alert inventory__alert--warning">
                        <p><strong>Commerce component not found.</strong> Install the commerce component to enable integration.</p>
                    </div>
                    <?php endif; ?>
                    
                    <h3>Approval Workflows</h3>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">
                            <input type="checkbox" name="require_adjustment_approval" value="1" <?php echo $requireAdjustmentApproval ? 'checked' : ''; ?>>
                            Require Approval for Stock Adjustments
                        </label>
                        <small class="inventory__help">Stock adjustments will require approval before being processed</small>
                    </div>
                    
                    <div class="inventory__form-group">
                        <label class="inventory__label">
                            <input type="checkbox" name="require_transfer_approval" value="1" <?php echo $requireTransferApproval ? 'checked' : ''; ?>>
                            Require Approval for Stock Transfers
                        </label>
                        <small class="inventory__help">Stock transfers will require approval before items can be shipped</small>
                    </div>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Save Settings</button>
                        <a href="index.php" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

