<?php
/**
 * Formula Builder Component - Notification Center
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/notifications.php';

$userId = $_SESSION['user_id'] ?? 1; // Default to user 1 for demo
$notifications = formula_builder_get_notifications($userId, ['read' => false, 'limit' => 50]);
$allNotifications = formula_builder_get_notifications($userId, ['limit' => 100]);

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = (int)$_POST['notification_id'];
    formula_builder_mark_notification_read($notificationId, $userId);
    header('Location: index.php');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Notifications - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 2px; border: none; cursor: pointer; font-size: 12px; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .notification { padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; background: #f8f9fa; border-radius: 4px; }
        .notification.read { opacity: 0.6; border-left-color: #6c757d; }
        .notification-type { font-size: 11px; color: #666; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>Notifications</h1>
    <a href="../formulas/index.php" class="btn btn-secondary">Back to Formulas</a>
    
    <div style="margin-top: 20px;">
        <h2>Unread Notifications (<?php echo count($notifications); ?>)</h2>
        <?php if (empty($notifications)): ?>
            <p>No unread notifications</p>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification">
                    <div class="notification-type"><?php echo htmlspecialchars($notification['notification_type']); ?> - <?php echo htmlspecialchars($notification['channel']); ?></div>
                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                    <small><?php echo date('Y-m-d H:i:s', strtotime($notification['created_at'])); ?></small>
                    <form method="POST" style="display: inline; margin-left: 10px;">
                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                        <button type="submit" name="mark_read" class="btn">Mark as Read</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>All Notifications</h2>
        <?php if (empty($allNotifications)): ?>
            <p>No notifications</p>
        <?php else: ?>
            <?php foreach ($allNotifications as $notification): ?>
                <div class="notification <?php echo $notification['read'] ? 'read' : ''; ?>">
                    <div class="notification-type"><?php echo htmlspecialchars($notification['notification_type']); ?> - <?php echo htmlspecialchars($notification['channel']); ?></div>
                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                    <small><?php echo date('Y-m-d H:i:s', strtotime($notification['created_at'])); ?></small>
                    <?php if (!$notification['read']): ?>
                        <form method="POST" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" name="mark_read" class="btn">Mark as Read</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

