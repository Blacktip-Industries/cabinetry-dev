<?php
/**
 * Mobile API - Offline Sync Endpoints
 */

require_once __DIR__ . '/../../../core/offline_sync.php';

if ($method === 'POST' && ($segments[1] ?? '') === 'queue') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $auth['user_id'] ?? null;
    $deviceId = $data['device_id'] ?? '';
    $endpoint = $data['endpoint'] ?? '';
    $method = $data['method'] ?? 'POST';
    $requestData = $data['request_data'] ?? [];
    
    if (!$userId || empty($deviceId) || empty($endpoint)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User ID, device ID, and endpoint required']);
        exit;
    }
    
    $result = mobile_api_queue_sync_request($userId, $deviceId, $endpoint, $method, $requestData);
    echo json_encode($result);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'status') {
    $deviceId = $_GET['device_id'] ?? '';
    
    if (empty($deviceId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Device ID required']);
        exit;
    }
    
    $status = mobile_api_get_sync_status($deviceId);
    echo json_encode(['success' => true, 'status' => $status]);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'process') {
    $limit = (int)($_POST['limit'] ?? 10);
    $result = mobile_api_process_sync_queue($limit);
    echo json_encode($result);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);

