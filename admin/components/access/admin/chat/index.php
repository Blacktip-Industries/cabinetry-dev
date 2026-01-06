<?php
/**
 * Access Component - Chat Sessions Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Chat Sessions', true, 'access_chat');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Chat Sessions</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
        <link rel="stylesheet" href="../../assets/css/chat.css">
    </head>
    <body>
    <?php
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../../../login.php');
    exit;
}

$status = $_GET['status'] ?? null;
$chats = access_get_active_chats($userId, $status);
$waitingCount = count(access_get_active_chats($userId, 'waiting'));
$activeCount = count(access_get_active_chats($userId, 'active'));

?>
<div class="access-container">
    <div class="access-header">
        <h1>Chat Sessions</h1>
        <div class="access-actions">
            <a href="history.php" class="btn btn-secondary">Chat History</a>
            <a href="settings.php" class="btn btn-secondary">Settings</a>
        </div>
    </div>

    <div class="chat-stats">
        <div class="stat-card">
            <div class="stat-value"><?php echo $waitingCount; ?></div>
            <div class="stat-label">Waiting</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $activeCount; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>

    <div class="access-filters">
        <a href="index.php" class="btn btn-sm <?php echo $status === null ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
        <a href="index.php?status=waiting" class="btn btn-sm <?php echo $status === 'waiting' ? 'btn-primary' : 'btn-secondary'; ?>">Waiting</a>
        <a href="index.php?status=active" class="btn btn-sm <?php echo $status === 'active' ? 'btn-primary' : 'btn-secondary'; ?>">Active</a>
        <a href="index.php?status=closed" class="btn btn-sm <?php echo $status === 'closed' ? 'btn-primary' : 'btn-secondary'; ?>">Closed</a>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Account</th>
                    <th>Subject</th>
                    <th>Assigned Admin</th>
                    <th>Status</th>
                    <th>Last Message</th>
                    <th>Unread</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($chats)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No chat sessions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($chats as $chat): ?>
                        <tr class="<?php echo $chat['status'] === 'waiting' ? 'chat-waiting' : ''; ?>">
                            <td>
                                <?php echo htmlspecialchars(trim(($chat['user_first_name'] ?? '') . ' ' . ($chat['user_last_name'] ?? '')) ?: $chat['user_email']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($chat['account_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($chat['subject'] ?? '(No Subject)'); ?></td>
                            <td>
                                <?php if ($chat['admin_user_id']): ?>
                                    <?php echo htmlspecialchars(trim(($chat['admin_first_name'] ?? '') . ' ' . ($chat['admin_last_name'] ?? '')) ?: $chat['admin_email']); ?>
                                <?php else: ?>
                                    <em>Unassigned</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $chat['status'] === 'active' ? 'success' : ($chat['status'] === 'waiting' ? 'warning' : 'secondary'); ?>">
                                    <?php echo ucfirst($chat['status']); ?>
                                </span>
                            </td>
                            <td><?php echo access_format_date($chat['last_message_at']); ?></td>
                            <td>
                                <?php if ($chat['unread_count'] > 0): ?>
                                    <span class="badge badge-warning"><?php echo $chat['unread_count']; ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="session.php?id=<?php echo $chat['id']; ?>" class="btn btn-sm btn-primary">Open</a>
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

