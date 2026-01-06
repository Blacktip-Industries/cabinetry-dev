<?php
/**
 * Order Management Component - Upload Attachment
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/attachments.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? 0;
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $orderId = intval($_POST['order_id'] ?? 0);
    $fileType = order_management_sanitize($_POST['file_type'] ?? 'document');
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    
    if ($orderId <= 0) {
        $error = 'Order ID is required';
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed';
    } else {
        $result = order_management_upload_attachment($orderId, $_FILES['file'], $fileType, $isPublic);
        
        if ($result['success']) {
            $success = true;
            header('Location: ' . order_management_get_component_admin_url() . '/orders/view.php?id=' . $orderId . '&tab=attachments');
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to upload attachment';
        }
    }
}

$pageTitle = 'Upload Attachment';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/attachments/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="order_management__form">
        <div class="order_management__form-group">
            <label for="order_id">Order ID *</label>
            <input type="number" id="order_id" name="order_id" value="<?php echo $orderId; ?>" required>
        </div>
        
        <div class="order_management__form-group">
            <label for="file_type">File Type *</label>
            <select id="file_type" name="file_type" required>
                <option value="document">Document</option>
                <option value="invoice">Invoice</option>
                <option value="packing_slip">Packing Slip</option>
                <option value="label">Label</option>
                <option value="other">Other</option>
            </select>
        </div>
        
        <div class="order_management__form-group">
            <label for="file">File *</label>
            <input type="file" id="file" name="file" required>
        </div>
        
        <div class="order_management__form-group">
            <label>
                <input type="checkbox" name="is_public" value="1"> Public (accessible without authentication)
            </label>
        </div>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Upload Attachment</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/attachments/index.php" class="btn btn-secondary">Cancel</a>
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
.order_management__form-group select {
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

