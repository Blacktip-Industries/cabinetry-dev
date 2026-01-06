<?php
/**
 * Access Component - Frontend Chat History
 */

require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Chat History', false, 'access_chat');
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

$filters = [
    'user_id' => $userId,
    'status' => 'closed',
    'limit' => 50
];

$chats = access_get_chat_history($filters);

?>
<div class="access-container">
    <div class="access-header">
        <h1>Chat History</h1>
        <a href="index.php" class="btn btn-secondary">New Chat</a>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Started</th>
                    <th>Ended</th>
                    <th>Forwarded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($chats)): ?>
                    <tr><td colspan="5" class="text-center">No chat history found.</td></tr>
                <?php else: ?>
                    <?php foreach ($chats as $chat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($chat['subject'] ?? 'Chat #' . $chat['id']); ?></td>
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
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($hasBaseLayout) endLayout(); ?>

