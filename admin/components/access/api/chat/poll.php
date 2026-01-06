<?php
/**
 * Access Component - Chat Polling API
 * Polls for new chat messages (AJAX polling endpoint)
 */

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'messages' => [], 'error' => ''];

try {
    $chatSessionId = isset($_GET['chat_session_id']) ? (int)$_GET['chat_session_id'] : 0;
    $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : null;
    $userId = $_SESSION['access_user_id'] ?? null;
    
    if (empty($chatSessionId)) {
        $response['error'] = 'Chat session ID required';
        echo json_encode($response);
        exit;
    }
    
    if (empty($userId)) {
        $response['error'] = 'User not authenticated';
        echo json_encode($response);
        exit;
    }
    
    // Verify user has access to this chat
    $chat = access_get_chat_session($chatSessionId);
    if (!$chat) {
        $response['error'] = 'Chat session not found';
        echo json_encode($response);
        exit;
    }
    
    // Check if user is part of this chat (customer or admin)
    $hasAccess = false;
    if ($chat['user_id'] == $userId || $chat['admin_user_id'] == $userId) {
        $hasAccess = true;
    }
    
    // Check if user has permission to view all chats (admin)
    if (!$hasAccess && access_user_has_permission($userId, 'view_chats')) {
        $hasAccess = true;
    }
    
    if (!$hasAccess) {
        $response['error'] = 'Access denied';
        echo json_encode($response);
        exit;
    }
    
    // Get new messages
    $messages = access_get_chat_messages($chatSessionId, $sinceId);
    
    // Mark messages as read for current user
    if (!empty($messages)) {
        access_mark_chat_messages_read($chatSessionId, $userId);
    }
    
    $response['success'] = true;
    $response['messages'] = $messages;
    $response['last_message_id'] = !empty($messages) ? end($messages)['id'] : $sinceId;
    
} catch (Exception $e) {
    $response['error'] = 'Server error: ' . $e->getMessage();
    error_log("Access Chat Poll Error: " . $e->getMessage());
}

echo json_encode($response);

