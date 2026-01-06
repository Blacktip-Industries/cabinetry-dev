<?php
/**
 * Access Component - Frontend Messages
 */

require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Messages', false, 'access_messaging');
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

$accountId = $_SESSION['access_account_id'] ?? null;
$filters = [
    'to_user_id' => $userId,
    'account_id' => $accountId,
    'is_read' => isset($_GET['read']) ? (int)$_GET['read'] : null,
    'limit' => 50
];

$messages = access_get_messages($filters);
$unreadCount = access_get_unread_message_count($userId, $accountId);

?>
<div class="access-container">
    <div class="access-header">
        <h1>Messages <?php if ($unreadCount > 0): ?><span class="badge badge-warning"><?php echo $unreadCount; ?> unread</span><?php endif; ?></h1>
        <a href="compose.php" class="btn btn-primary">Compose</a>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr><td colspan="5" class="text-center">No messages found.</td></tr>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <tr class="<?php echo !$message['is_read'] ? 'unread' : ''; ?>">
                            <td><?php echo htmlspecialchars(trim(($message['from_first_name'] ?? '') . ' ' . ($message['from_last_name'] ?? '')) ?: $message['from_email']); ?></td>
                            <td><a href="view.php?id=<?php echo $message['id']; ?>"><?php echo htmlspecialchars($message['subject'] ?? '(No Subject)'); ?></a></td>
                            <td><?php echo access_format_date($message['created_at']); ?></td>
                            <td><?php echo $message['is_read'] ? '<span class="badge badge-success">Read</span>' : '<span class="badge badge-warning">Unread</span>'; ?></td>
                            <td><a href="view.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-secondary">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($hasBaseLayout) endLayout(); ?>

