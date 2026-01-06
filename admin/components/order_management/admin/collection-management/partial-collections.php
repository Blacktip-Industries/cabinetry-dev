<?php
/**
 * Order Management Component - Partial Collections
 * Manage partial collections
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-management.php';

// Check permissions
if (!access_has_permission('order_management_collection_manage')) {
    access_denied();
}

$conn = order_management_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'record_partial') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $itemsJson = json_encode($_POST['collected_items'] ?? []);
        $locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
        
        if ($orderId) {
            $result = order_management_record_partial_collection($orderId, $itemsJson, $locationId);
            if ($result) {
                $success = true;
            } else {
                $errors[] = 'Failed to record partial collection';
            }
        } else {
            $errors[] = 'Order ID is required';
        }
    }
}

// Get partial collections
$partialCollections = [];
if (function_exists('commerce_get_db_connection')) {
    $commerceConn = commerce_get_db_connection();
    if ($commerceConn) {
        $ordersTable = commerce_get_table_name('orders');
        $stmt = $commerceConn->prepare("SELECT id, order_number, customer_name, collection_is_partial, collection_partial_items_json FROM {$ordersTable} WHERE collection_is_partial = 1 ORDER BY collection_completed_at DESC LIMIT 50");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $partialCollections[] = $row;
            }
            $stmt->close();
        }
    }
}

$pageTitle = 'Partial Collections';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Collection Management</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Partial collection recorded successfully</div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (empty($partialCollections)): ?>
        <div class="alert alert-info">No partial collections recorded</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Collected Items</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($partialCollections as $collection): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($collection['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($collection['customer_name']); ?></td>
                        <td>
                            <?php
                            $items = json_decode($collection['collection_partial_items_json'] ?? '[]', true);
                            if (!empty($items)):
                                echo '<ul class="mb-0">';
                                foreach ($items as $item):
                                    echo '<li>' . htmlspecialchars($item['name'] ?? '') . ' (Qty: ' . ($item['quantity'] ?? 0) . ')</li>';
                                endforeach;
                                echo '</ul>';
                            else:
                                echo '<span class="text-muted">No items listed</span>';
                            endif;
                            ?>
                        </td>
                        <td>
                            <a href="view-collection.php?order_id=<?php echo $collection['id']; ?>" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

