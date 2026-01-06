<?php
/**
 * Access Component - Chat History
 */

require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Chat History', true, 'access_chat');
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../../../login.php');
    exit;
}

$filters = [
    'admin_user_id' => $userId,
    'status' => 'closed',
    'limit' => 50
];

$chats = access_get_chat_history($filters);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_chat'])) {
    $chatId = (int)$_POST['forward_chat'];
    if (access_forward_chat_to_customer($chatId, $userId)) {
        $success = 'Chat transcript forwarded successfully!';
    } else {
        $error = 'Failed to forward chat transcript';
    }
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Chat History</h1>
        <a href="index.php" class="btn btn-secondary">Active Chats</a>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Account</th>
                    <th>Subject</th>
                    <th>Started</th>
                    <th>Ended</th>
                    <th>Forwarded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($chats)): ?>
                    <tr><td colspan="7" class="text-center">No chat history found.</td></tr>
                <?php else: ?>
                    <?php foreach ($chats as $chat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(trim(($chat['user_first_name'] ?? '') . ' ' . ($chat['user_last_name'] ?? '')) ?: $chat['user_email']); ?></td>
                            <td><?php echo htmlspecialchars($chat['account_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($chat['subject'] ?? '(No Subject)'); ?></td>
                            <td><?php echo access_format_date($chat['started_at']); ?></td>
                            <td><?php echo $chat['ended_at'] ? access_format_date($chat['ended_at']) : '-'; ?></td>
                            <td>
                                <?php if ($chat['is_forwarded_to_customer']): ?>
                                    <span class="badge badge-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="session.php?id=<?php echo $chat['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <?php if (!$chat['is_forwarded_to_customer']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="forward_chat" value="<?php echo $chat['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Forward chat transcript to customer?');">Forward</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($hasBaseLayout) endLayout(); ?>

