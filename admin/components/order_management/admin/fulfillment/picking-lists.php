<?php
/**
 * Order Management Component - Picking Lists Management
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/picking-lists.php';
require_once __DIR__ . '/../../../core/fulfillment.php';

// Check if installed
if (!order_management_is_installed()) {
    die('Order Management component is not installed. Please run the installer.');
}

$action = $_GET['action'] ?? 'list';
$pickingListId = $_GET['id'] ?? 0;
$errors = [];
$success = false;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_list'])) {
        $warehouseId = $_POST['warehouse_id'] ?? null;
        $pickingDate = $_POST['picking_date'] ?? date('Y-m-d');
        $optimizeRoute = isset($_POST['optimize_route']);
        
        $result = order_management_generate_picking_list_from_fulfillments($warehouseId, $pickingDate, [
            'optimize_route' => $optimizeRoute,
            'max_orders' => $_POST['max_orders'] ?? null
        ]);
        
        if ($result['success']) {
            $success = true;
            $pickingListId = $result['picking_list_id'];
            header('Location: picking-lists.php?id=' . $pickingListId);
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to create picking list';
        }
    }
}

// Get picking lists
$pickingLists = order_management_get_picking_lists();

// Get specific picking list if ID provided
$pickingList = null;
$pickingItems = [];
if ($pickingListId) {
    $pickingList = order_management_get_picking_list($pickingListId);
    if ($pickingList) {
        $pickingItems = order_management_get_picking_list_items($pickingListId);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Picking Lists - Order Management</title>
    <link rel="stylesheet" href="../../../assets/css/order_management.css">
    <style>
        body { font-family: sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn:hover { opacity: 0.8; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; }
        .picked { background: #d4edda; }
    </style>
</head>
<body>
    <h1>Picking Lists</h1>
    
    <?php if ($success): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            Picking list created successfully!
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($pickingList): ?>
        <h2>Picking List #<?php echo $pickingList['id']; ?></h2>
        <p><strong>Date:</strong> <?php echo $pickingList['picking_date']; ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($pickingList['status']); ?></p>
        
        <h3>Items to Pick</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Sequence</th>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Location</th>
                    <th>Quantity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pickingItems as $item): ?>
                    <tr class="<?php echo $item['picked_status'] ? 'picked' : ''; ?>">
                        <td><?php echo $item['sequence_order']; ?></td>
                        <td><?php echo $item['order_id']; ?></td>
                        <td>Product #<?php echo $item['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo $item['picked_status'] ? '✓ Picked' : 'Pending'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <a href="index.php" class="btn">Back to Dashboard</a>
    <?php else: ?>
        <div style="margin-bottom: 20px;">
            <a href="?action=create" class="btn">Create New Picking List</a>
        </div>
        
        <?php if ($action === 'create'): ?>
            <h2>Create Picking List</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="warehouse_id">Warehouse ID</label>
                    <input type="number" id="warehouse_id" name="warehouse_id" value="<?php echo $_POST['warehouse_id'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="picking_date">Picking Date</label>
                    <input type="date" id="picking_date" name="picking_date" value="<?php echo $_POST['picking_date'] ?? date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="optimize_route" value="1" <?php echo isset($_POST['optimize_route']) ? 'checked' : ''; ?>>
                        Optimize picking route
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="max_orders">Max Orders (optional)</label>
                    <input type="number" id="max_orders" name="max_orders" value="<?php echo $_POST['max_orders'] ?? ''; ?>" min="1">
                </div>
                
                <button type="submit" name="create_list" class="btn">Generate Picking List</button>
                <a href="picking-lists.php" class="btn" style="background: #6c757d;">Cancel</a>
            </form>
        <?php else: ?>
            <h2>All Picking Lists</h2>
            <?php if (empty($pickingLists)): ?>
                <p>No picking lists found. <a href="?action=create">Create your first picking list</a>.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Warehouse</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pickingLists as $list): ?>
                            <tr>
                                <td><?php echo $list['id']; ?></td>
                                <td><?php echo $list['picking_date']; ?></td>
                                <td><?php echo $list['warehouse_id'] ?? 'N/A'; ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $list['status'])); ?></td>
                                <td><?php echo $list['assigned_to'] ?? 'Unassigned'; ?></td>
                                <td>
                                    <a href="picking-lists.php?id=<?php echo $list['id']; ?>" class="btn" style="font-size: 12px; padding: 4px 8px;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>

