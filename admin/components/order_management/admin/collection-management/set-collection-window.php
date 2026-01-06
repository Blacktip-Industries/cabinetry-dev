<?php
/**
 * Order Management Component - Set Collection Window
 * Set collection window for a specific order
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-management.php';

// Check permissions
if (!access_has_permission('order_management_collection_manage')) {
    access_denied();
}

$orderId = $_GET['order_id'] ?? null;
$errors = [];
$success = false;

if (!$orderId) {
    $errors[] = 'Order ID is required';
}

if (function_exists('commerce_get_order')) {
    $order = commerce_get_order($orderId);
    if (!$order) {
        $errors[] = 'Order not found';
    }
} else {
    $errors[] = 'Commerce functions not available';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $startDateTime = $_POST['start_datetime'] ?? '';
    $endDateTime = $_POST['end_datetime'] ?? '';
    
    if (empty($startDateTime) || empty($endDateTime)) {
        $errors[] = 'Start and end date/time are required';
    } else {
        $result = order_management_set_manual_collection_window($orderId, $startDateTime, $endDateTime);
        if ($result) {
            $success = true;
        } else {
            $errors[] = 'Failed to set collection window';
        }
    }
}

$pageTitle = 'Set Collection Window';
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
        <div class="alert alert-success">Collection window set successfully</div>
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
    
    <?php if ($order): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5>Order Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                <?php if ($order['manual_completion_date']): ?>
                    <p><strong>Completion Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['manual_completion_date'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <form method="POST" class="form-horizontal">
            <div class="card">
                <div class="card-header">
                    <h5>Collection Window</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="start_datetime" class="required">Collection Window Start</label>
                        <input type="datetime-local" name="start_datetime" id="start_datetime" class="form-control" 
                               value="<?php echo $order['collection_window_start'] ? date('Y-m-d\TH:i', strtotime($order['collection_window_start'])) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_datetime" class="required">Collection Window End</label>
                        <input type="datetime-local" name="end_datetime" id="end_datetime" class="form-control" 
                               value="<?php echo $order['collection_window_end'] ? date('Y-m-d\TH:i', strtotime($order['collection_window_end'])) : ''; ?>" required>
                    </div>
                    
                    <?php
                    // Calculate available windows if completion date is set
                    if ($order['manual_completion_date']):
                        $completionDate = date('Y-m-d', strtotime($order['manual_completion_date']));
                        $availableWindows = order_management_calculate_collection_windows($completionDate);
                        if (!empty($availableWindows)):
                    ?>
                        <div class="alert alert-info">
                            <strong>Available Windows:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($availableWindows as $window): ?>
                                    <li>
                                        <?php echo date('Y-m-d', strtotime($window['date'])); ?> 
                                        <?php echo date('H:i', strtotime($window['start'])); ?> - 
                                        <?php echo date('H:i', strtotime($window['end'])); ?>
                                        (<?php echo $window['available_capacity']; ?> slots available)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; endif; ?>
                </div>
            </div>
            
            <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary">Set Collection Window</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

