<?php
/**
 * Confirm Collection
 * Frontend page for customers to confirm their collection window
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'confirm') {
        $result = commerce_confirm_collection($orderId, $accountId);
        if ($result['success']) {
            $success = true;
        } else {
            $errors[] = $result['error'] ?? 'Failed to confirm collection';
        }
    }
}

startFrontendLayout('Confirm Collection', 'collection');
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Confirm Collection</h2>
        <p class="text-muted">Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
    </div>
</div>

<div class="collection_confirm__container">
    <?php if ($success): ?>
        <div class="alert alert-success">
            <p>Collection confirmed successfully!</p>
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
    
    <?php if ($order['collection_window_start'] && $order['collection_window_end']): ?>
        <div class="card">
            <div class="card-header">
                <h5>Collection Window</h5>
            </div>
            <div class="card-body">
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['collection_window_start'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($order['collection_window_start'])); ?> - <?php echo date('g:i A', strtotime($order['collection_window_end'])); ?></p>
                
                <?php if (!$order['collection_confirmed_at']): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="confirm">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Confirm Collection Window</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">
                        <p>Collection confirmed on <?php echo date('F j, Y g:i A', strtotime($order['collection_confirmed_at'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <p>No collection window has been set for this order yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php endFrontendLayout(); ?>

