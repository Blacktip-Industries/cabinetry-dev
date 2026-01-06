<?php
/**
 * Order Management Component - Print Templates Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/printing.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$templateId = $_GET['id'] ?? 0;
$template = null;

if ($templateId > 0) {
    $template = order_management_get_print_template(''); // Would need to get by ID
    $conn = order_management_get_db_connection();
    $tableName = order_management_get_table_name('print_templates');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $templateId);
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();
    $stmt->close();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateType = order_management_sanitize($_POST['template_type'] ?? '');
    $templateName = order_management_sanitize($_POST['template_name'] ?? 'Default');
    $templateContent = $_POST['template_content'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($templateType)) {
        $error = 'Template type is required';
    } else {
        $conn = order_management_get_db_connection();
        $tableName = order_management_get_table_name('print_templates');
        
        if ($template) {
            // Update
            $stmt = $conn->prepare("UPDATE {$tableName} SET template_name = ?, template_content = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssii", $templateName, $templateContent, $isActive, $templateId);
        } else {
            // Create
            $stmt = $conn->prepare("INSERT INTO {$tableName} (template_type, template_name, template_content, is_active, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssi", $templateType, $templateName, $templateContent, $isActive);
        }
        
        if ($stmt->execute()) {
            header('Location: ' . order_management_get_component_admin_url() . '/printing/index.php');
            exit;
        } else {
            $error = 'Failed to save template';
        }
        $stmt->close();
    }
}

$pageTitle = $template ? 'Edit Print Template' : 'Create Print Template';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/printing/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="order_management__form">
        <?php if (!$template): ?>
            <div class="order_management__form-group">
                <label for="template_type">Template Type *</label>
                <select id="template_type" name="template_type" required>
                    <option value="invoice">Invoice</option>
                    <option value="packing_slip">Packing Slip</option>
                    <option value="label">Label</option>
                </select>
            </div>
        <?php endif; ?>
        
        <div class="order_management__form-group">
            <label for="template_name">Template Name</label>
            <input type="text" id="template_name" name="template_name" value="<?php echo htmlspecialchars($template['template_name'] ?? 'Default'); ?>">
        </div>
        
        <div class="order_management__form-group">
            <label for="template_content">Template Content (HTML) *</label>
            <textarea id="template_content" name="template_content" rows="20" required><?php echo htmlspecialchars($template['template_content'] ?? ''); ?></textarea>
            <small>Use placeholders: {order_number}, {order_date}, {total_amount}, {customer_name}, {shipping_address}</small>
        </div>
        
        <div class="order_management__form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo ($template && $template['is_active']) ? 'checked' : ''; ?>> Active
            </label>
        </div>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Save Template</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/printing/index.php" class="btn btn-secondary">Cancel</a>
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
    max-width: 800px;
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

.order_management__form-group textarea {
    font-family: monospace;
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

