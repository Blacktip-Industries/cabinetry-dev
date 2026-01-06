<?php
/**
 * Order Management Component - View Attachment
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

$attachmentId = $_GET['id'] ?? 0;
$attachment = order_management_get_attachment($attachmentId);

if (!$attachment) {
    header('Location: ' . order_management_get_component_admin_url() . '/attachments/index.php');
    exit;
}

// Handle download
if (isset($_GET['download'])) {
    if (file_exists($attachment['file_path'])) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
        header('Content-Length: ' . filesize($attachment['file_path']));
        readfile($attachment['file_path']);
        exit;
    }
}

$pageTitle = 'Attachment: ' . htmlspecialchars($attachment['file_name']);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="order_management__header-actions">
            <a href="?id=<?php echo $attachmentId; ?>&download=1" class="btn btn-primary">Download</a>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/attachments/index.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
    
    <div class="order_management__section">
        <h2>Attachment Details</h2>
        <dl class="order_management__details-list">
            <dt>File Name:</dt>
            <dd><?php echo htmlspecialchars($attachment['file_name']); ?></dd>
            
            <dt>Order ID:</dt>
            <dd>
                <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $attachment['order_id']; ?>">
                    #<?php echo $attachment['order_id']; ?>
                </a>
            </dd>
            
            <dt>File Type:</dt>
            <dd><?php echo ucfirst(htmlspecialchars($attachment['file_type'])); ?></dd>
            
            <dt>File Size:</dt>
            <dd><?php echo number_format($attachment['file_size'] / 1024, 2); ?> KB</dd>
            
            <dt>Visibility:</dt>
            <dd><?php echo $attachment['is_public'] ? 'Public' : 'Private'; ?></dd>
            
            <dt>Uploaded:</dt>
            <dd><?php echo date('Y-m-d H:i:s', strtotime($attachment['created_at'])); ?></dd>
        </dl>
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

.order_management__header-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.order_management__section {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__section h2 {
    margin: 0 0 var(--spacing-md) 0;
}

.order_management__details-list {
    margin: 0;
    padding: 0;
}

.order_management__details-list dt {
    font-weight: bold;
    margin-top: var(--spacing-sm);
    color: var(--color-text-secondary);
}

.order_management__details-list dd {
    margin: var(--spacing-xs) 0 0 0;
    color: var(--color-text);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

