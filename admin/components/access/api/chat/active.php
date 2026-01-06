<?php
/**
 * Access Component - Active Chats API
 * Get active chat sessions
 */

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'chats' => [], 'error' => ''];

try {
    $userId = $_SESSION['access_user_id'] ?? null;
    
    if (empty($userId)) {
        $response['error'] = 'User not authenticated';
        echo json_encode($response);
        exit;
    }
    
    // Check if user is admin
    $isAdmin = access_user_has_permission($userId, 'manage_chats');
    
    if ($isAdmin) {
        // Admin can see all active chats
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $chats = access_get_active_chats(null, $status);
    } else {
        // Customer can only see their own chats
        $chats = access_get_active_chats(null, 'active');
        $chats = array_filter($chats, function($chat) use ($userId) {
            return $chat['user_id'] == $userId;
        });
        $chats = array_values($chats);
    }
    
    $response['success'] = true;
    $response['chats'] = $chats;
    
} catch (Exception $e) {
    $response['error'] = 'Server error: ' . $e->getMessage();
    error_log("Access Active Chats Error: " . $e->getMessage());
}

echo json_encode($response);

