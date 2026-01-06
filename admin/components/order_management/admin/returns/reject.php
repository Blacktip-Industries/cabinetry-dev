<?php
/**
 * Order Management Component - Reject Return
 * Admin interface to reject return requests
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/returns.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$returnId = $_GET['id'] ?? 0;
$return = order_management_get_return($returnId);

if (!$return) {
    header('Location: ' . order_management_get_component_admin_url() . '/returns/index.php');
    exit;
}

if ($return['status'] !== 'pending') {
    header('Location: ' . order_management_get_component_admin_url() . '/returns/view.php?id=' . $returnId);
    exit;
}

$error = null;

// Handle rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $reason = order_management_sanitize($_POST['reason'] ?? '');
    $result = order_management_reject_return($returnId, $userId, $reason);
    
    if ($result['success']) {
        header('Location: ' . order_management_get_component_admin_url() . '/returns/view.php?id=' . $returnId);
        exit;
    } else {
        $error = $result['error'] ?? 'Failed to reject return';
    }
}

$pageTitle = 'Reject Return: ' . $return['return_number'];

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/view.php?id=<?php echo $returnId; ?>" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="order_management__rejection-form">
        <p>Are you sure you want to reject this return request?</p>
        <p><strong>Return Number:</strong> <?php echo htmlspecialchars($return['return_number']); ?></p>
        <p><strong>Type:</strong> <?php echo ucfirst(htmlspecialchars($return['return_type'])); ?></p>
        
        <form method="POST">
            <div class="order_management__form-group">
                <label for="reason">Rejection Reason *</label>
                <textarea id="reason" name="reason" rows="4" required placeholder="Please provide a reason for rejecting this return..."><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
            </div>
            
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-danger">Reject Return</button>
                <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/view.php?id=<?php echo $returnId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.order_management__container {
    padding: var(--spacing-lg);
}

.order_management__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.order_management__alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius-md);
    margin-bottom: var(--spacing-md);
}

.order_management__alert--error {
    background: var(--color-error-light);
    color: var(--color-error-dark);
    border: var(--border-width) solid var(--color-error);
}

.order_management__rejection-form {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    max-width: 600px;
}

.order_management__form-group {
    margin-bottom: var(--spacing-md);
}

.order_management__form-group label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: bold;
}

.order_management__form-group textarea {
    width: 100%;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
    font-family: inherit;
}

.order_management__form-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-lg);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

