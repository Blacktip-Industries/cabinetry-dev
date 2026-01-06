<?php
/**
 * Inventory Component - Approve Adjustment
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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = inventory_approve_adjustment($adjustmentId);
    
    if ($result['success']) {
        header('Location: view.php?id=' . $adjustmentId . '&approved=1');
        exit;
    } else {
        $errors[] = $result['error'];
    }
}

$adjustmentItems = inventory_get_adjustment_items($adjustmentId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Adjustment - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Approve Adjustment: <?php echo htmlspecialchars($adjustment['adjustment_number']); ?></h1>
                <a href="view.php?id=<?php echo $adjustmentId; ?>" class="inventory__button">Back to Adjustment</a>
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
                <p><strong>Location:</strong> <?php echo htmlspecialchars($adjustment['location_name'] ?? 'N/A'); ?></p>
                <p><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($adjustment['adjustment_type'])); ?></p>
                <?php if ($adjustment['reason']): ?>
                <p><strong>Reason:</strong> <?php echo htmlspecialchars($adjustment['reason']); ?></p>
                <?php endif; ?>
                
                <h3>Adjustment Items</h3>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adjustmentItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?></td>
                            <td><?php echo number_format($item['quantity_before']); ?></td>
                            <td><?php echo number_format($item['quantity_after']); ?></td>
                            <td>
                                <?php
                                $change = $item['quantity_change'];
                                $changeClass = $change > 0 ? 'inventory__badge--success' : ($change < 0 ? 'inventory__badge--danger' : '');
                                ?>
                                <span class="inventory__badge <?php echo $changeClass; ?>">
                                    <?php echo $change > 0 ? '+' : ''; ?><?php echo number_format($change); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <form method="POST" class="inventory__form">
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Approve & Process Adjustment</button>
                        <a href="view.php?id=<?php echo $adjustmentId; ?>" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

