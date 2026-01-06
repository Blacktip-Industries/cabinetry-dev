<?php
/**
 * Access Component - View Message
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('View Message', true, 'access_messaging');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>View Message</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
        <link rel="stylesheet" href="../../assets/css/messaging.css">
    </head>
    <body>
    <?php
}

$messageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = $messageId ? access_get_message($messageId) : null;

if (!$message) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../../../login.php');
    exit;
}

// Mark as read if viewing
if ($message['to_user_id'] == $userId && !$message['is_read']) {
    access_mark_message_read($messageId, $userId);
    $message = access_get_message($messageId); // Refresh
}

$attachments = access_get_message_attachments($messageId);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'archive') {
        if (access_archive_message($messageId)) {
            header('Location: index.php');
            exit;
        }
    } elseif ($action === 'delete') {
        if (access_delete_message($messageId)) {
            header('Location: index.php');
            exit;
        }
    } elseif ($action === 'reply') {
        header('Location: compose.php?reply_to=' . $messageId);
        exit;
    }
}

?>
<div class="access-container">
    <div class="access-header">
        <h1><?php echo htmlspecialchars($message['subject'] ?? '(No Subject)'); ?></h1>
        <div class="access-actions">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="reply">
                <button type="submit" class="btn btn-primary">Reply</button>
            </form>
            <form method="POST" style="display: inline;" onsubmit="return confirm('Archive this message?');">
                <input type="hidden" name="action" value="archive">
                <button type="submit" class="btn btn-secondary">Archive</button>
            </form>
            <a href="index.php" class="btn btn-secondary">Back to Inbox</a>
        </div>
    </div>

    <div class="access-details">
        <div class="detail-section">
            <dl class="detail-list">
                <dt>From</dt>
                <dd>
                    <?php if ($message['from_user_id']): ?>
                        <?php echo htmlspecialchars(trim(($message['from_first_name'] ?? '') . ' ' . ($message['from_last_name'] ?? '')) ?: $message['from_email']); ?>
                        <small>(<?php echo htmlspecialchars($message['from_email']); ?>)</small>
                    <?php else: ?>
                        <em>System</em>
                    <?php endif; ?>
                </dd>
                
                <dt>To</dt>
                <dd>
                    <?php if ($message['to_user_id']): ?>
                        <?php echo htmlspecialchars(trim(($message['to_first_name'] ?? '') . ' ' . ($message['to_last_name'] ?? '')) ?: $message['to_email']); ?>
                    <?php elseif ($message['account_id']): ?>
                        <?php echo htmlspecialchars($message['account_name'] ?? 'Account #' . $message['account_id']); ?>
                    <?php else: ?>
                        <em>Broadcast</em>
                    <?php endif; ?>
                </dd>
                
                <dt>Type</dt>
                <dd>
                    <span class="badge badge-info"><?php echo ucfirst($message['message_type']); ?></span>
                </dd>
                
                <dt>Priority</dt>
                <dd>
                    <?php if ($message['priority'] !== 'normal'): ?>
                        <span class="badge badge-<?php echo $message['priority'] === 'urgent' ? 'danger' : ($message['priority'] === 'high' ? 'warning' : 'secondary'); ?>">
                            <?php echo ucfirst($message['priority']); ?>
                        </span>
                    <?php else: ?>
                        Normal
                    <?php endif; ?>
                </dd>
                
                <dt>Date</dt>
                <dd><?php echo access_format_date($message['created_at']); ?></dd>
                
                <?php if ($message['related_entity_type']): ?>
                    <dt>Related To</dt>
                    <dd>
                        <?php echo ucfirst($message['related_entity_type']); ?> #<?php echo $message['related_entity_id']; ?>
                    </dd>
                <?php endif; ?>
            </dl>
        </div>

        <div class="detail-section">
            <h2>Message</h2>
            <div class="message-content">
                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
            </div>
        </div>

        <?php if (!empty($attachments)): ?>
            <div class="detail-section">
                <h2>Attachments (<?php echo count($attachments); ?>)</h2>
                <ul class="attachment-list">
                    <?php foreach ($attachments as $attachment): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" download>
                                <?php echo htmlspecialchars($attachment['file_name']); ?>
                            </a>
                            <small>(<?php echo number_format($attachment['file_size'] / 1024, 2); ?> KB)</small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
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

