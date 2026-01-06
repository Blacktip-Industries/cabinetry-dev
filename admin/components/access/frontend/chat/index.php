<?php
/**
 * Access Component - Frontend Chat Interface
 */

require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Chat', false, 'access_chat');
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

$accountId = $_SESSION['access_account_id'] ?? null;
if (!$accountId) {
    $account = access_get_user_primary_account($userId);
    $accountId = $account ? $account['account_id'] : null;
}

$isAvailable = access_is_admin_available();
$availableAdmins = access_get_available_admins();

// Get user's active chats
$userChats = access_get_active_chats(null, 'active');
$userChats = array_filter($userChats, function($chat) use ($userId) {
    return $chat['user_id'] == $userId;
});
$userChats = array_values($userChats);

// Create new chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_chat'])) {
    $chatId = access_create_chat_session([
        'user_id' => $userId,
        'account_id' => $accountId,
        'subject' => $_POST['subject'] ?? null,
        'status' => 'waiting'
    ]);
    
    if ($chatId) {
        header('Location: session.php?id=' . $chatId);
        exit;
    }
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Chat Support</h1>
        <a href="history.php" class="btn btn-secondary">Chat History</a>
    </div>

    <?php if ($isAvailable): ?>
        <div class="alert alert-success">
            <strong>Admin Available:</strong> <?php echo count($availableAdmins); ?> admin(s) are online and ready to help.
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <strong>No Admin Available:</strong> All admins are currently offline. You can still start a chat and we'll respond as soon as possible.
        </div>
    <?php endif; ?>

    <?php if (!empty($userChats)): ?>
        <div class="chat-section">
            <h2>Active Chats</h2>
            <div class="chat-list">
                <?php foreach ($userChats as $chat): ?>
                    <div class="chat-item">
                        <div class="chat-info">
                            <h3><?php echo htmlspecialchars($chat['subject'] ?? 'Chat #' . $chat['id']); ?></h3>
                            <small>Last message: <?php echo access_format_date($chat['last_message_at']); ?></small>
                        </div>
                        <div class="chat-actions">
                            <a href="session.php?id=<?php echo $chat['id']; ?>" class="btn btn-primary">Open Chat</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="chat-section">
        <h2>Start New Chat</h2>
        <form method="POST" class="access-form">
            <input type="hidden" name="create_chat" value="1">
            <div class="form-group">
                <label for="subject">Subject (Optional)</label>
                <input type="text" id="subject" name="subject" placeholder="What can we help you with?">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Start Chat</button>
            </div>
        </form>
    </div>
</div>

<?php if ($hasBaseLayout) endLayout(); ?>

