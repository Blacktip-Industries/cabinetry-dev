<?php
/**
 * Access Component - Messages Inbox
 * List all messages with filters
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Messages', true, 'access_messaging');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Messages</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
        <link rel="stylesheet" href="../../assets/css/messaging.css">
    </head>
    <body>
    <?php
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../../../login.php');
    exit;
}

$filters = [
    'to_user_id' => $userId,
    'message_type' => $_GET['type'] ?? null,
    'is_read' => isset($_GET['read']) ? (int)$_GET['read'] : null,
    'is_archived' => isset($_GET['archived']) ? (int)$_GET['archived'] : 0,
    'limit' => 50
];

$messages = access_get_messages($filters);
$unreadCount = access_get_unread_message_count($userId);

?>
<div class="access-container">
    <div class="access-header">
        <h1>Messages <?php if ($unreadCount > 0): ?><span class="badge badge-warning"><?php echo $unreadCount; ?> unread</span><?php endif; ?></h1>
        <div class="access-actions">
            <a href="compose.php" class="btn btn-primary">Compose</a>
            <a href="sent.php" class="btn btn-secondary">Sent</a>
            <a href="archived.php" class="btn btn-secondary">Archived</a>
        </div>
    </div>

    <div class="access-filters">
        <form method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="type">Message Type</label>
                    <select id="type" name="type">
                        <option value="">All Types</option>
                        <option value="order" <?php echo ($filters['message_type'] ?? '') === 'order' ? 'selected' : ''; ?>>Order</option>
                        <option value="quote" <?php echo ($filters['message_type'] ?? '') === 'quote' ? 'selected' : ''; ?>>Quote</option>
                        <option value="direct" <?php echo ($filters['message_type'] ?? '') === 'direct' ? 'selected' : ''; ?>>Direct</option>
                        <option value="general" <?php echo ($filters['message_type'] ?? '') === 'general' ? 'selected' : ''; ?>>General</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="read">Status</label>
                    <select id="read" name="read">
                        <option value="">All</option>
                        <option value="0" <?php echo ($filters['is_read'] ?? '') === 0 ? 'selected' : ''; ?>>Unread</option>
                        <option value="1" <?php echo ($filters['is_read'] ?? '') === 1 ? 'selected' : ''; ?>>Read</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="index.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No messages found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <tr class="<?php echo !$message['is_read'] ? 'unread' : ''; ?>">
                            <td>
                                <?php if ($message['from_user_id']): ?>
                                    <?php echo htmlspecialchars(trim(($message['from_first_name'] ?? '') . ' ' . ($message['from_last_name'] ?? '')) ?: $message['from_email']); ?>
                                <?php else: ?>
                                    <em>System</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $message['id']; ?>">
                                    <?php echo htmlspecialchars($message['subject'] ?? '(No Subject)'); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo ucfirst($message['message_type']); ?></span>
                            </td>
                            <td>
                                <?php if ($message['priority'] !== 'normal'): ?>
                                    <span class="badge badge-<?php echo $message['priority'] === 'urgent' ? 'danger' : ($message['priority'] === 'high' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($message['priority']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo access_format_date($message['created_at']); ?></td>
                            <td>
                                <?php if ($message['is_read']): ?>
                                    <span class="badge badge-success">Read</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Unread</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
if ($hasBaseLayout) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

