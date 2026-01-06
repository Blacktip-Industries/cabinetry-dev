<?php
/**
 * Access Component - Chat Send API
 * Sends a chat message
 */

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message_id' => null, 'error' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['error'] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }
    
    $chatSessionId = isset($_POST['chat_session_id']) ? (int)$_POST['chat_session_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $userId = $_SESSION['access_user_id'] ?? null;
    
    if (empty($chatSessionId)) {
        $response['error'] = 'Chat session ID required';
        echo json_encode($response);
        exit;
    }
    
    if (empty($message)) {
        $response['error'] = 'Message cannot be empty';
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
    
    // Check if user is part of this chat
    $hasAccess = false;
    $senderType = 'user';
    
    if ($chat['user_id'] == $userId) {
        $hasAccess = true;
        $senderType = 'user';
    } elseif ($chat['admin_user_id'] == $userId) {
        $hasAccess = true;
        $senderType = 'admin';
    }
    
    // Check if user has permission to manage chats (admin)
    if (!$hasAccess && access_user_has_permission($userId, 'manage_chats')) {
        $hasAccess = true;
        $senderType = 'admin';
    }
    
    if (!$hasAccess) {
        $response['error'] = 'Access denied';
        echo json_encode($response);
        exit;
    }
    
    // Check if chat is closed
    if ($chat['status'] === 'closed') {
        $response['error'] = 'Chat session is closed';
        echo json_encode($response);
        exit;
    }
    
    // Send message
    $messageId = access_send_chat_message($chatSessionId, [
        'sender_user_id' => $userId,
        'sender_type' => $senderType,
        'message' => $message
    ]);
    
    if ($messageId) {
        $response['success'] = true;
        $response['message_id'] = $messageId;
    } else {
        $response['error'] = 'Failed to send message';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Server error: ' . $e->getMessage();
    error_log("Access Chat Send Error: " . $e->getMessage());
}

echo json_encode($response);

