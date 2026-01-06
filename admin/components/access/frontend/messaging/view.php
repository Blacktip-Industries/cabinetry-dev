<?php
/**
 * Access Component - Frontend View Message
 */

require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('View Message', false, 'access_messaging');
}

$messageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = $messageId ? access_get_message($messageId) : null;

if (!$message) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

// Check access
if ($message['to_user_id'] != $userId) {
    header('Location: index.php');
    exit;
}

// Mark as read
if (!$message['is_read']) {
    access_mark_message_read($messageId, $userId);
    $message = access_get_message($messageId); // Refresh
}

$attachments = access_get_message_attachments($messageId);

?>
<div class="access-container">
    <div class="access-header">
        <h1><?php echo htmlspecialchars($message['subject'] ?? '(No Subject)'); ?></h1>
        <a href="index.php" class="btn btn-secondary">Back to Inbox</a>
    </div>

    <div class="access-details">
        <div class="detail-section">
            <dl class="detail-list">
                <dt>From</dt>
                <dd>
                    <?php if ($message['from_user_id']): ?>
                        <?php echo htmlspecialchars(trim(($message['from_first_name'] ?? '') . ' ' . ($message['from_last_name'] ?? '')) ?: $message['from_email']); ?>
                    <?php else: ?>
                        <em>System</em>
                    <?php endif; ?>
                </dd>
                
                <dt>Date</dt>
                <dd><?php echo access_format_date($message['created_at']); ?></dd>
                
                <dt>Type</dt>
                <dd><span class="badge badge-info"><?php echo ucfirst($message['message_type']); ?></span></dd>
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
                <h2>Attachments</h2>
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

<?php if ($hasBaseLayout) endLayout(); ?>

