<?php
/**
 * Request Collection Reschedule
 * Frontend page for customers to request a collection reschedule
 */

require_once __DIR__ . '/includes/frontend_layout.php';
require_once __DIR__ . '/admin/components/commerce/core/collection-management.php';
require_once __DIR__ . '/config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['account_id'])) {
    header('Location: login.php');
    exit;
}

$accountId = $_SESSION['account_id'];
$orderId = $_GET['order_id'] ?? null;
$errors = [];
$success = false;

if (!$orderId) {
    header('Location: my_orders.php');
    exit;
}

// Get order
$order = null;
if (function_exists('commerce_get_order')) {
    $order = commerce_get_order($orderId);
}

if (!$order || $order['account_id'] != $accountId) {
    header('Location: my_orders.php');
    exit;
}

// Check reschedule limit
$rescheduleLimit = $order['collection_reschedule_limit'] ?? 2;
$rescheduleCount = $order['collection_reschedule_count'] ?? 0;
$canReschedule = $rescheduleCount < $rescheduleLimit;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'request_reschedule') {
        if (!$canReschedule) {
            $errors[] = 'You have reached the maximum number of reschedule requests';
        } else {
            $newStart = $_POST['new_window_start'] ?? '';
            $newEnd = $_POST['new_window_end'] ?? '';
            $reason = $_POST['reason'] ?? '';
            
            if (empty($newStart) || empty($newEnd)) {
                $errors[] = 'New collection window is required';
            } else {
                $result = commerce_request_collection_reschedule($orderId, $newStart, $newEnd, $reason, $accountId);
                if ($result['success']) {
                    $success = true;
                } else {
                    $errors[] = $result['error'] ?? 'Failed to request reschedule';
                }
            }
        }
    }
}

startFrontendLayout('Request Collection Reschedule', 'collection');
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Request Collection Reschedule</h2>
        <p class="text-muted">Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
    </div>
</div>

<div class="collection_reschedule__container">
    <?php if ($success): ?>
        <div class="alert alert-success">
            <p>Reschedule request submitted successfully. We will review your request and get back to you.</p>
        </div>
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
    
    <?php if (!$canReschedule): ?>
        <div class="alert alert-warning">
            <p>You have reached the maximum number of reschedule requests (<?php echo $rescheduleLimit; ?>). Please contact us directly if you need to reschedule again.</p>
        </div>
    <?php endif; ?>
    
    <?php if ($order['collection_window_start'] && $order['collection_window_end']): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5>Current Collection Window</h5>
            </div>
            <div class="card-body">
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['collection_window_start'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($order['collection_window_start'])); ?> - <?php echo date('g:i A', strtotime($order['collection_window_end'])); ?></p>
            </div>
        </div>
        
        <?php if ($canReschedule): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Request New Collection Window</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="request_reschedule">
                        
                        <div class="form-group">
                            <label for="new_window_start" class="required">New Collection Window Start</label>
                            <input type="datetime-local" name="new_window_start" id="new_window_start" class="form-control" required>
                            <small class="form-text text-muted">Collection windows must be 2-4 hours long</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_window_end" class="required">New Collection Window End</label>
                            <input type="datetime-local" name="new_window_end" id="new_window_end" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="reason">Reason for Reschedule</label>
                            <textarea name="reason" id="reason" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Submit Reschedule Request</button>
                            <a href="collection_confirm.php?order_id=<?php echo $orderId; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <p>No collection window has been set for this order yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php endFrontendLayout(); ?>

