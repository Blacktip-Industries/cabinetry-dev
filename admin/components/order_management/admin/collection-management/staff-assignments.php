<?php
/**
 * Order Management Component - Staff Assignments
 * Manage staff assignments to collections
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
    if (isset($_POST['action']) && $_POST['action'] === 'assign') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $staffId = (int)($_POST['staff_id'] ?? 0);
        
        if ($orderId && $staffId) {
            $result = order_management_assign_staff_to_collection($orderId, $staffId);
            if ($result) {
                $success = true;
            } else {
                $errors[] = 'Failed to assign staff';
            }
        } else {
            $errors[] = 'Order ID and Staff ID are required';
        }
    }
}

// Get staff list
$staff = [];
$staffTable = order_management_get_table_name('collection_staff');
$stmt = $conn->prepare("SELECT * FROM {$staffTable} WHERE is_active = 1 ORDER BY staff_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }
    $stmt->close();
}

// Get assignments
$assignments = [];
if (function_exists('commerce_get_db_connection')) {
    $commerceConn = commerce_get_db_connection();
    if ($commerceConn) {
        $ordersTable = commerce_get_table_name('orders');
        $stmt = $commerceConn->prepare("SELECT id, order_number, customer_name, collection_window_start, collection_staff_id FROM {$ordersTable} WHERE collection_window_start IS NOT NULL AND collection_status = 'pending' ORDER BY collection_window_start ASC LIMIT 50");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $assignments[] = $row;
            }
            $stmt->close();
        }
    }
}

$pageTitle = 'Staff Assignments';
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
        <div class="alert alert-success">Staff assigned successfully</div>
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
    
    <?php if (empty($assignments)): ?>
        <div class="alert alert-info">No pending collections</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Collection Time</th>
                    <th>Assigned Staff</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($assignment['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['customer_name']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($assignment['collection_window_start'])); ?></td>
                        <td>
                            <?php if ($assignment['collection_staff_id']): ?>
                                <?php
                                $assignedStaff = null;
                                foreach ($staff as $s) {
                                    if ($s['id'] == $assignment['collection_staff_id']) {
                                        $assignedStaff = $s;
                                        break;
                                    }
                                }
                                echo $assignedStaff ? htmlspecialchars($assignedStaff['staff_name']) : 'Unknown';
                                ?>
                            <?php else: ?>
                                <span class="text-muted">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="assign">
                                <input type="hidden" name="order_id" value="<?php echo $assignment['id']; ?>">
                                <select name="staff_id" class="form-control form-control-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                    <option value="">Select Staff</option>
                                    <?php foreach ($staff as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" 
                                                <?php echo $assignment['collection_staff_id'] == $s['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['staff_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

