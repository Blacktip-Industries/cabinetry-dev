<?php
/**
 * Mobile API - Push Notifications Endpoints
 */

require_once __DIR__ . '/../../../core/push_notifications.php';

if ($method === 'GET' && ($segments[1] ?? '') === 'vapid-keys') {
    $keys = mobile_api_get_vapid_keys();
    echo json_encode($keys);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'subscribe') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $auth['user_id'] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    $result = mobile_api_subscribe_push($userId, $data);
    echo json_encode($result);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'unsubscribe') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $auth['user_id'] ?? null;
    $endpoint = $data['endpoint'] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    $result = mobile_api_unsubscribe_push($userId, $endpoint);
    echo json_encode(['success' => $result]);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'send') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? $auth['user_id'] ?? null;
    $title = $data['title'] ?? '';
    $message = $data['message'] ?? '';
    $options = $data['options'] ?? [];
    
    if (!$userId || empty($title) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User ID, title, and message required']);
        exit;
    }
    
    $result = mobile_api_send_push($userId, $title, $message, $options);
    echo json_encode($result);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);

