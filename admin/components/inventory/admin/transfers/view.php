<?php
/**
 * Inventory Component - View Transfer
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

$transferItems = inventory_get_transfer_items($transferId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transfer - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Transfer: <?php echo htmlspecialchars($transfer['transfer_number']); ?></h1>
                <div>
                    <?php if ($transfer['status'] === 'pending'): ?>
                        <a href="approve.php?id=<?php echo $transferId; ?>" class="inventory__button inventory__button--primary">Approve</a>
                    <?php elseif ($transfer['status'] === 'approved'): ?>
                        <a href="process.php?id=<?php echo $transferId; ?>" class="inventory__button inventory__button--primary">Process Shipment</a>
                    <?php elseif ($transfer['status'] === 'in_transit'): ?>
                        <a href="complete.php?id=<?php echo $transferId; ?>" class="inventory__button inventory__button--primary">Complete Receipt</a>
                    <?php endif; ?>
                    <a href="index.php" class="inventory__button">Back to Transfers</a>
                </div>
            </div>
            
            <?php if (isset($_GET['created'])): ?>
            <div class="inventory__alert inventory__alert--success">
                Transfer created successfully!
            </div>
            <?php endif; ?>
            
            <!-- Transfer Details -->
            <div class="inventory__section">
                <h2>Transfer Details</h2>
                <table class="inventory__table inventory__table--details">
                    <tr>
                        <th>Transfer Number</th>
                        <td><?php echo htmlspecialchars($transfer['transfer_number']); ?></td>
                    </tr>
                    <tr>
                        <th>From Location</th>
                        <td><?php echo htmlspecialchars($transfer['from_location_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>To Location</th>
                        <td><?php echo htmlspecialchars($transfer['to_location_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php
                            $statusClass = '';
                            switch ($transfer['status']) {
                                case 'pending':
                                    $statusClass = 'inventory__badge--warning';
                                    break;
                                case 'approved':
                                case 'in_transit':
                                    $statusClass = 'inventory__badge--info';
                                    break;
                                case 'completed':
                                    $statusClass = 'inventory__badge--success';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'inventory__badge--inactive';
                                    break;
                            }
                            ?>
                            <span class="inventory__badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $transfer['status']))); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Requested</th>
                        <td><?php echo inventory_format_date($transfer['requested_at'], 'Y-m-d H:i:s'); ?></td>
                    </tr>
                    <?php if ($transfer['approved_at']): ?>
                    <tr>
                        <th>Approved</th>
                        <td><?php echo inventory_format_date($transfer['approved_at'], 'Y-m-d H:i:s'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($transfer['processed_at']): ?>
                    <tr>
                        <th>Processed</th>
                        <td><?php echo inventory_format_date($transfer['processed_at'], 'Y-m-d H:i:s'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($transfer['notes']): ?>
                    <tr>
                        <th>Notes</th>
                        <td><?php echo nl2br(htmlspecialchars($transfer['notes'])); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <!-- Transfer Items -->
            <div class="inventory__section">
                <h2>Transfer Items</h2>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Requested</th>
                            <th>Shipped</th>
                            <th>Received</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transferItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?></td>
                            <td><?php echo number_format($item['quantity_requested']); ?></td>
                            <td><?php echo number_format($item['quantity_shipped']); ?></td>
                            <td><?php echo number_format($item['quantity_received']); ?></td>
                            <td>
                                <?php
                                if ($item['quantity_received'] == $item['quantity_requested']) {
                                    echo '<span class="inventory__badge inventory__badge--success">Complete</span>';
                                } elseif ($item['quantity_shipped'] > 0) {
                                    echo '<span class="inventory__badge inventory__badge--info">In Transit</span>';
                                } else {
                                    echo '<span class="inventory__badge inventory__badge--warning">Pending</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

