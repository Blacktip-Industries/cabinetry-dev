<?php
/**
 * Access Component - Frontend Chat Session
 */

require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Chat', false, 'access_chat');
}

$chatSessionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$chat = $chatSessionId ? access_get_chat_session($chatSessionId) : null;

if (!$chat) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

// Check access
if ($chat['user_id'] != $userId) {
    header('Location: index.php');
    exit;
}

$messages = access_get_chat_messages($chatSessionId);
$lastMessageId = !empty($messages) ? end($messages)['id'] : 0;
$pollInterval = (int)access_get_parameter('Chat', 'poll_interval_seconds', 3) * 1000;
$apiBase = '/admin/components/access/api/chat';

?>
<div class="access-container">
    <div class="chat-header">
        <h1><?php echo htmlspecialchars($chat['subject'] ?? 'Chat Session #' . $chatSessionId); ?></h1>
        <div class="chat-status">
            <span class="badge badge-<?php echo $chat['status'] === 'active' ? 'success' : 'warning'; ?>">
                <?php echo ucfirst($chat['status']); ?>
            </span>
        </div>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </div>

    <div class="chat-container">
        <div class="chat-messages" id="chatMessages">
            <?php foreach ($messages as $message): ?>
                <div class="chat-message <?php echo $message['sender_type'] === 'admin' ? 'message-admin' : 'message-user'; ?>">
                    <div class="message-header">
                        <strong><?php echo htmlspecialchars(trim(($message['sender_first_name'] ?? '') . ' ' . ($message['sender_last_name'] ?? '')) ?: $message['sender_email']); ?></strong>
                        <span class="message-time"><?php echo access_format_date($message['created_at']); ?></span>
                    </div>
                    <div class="message-content">
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-input-container">
            <form id="chatForm" method="POST" action="<?php echo $apiBase; ?>/send.php">
                <input type="hidden" name="chat_session_id" value="<?php echo $chatSessionId; ?>">
                <div class="chat-input-wrapper">
                    <textarea id="chatMessage" name="message" rows="3" placeholder="Type your message..." required></textarea>
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../assets/js/chat.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const chatMessageInput = document.getElementById('chatMessage');
    let lastMessageId = <?php echo $lastMessageId; ?>;
    const chatSessionId = <?php echo $chatSessionId; ?>;
    const pollInterval = <?php echo $pollInterval; ?>;

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    scrollToBottom();

    function pollMessages() {
        fetch(`<?php echo $apiBase; ?>/poll.php?chat_session_id=${chatSessionId}&since_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `chat-message ${msg.sender_type === 'admin' ? 'message-admin' : 'message-user'}`;
                        messageDiv.innerHTML = `
                            <div class="message-header">
                                <strong>${escapeHtml(msg.sender_first_name + ' ' + msg.sender_last_name || msg.sender_email)}</strong>
                                <span class="message-time">${new Date(msg.created_at).toLocaleString()}</span>
                            </div>
                            <div class="message-content">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>
                        `;
                        chatMessages.appendChild(messageDiv);
                        lastMessageId = msg.id;
                    });
                    scrollToBottom();
                }
            })
            .catch(error => console.error('Poll error:', error));
    }

    setInterval(pollMessages, pollInterval);

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = chatMessageInput.value.trim();
        if (!message) return;

        const formData = new FormData(chatForm);
        fetch(chatForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chatMessageInput.value = '';
                pollMessages();
            } else {
                alert('Error: ' + (data.error || 'Failed to send message'));
            }
        })
        .catch(error => {
            console.error('Send error:', error);
            alert('Error sending message');
        });
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php if ($hasBaseLayout) endLayout(); ?>

