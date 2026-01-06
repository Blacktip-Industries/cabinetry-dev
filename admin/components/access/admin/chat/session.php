<?php
/**
 * Access Component - Chat Session Interface
 * Real-time chat window
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Chat Session', true, 'access_chat');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Chat Session</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
        <link rel="stylesheet" href="../../assets/css/chat.css">
    </head>
    <body>
    <?php
}

$chatSessionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$chat = $chatSessionId ? access_get_chat_session($chatSessionId) : null;

if (!$chat) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../../../login.php');
    exit;
}

// Check access
$hasAccess = ($chat['admin_user_id'] == $userId || access_user_has_permission($userId, 'manage_chats'));

if (!$hasAccess) {
    header('Location: index.php');
    exit;
}

// Assign chat to admin if unassigned
if (!$chat['admin_user_id'] && access_user_has_permission($userId, 'manage_chats')) {
    access_assign_chat_to_admin($chatSessionId, $userId);
    $chat = access_get_chat_session($chatSessionId); // Refresh
}

$messages = access_get_chat_messages($chatSessionId);
$lastMessageId = !empty($messages) ? end($messages)['id'] : 0;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'close') {
        if (access_close_chat_session($chatSessionId, $userId)) {
            $askForward = access_get_parameter('Chat', 'ask_before_forward', 'yes');
            if ($askForward === 'yes') {
                header('Location: index.php?forward=' . $chatSessionId);
            } else {
                header('Location: index.php');
            }
            exit;
        }
    } elseif ($action === 'forward') {
        if (access_forward_chat_to_customer($chatSessionId, $userId)) {
            header('Location: index.php?forwarded=1');
            exit;
        }
    }
}

$pollInterval = (int)access_get_parameter('Chat', 'poll_interval_seconds', 3) * 1000;
$apiBase = '/admin/components/access/api/chat';

?>
<div class="access-container">
    <div class="chat-header">
        <div class="chat-header-info">
            <h1>Chat Session #<?php echo $chatSessionId; ?></h1>
            <div class="chat-meta">
                <span><strong>Customer:</strong> <?php echo htmlspecialchars(trim(($chat['user_first_name'] ?? '') . ' ' . ($chat['user_last_name'] ?? '')) ?: $chat['user_email']); ?></span>
                <span><strong>Account:</strong> <?php echo htmlspecialchars($chat['account_name'] ?? 'N/A'); ?></span>
                <?php if ($chat['subject']): ?>
                    <span><strong>Subject:</strong> <?php echo htmlspecialchars($chat['subject']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="chat-header-actions">
            <form method="POST" style="display: inline;" onsubmit="return confirm('Close this chat session?');">
                <input type="hidden" name="action" value="close">
                <button type="submit" class="btn btn-secondary">Close Chat</button>
            </form>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
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

    // Auto-scroll to bottom
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    scrollToBottom();

    // Poll for new messages
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

    // Start polling
    setInterval(pollMessages, pollInterval);

    // Handle form submission
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
                pollMessages(); // Immediately poll for new messages
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

