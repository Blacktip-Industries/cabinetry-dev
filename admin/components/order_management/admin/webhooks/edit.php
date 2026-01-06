<?php
/**
 * Order Management Component - Edit Webhook
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/webhooks.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$webhookId = $_GET['id'] ?? 0;
$webhook = order_management_get_webhook($webhookId);

if (!$webhook) {
    header('Location: ' . order_management_get_component_admin_url() . '/webhooks/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = order_management_sanitize($_POST['name'] ?? '');
    $url = order_management_sanitize($_POST['url'] ?? '');
    $events = $_POST['events'] ?? [];
    $headers = json_decode($_POST['headers'] ?? '{}', true);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name) || empty($url)) {
        $error = 'Name and URL are required';
    } else {
        $conn = order_management_get_db_connection();
        $tableName = order_management_get_table_name('webhooks');
        $eventsJson = json_encode($events);
        $headersJson = json_encode($headers);
        $stmt = $conn->prepare("UPDATE {$tableName} SET name = ?, url = ?, events = ?, headers = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssssii", $name, $url, $eventsJson, $headersJson, $isActive, $webhookId);
        if ($stmt->execute()) {
            header('Location: ' . order_management_get_component_admin_url() . '/webhooks/index.php');
            exit;
        } else {
            $error = 'Failed to update webhook';
        }
        $stmt->close();
    }
}

$pageTitle = 'Edit Webhook: ' . htmlspecialchars($webhook['name']);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/webhooks/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="order_management__form">
        <div class="order_management__form-group">
            <label for="name">Webhook Name *</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($webhook['name']); ?>" required>
        </div>
        
        <div class="order_management__form-group">
            <label for="url">Webhook URL *</label>
            <input type="url" id="url" name="url" value="<?php echo htmlspecialchars($webhook['url']); ?>" required>
        </div>
        
        <div class="order_management__form-group">
            <label>Events *</label>
            <div class="order_management__checkbox-group">
                <?php
                $webhookEvents = $webhook['events'] ?? [];
                $allEvents = ['*', 'order.created', 'order.updated', 'order.status_changed', 'fulfillment.created', 'return.created'];
                foreach ($allEvents as $event):
                ?>
                    <label>
                        <input type="checkbox" name="events[]" value="<?php echo $event; ?>" 
                               <?php echo in_array($event, $webhookEvents) ? 'checked' : ''; ?>>
                        <?php echo ucfirst(str_replace(['.', '_'], ' ', $event)); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="order_management__form-group">
            <label for="headers">Custom Headers (JSON)</label>
            <textarea id="headers" name="headers" rows="4"><?php echo htmlspecialchars(json_encode($webhook['headers'] ?? [], JSON_PRETTY_PRINT)); ?></textarea>
        </div>
        
        <div class="order_management__form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo $webhook['is_active'] ? 'checked' : ''; ?>> Active
            </label>
        </div>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/webhooks/index.php" class="btn btn-secondary">Cancel</a>
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

.order_management__checkbox-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.order_management__checkbox-group label {
    font-weight: normal;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
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

