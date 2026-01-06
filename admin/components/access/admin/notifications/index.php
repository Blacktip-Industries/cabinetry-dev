<?php
/**
 * Access Component - Notifications Center
 */

require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Notifications', true, 'access_notifications');
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../../../login.php');
    exit;
}

$accountId = $_SESSION['access_account_id'] ?? null;
$filters = [
    'user_id' => $userId,
    'account_id' => $accountId,
    'is_read' => isset($_GET['read']) ? (int)$_GET['read'] : null,
    'limit' => 50
];

$notifications = access_get_notifications($filters);
$unreadCount = access_get_unread_notification_count($userId, $accountId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    access_mark_all_notifications_read($userId, $accountId);
    header('Location: index.php');
    exit;
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Notifications <?php if ($unreadCount > 0): ?><span class="badge badge-warning"><?php echo $unreadCount; ?> unread</span><?php endif; ?></h1>
        <div class="access-actions">
            <?php if ($unreadCount > 0): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="mark_all_read" value="1">
                    <button type="submit" class="btn btn-secondary">Mark All Read</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="access-table-container">
        <?php if (empty($notifications)): ?>
            <p class="text-center">No notifications found.</p>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                        <div class="notification-content">
                            <h3><?php echo htmlspecialchars($notification['title']); ?></h3>
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small><?php echo access_format_date($notification['created_at']); ?></small>
                        </div>
                        <div class="notification-actions">
                            <?php if (!$notification['is_read']): ?>
                                <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-secondary">Mark Read</a>
                            <?php endif; ?>
                            <?php if ($notification['action_url']): ?>
                                <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="btn btn-sm btn-primary">View</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasBaseLayout) endLayout(); ?>

