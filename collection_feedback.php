<?php
/**
 * Collection Feedback
 * Frontend page for customers to provide feedback on their collection experience
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
    if (isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = $_POST['comment'] ?? '';
        
        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Please provide a rating between 1 and 5';
        } else {
            $result = commerce_submit_collection_feedback($orderId, $rating, $comment, $accountId);
            if ($result['success']) {
                $success = true;
            } else {
                $errors[] = $result['error'] ?? 'Failed to submit feedback';
            }
        }
    }
}

startFrontendLayout('Collection Feedback', 'collection');
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Collection Feedback</h2>
        <p class="text-muted">Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
    </div>
</div>

<div class="collection_feedback__container">
    <?php if ($success): ?>
        <div class="alert alert-success">
            <p>Thank you for your feedback!</p>
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
    
    <?php if ($order['collection_feedback_rating']): ?>
        <div class="alert alert-info">
            <p>You have already submitted feedback for this collection.</p>
            <p><strong>Rating:</strong> <?php echo $order['collection_feedback_rating']; ?>/5</p>
            <?php if ($order['collection_feedback_comment']): ?>
                <p><strong>Comment:</strong> <?php echo htmlspecialchars($order['collection_feedback_comment']); ?></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5>Share Your Experience</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="submit_feedback">
                    
                    <div class="form-group">
                        <label for="rating" class="required">Rating</label>
                        <select name="rating" id="rating" class="form-control" required>
                            <option value="">Select Rating</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Very Good</option>
                            <option value="3">3 - Good</option>
                            <option value="2">2 - Fair</option>
                            <option value="1">1 - Poor</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment">Comments (Optional)</label>
                        <textarea name="comment" id="comment" class="form-control" rows="5"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Submit Feedback</button>
                        <a href="my_orders.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php endFrontendLayout(); ?>

