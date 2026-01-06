<?php
/**
 * Inventory Component - Reject Adjustment
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/adjustments.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$adjustmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$adjustment = inventory_get_adjustment($adjustmentId);

if (!$adjustment) {
    die('Adjustment not found.');
}

if ($adjustment['status'] !== 'pending') {
    header('Location: view.php?id=' . $adjustmentId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = inventory_reject_adjustment($adjustmentId);
    
    if ($result['success']) {
        header('Location: view.php?id=' . $adjustmentId . '&rejected=1');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Adjustment - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Reject Adjustment: <?php echo htmlspecialchars($adjustment['adjustment_number']); ?></h1>
                <a href="view.php?id=<?php echo $adjustmentId; ?>" class="inventory__button">Back to Adjustment</a>
            </div>
            
            <div class="inventory__section">
                <div class="inventory__alert inventory__alert--error">
                    <p><strong>Warning:</strong> This will reject the adjustment request. The stock will not be changed.</p>
                </div>
                
                <form method="POST" class="inventory__form">
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--danger" onclick="return confirm('Are you sure you want to reject this adjustment?');">Reject Adjustment</button>
                        <a href="view.php?id=<?php echo $adjustmentId; ?>" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

