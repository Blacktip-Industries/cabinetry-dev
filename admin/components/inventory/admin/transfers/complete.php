<?php
/**
 * Inventory Component - Complete Transfer (Receive Items)
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/transfers.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$transferId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$transfer = inventory_get_transfer($transferId);

if (!$transfer) {
    die('Transfer not found.');
}

if ($transfer['status'] !== 'in_transit') {
    header('Location: view.php?id=' . $transferId);
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receivedItems = [];
    foreach ($_POST['received'] as $itemId => $quantity) {
        if ((int)$quantity > 0) {
            $receivedItems[(int)$itemId] = (int)$quantity;
        }
    }
    
    if (empty($receivedItems)) {
        $errors[] = 'At least one item must be received';
    }
    
    if (empty($errors)) {
        $result = inventory_complete_transfer($transferId, $receivedItems);
        
        if ($result['success']) {
            header('Location: view.php?id=' . $transferId . '&completed=1');
            exit;
        } else {
            $errors[] = $result['error'];
        }
    }
}

$transferItems = inventory_get_transfer_items($transferId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Transfer - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Complete Transfer: <?php echo htmlspecialchars($transfer['transfer_number']); ?></h1>
                <a href="view.php?id=<?php echo $transferId; ?>" class="inventory__button">Back to Transfer</a>
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
                <p><strong>From:</strong> <?php echo htmlspecialchars($transfer['from_location_name'] ?? 'N/A'); ?></p>
                <p><strong>To:</strong> <?php echo htmlspecialchars($transfer['to_location_name'] ?? 'N/A'); ?></p>
                
                <form method="POST" class="inventory__form">
                    <h3>Enter Received Quantities</h3>
                    <table class="inventory__table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Shipped</th>
                                <th>Already Received</th>
                                <th>Received Now</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transferItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?></td>
                                <td><?php echo number_format($item['quantity_shipped']); ?></td>
                                <td><?php echo number_format($item['quantity_received']); ?></td>
                                <td>
                                    <input type="number" name="received[<?php echo $item['item_id']; ?>]" 
                                           class="inventory__input" 
                                           min="0" 
                                           max="<?php echo $item['quantity_shipped'] - $item['quantity_received']; ?>"
                                           value="<?php echo $item['quantity_shipped'] - $item['quantity_received']; ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="inventory__form-actions">
                        <button type="submit" class="inventory__button inventory__button--primary">Complete Receipt</button>
                        <a href="view.php?id=<?php echo $transferId; ?>" class="inventory__button">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

