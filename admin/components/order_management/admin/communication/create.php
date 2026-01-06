<?php
/**
 * Order Management Component - Create Communication
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/communication.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? 0;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $type = order_management_sanitize($_POST['communication_type'] ?? 'note');
    $direction = order_management_sanitize($_POST['direction'] ?? 'outbound');
    $subject = order_management_sanitize($_POST['subject'] ?? '');
    $content = order_management_sanitize($_POST['content'] ?? '');
    
    if (empty($subject) || empty($content)) {
        $error = 'Subject and content are required';
    } else {
        $result = order_management_create_communication($orderId, $type, $direction, $subject, $content, $_SESSION['user_id']);
        
        if ($result['success']) {
            header('Location: ' . order_management_get_component_admin_url() . '/communication/view.php?order_id=' . $orderId);
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to create communication';
        }
    }
}

$pageTitle = 'Add Communication';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/communication/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="order_management__form">
        <div class="order_management__form-group">
            <label for="order_id">Order ID *</label>
            <input type="number" id="order_id" name="order_id" value="<?php echo $orderId; ?>" required>
        </div>
        
        <div class="order_management__form-group">
            <label for="communication_type">Type *</label>
            <select id="communication_type" name="communication_type" required>
                <option value="email">Email</option>
                <option value="phone">Phone</option>
                <option value="note">Note</option>
            </select>
        </div>
        
        <div class="order_management__form-group">
            <label for="direction">Direction *</label>
            <select id="direction" name="direction" required>
                <option value="inbound">Inbound</option>
                <option value="outbound" selected>Outbound</option>
            </select>
        </div>
        
        <div class="order_management__form-group">
            <label for="subject">Subject *</label>
            <input type="text" id="subject" name="subject" required>
        </div>
        
        <div class="order_management__form-group">
            <label for="content">Content *</label>
            <textarea id="content" name="content" rows="6" required></textarea>
        </div>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Create Communication</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/communication/index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
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

.order_management__form {
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

.order_management__form-group input,
.order_management__form-group select,
.order_management__form-group textarea {
    width: 100%;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
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

