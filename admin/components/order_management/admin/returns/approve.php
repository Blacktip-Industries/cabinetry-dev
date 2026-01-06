<?php
/**
 * Order Management Component - Approve Return
 * Admin interface to approve return requests
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

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $result = order_management_approve_return($returnId, $userId);
    
    if ($result['success']) {
        header('Location: ' . order_management_get_component_admin_url() . '/returns/view.php?id=' . $returnId);
        exit;
    } else {
        $error = $result['error'] ?? 'Failed to approve return';
    }
}

$pageTitle = 'Approve Return: ' . $return['return_number'];

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/view.php?id=<?php echo $returnId; ?>" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="order_management__approval-form">
        <p>Are you sure you want to approve this return request?</p>
        <p><strong>Return Number:</strong> <?php echo htmlspecialchars($return['return_number']); ?></p>
        <p><strong>Type:</strong> <?php echo ucfirst(htmlspecialchars($return['return_type'])); ?></p>
        
        <form method="POST">
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-success">Approve Return</button>
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

.order_management__approval-form {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    max-width: 600px;
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

