<?php
/**
 * Inventory Component - View Adjustment
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

$adjustmentItems = inventory_get_adjustment_items($adjustmentId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Adjustment - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Adjustment: <?php echo htmlspecialchars($adjustment['adjustment_number']); ?></h1>
                <div>
                    <?php if ($adjustment['status'] === 'pending'): ?>
                        <a href="approve.php?id=<?php echo $adjustmentId; ?>" class="inventory__button inventory__button--primary">Approve</a>
                        <a href="reject.php?id=<?php echo $adjustmentId; ?>" class="inventory__button inventory__button--danger" onclick="return confirm('Are you sure?');">Reject</a>
                    <?php endif; ?>
                    <a href="index.php" class="inventory__button">Back to Adjustments</a>
                </div>
            </div>
            
            <?php if (isset($_GET['created'])): ?>
            <div class="inventory__alert inventory__alert--success">
                Adjustment created successfully!
            </div>
            <?php endif; ?>
            
            <!-- Adjustment Details -->
            <div class="inventory__section">
                <h2>Adjustment Details</h2>
                <table class="inventory__table inventory__table--details">
                    <tr>
                        <th>Adjustment Number</th>
                        <td><?php echo htmlspecialchars($adjustment['adjustment_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Location</th>
                        <td><?php echo htmlspecialchars($adjustment['location_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td><?php echo htmlspecialchars(ucfirst($adjustment['adjustment_type'])); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php
                            $statusClass = '';
                            switch ($adjustment['status']) {
                                case 'pending':
                                    $statusClass = 'inventory__badge--warning';
                                    break;
                                case 'approved':
                                    $statusClass = 'inventory__badge--info';
                                    break;
                                case 'completed':
                                    $statusClass = 'inventory__badge--success';
                                    break;
                                case 'rejected':
                                    $statusClass = 'inventory__badge--inactive';
                                    break;
                            }
                            ?>
                            <span class="inventory__badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($adjustment['status'])); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Requested</th>
                        <td><?php echo inventory_format_date($adjustment['requested_at'], 'Y-m-d H:i:s'); ?></td>
                    </tr>
                    <?php if ($adjustment['approved_at']): ?>
                    <tr>
                        <th>Approved</th>
                        <td><?php echo inventory_format_date($adjustment['approved_at'], 'Y-m-d H:i:s'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($adjustment['processed_at']): ?>
                    <tr>
                        <th>Processed</th>
                        <td><?php echo inventory_format_date($adjustment['processed_at'], 'Y-m-d H:i:s'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($adjustment['reason']): ?>
                    <tr>
                        <th>Reason</th>
                        <td><?php echo htmlspecialchars($adjustment['reason']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($adjustment['notes']): ?>
                    <tr>
                        <th>Notes</th>
                        <td><?php echo nl2br(htmlspecialchars($adjustment['notes'])); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <!-- Adjustment Items -->
            <div class="inventory__section">
                <h2>Adjustment Items</h2>
                <table class="inventory__table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Change</th>
                            <th>Unit Cost</th>
                            <th>Reason</th>
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
                            <td><?php echo $item['unit_cost'] ? inventory_format_currency($item['unit_cost']) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($item['reason'] ?? ''); ?></td>
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

