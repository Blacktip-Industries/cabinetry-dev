<?php
/**
 * Order Management Component - Fulfillment Dashboard
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/fulfillment.php';
require_once __DIR__ . '/../../../core/picking-lists.php';
require_once __DIR__ . '/../../../core/packing.php';
require_once __DIR__ . '/../../../core/shipping.php';

// Check if installed
if (!order_management_is_installed()) {
    die('Order Management component is not installed. Please run the installer.');
}

// Get fulfillment stats
$pendingFulfillments = order_management_get_fulfillments_by_status('pending');
$pickingFulfillments = order_management_get_fulfillments_by_status('picking');
$packingFulfillments = order_management_get_fulfillments_by_status('packing');
$shippedFulfillments = order_management_get_fulfillments_by_status('shipped');

// Get picking lists
$pendingPickingLists = order_management_get_picking_lists(['status' => 'pending']);
$inProgressPickingLists = order_management_get_picking_lists(['status' => 'in_progress']);

// Get stats
$packingStats = order_management_get_packing_stats();
$shippingStats = order_management_get_shipping_stats();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fulfillment Dashboard - Order Management</title>
    <link rel="stylesheet" href="../../../assets/css/order_management.css">
    <style>
        body { font-family: sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; }
        .stat-card h3 { margin: 0 0 10px 0; color: #666; font-size: 14px; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #007bff; }
        .section { margin: 30px 0; }
        .section h2 { border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; margin-right: 10px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #000; }
        .status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .status-pending { background: #ffc107; color: #000; }
        .status-picking { background: #17a2b8; color: white; }
        .status-packing { background: #6c757d; color: white; }
        .status-shipped { background: #28a745; color: white; }
        .status-delivered { background: #007bff; color: white; }
    </style>
</head>
<body>
    <h1>Fulfillment Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Pending Fulfillments</h3>
            <div class="value"><?php echo count($pendingFulfillments); ?></div>
        </div>
        <div class="stat-card">
            <h3>In Picking</h3>
            <div class="value"><?php echo count($pickingFulfillments); ?></div>
        </div>
        <div class="stat-card">
            <h3>In Packing</h3>
            <div class="value"><?php echo count($packingFulfillments); ?></div>
        </div>
        <div class="stat-card">
            <h3>Shipped Today</h3>
            <div class="value"><?php echo $shippingStats['shipped_today']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Pending Delivery</h3>
            <div class="value"><?php echo $shippingStats['pending_delivery']; ?></div>
        </div>
    </div>
    
    <div style="margin: 20px 0;">
        <a href="picking-lists.php" class="btn btn-primary">Manage Picking Lists</a>
        <a href="packing.php" class="btn btn-warning">Packing Interface</a>
        <a href="shipping.php" class="btn btn-success">Shipping Management</a>
    </div>
    
    <div class="section">
        <h2>Pending Fulfillments</h2>
        <?php if (empty($pendingFulfillments)): ?>
            <p>No pending fulfillments.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Warehouse</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($pendingFulfillments, 0, 10) as $fulfillment): ?>
                        <tr>
                            <td><?php echo $fulfillment['order_id']; ?></td>
                            <td><?php echo $fulfillment['warehouse_id'] ?? 'N/A'; ?></td>
                            <td><span class="status-badge status-<?php echo $fulfillment['fulfillment_status']; ?>"><?php echo ucfirst($fulfillment['fulfillment_status']); ?></span></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($fulfillment['created_at'])); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $fulfillment['id']; ?>" class="btn btn-primary" style="font-size: 12px; padding: 4px 8px;">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Active Picking Lists</h2>
        <?php if (empty($pendingPickingLists) && empty($inProgressPickingLists)): ?>
            <p>No active picking lists. <a href="picking-lists.php?action=create">Create a picking list</a></p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_merge($pendingPickingLists, $inProgressPickingLists) as $list): ?>
                        <tr>
                            <td><?php echo $list['id']; ?></td>
                            <td><?php echo $list['picking_date']; ?></td>
                            <td><span class="status-badge status-<?php echo str_replace('_', '-', $list['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $list['status'])); ?></span></td>
                            <td><?php echo $list['assigned_to'] ?? 'Unassigned'; ?></td>
                            <td>
                                <a href="picking-lists.php?id=<?php echo $list['id']; ?>" class="btn btn-primary" style="font-size: 12px; padding: 4px 8px;">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

