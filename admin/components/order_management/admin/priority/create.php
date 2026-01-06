<?php
/**
 * Order Management Component - Create Priority Level
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/priority.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = order_management_sanitize($_POST['name'] ?? '');
    $priorityValue = intval($_POST['priority_value'] ?? 0);
    $color = order_management_sanitize($_POST['color'] ?? '#007bff');
    
    if (empty($name)) {
        $error = 'Priority name is required';
    } else {
        $conn = order_management_get_db_connection();
        $tableName = order_management_get_table_name('priority_levels');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (name, priority_value, color, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sis", $name, $priorityValue, $color);
        if ($stmt->execute()) {
            header('Location: ' . order_management_get_component_admin_url() . '/priority/index.php');
            exit;
        } else {
            $error = 'Failed to create priority level';
        }
        $stmt->close();
    }
}

$pageTitle = 'Create Priority Level';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/priority/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="order_management__form">
        <div class="order_management__form-group">
            <label for="name">Priority Name *</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="order_management__form-group">
            <label for="priority_value">Priority Value *</label>
            <input type="number" id="priority_value" name="priority_value" value="0" required>
            <small>Higher values = higher priority</small>
        </div>
        
        <div class="order_management__form-group">
            <label for="color">Color *</label>
            <input type="color" id="color" name="color" value="#007bff" required>
        </div>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Create Priority</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/priority/index.php" class="btn btn-secondary">Cancel</a>
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
    max-width: 400px;
}

.order_management__form-group {
    margin-bottom: var(--spacing-md);
}

.order_management__form-group label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: bold;
}

.order_management__form-group input {
    width: 100%;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
}

.order_management__form-group small {
    display: block;
    margin-top: var(--spacing-xs);
    color: var(--color-text-secondary);
    font-size: var(--font-size-sm);
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

