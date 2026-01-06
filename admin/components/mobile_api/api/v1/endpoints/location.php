<?php
/**
 * Mobile API - Location Tracking Endpoints
 */

require_once __DIR__ . '/../../../core/location_tracking.php';
require_once __DIR__ . '/../../../core/maps_integration.php';

if ($method === 'POST' && ($segments[1] ?? '') === 'start') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $auth['user_id'] ?? null;
    $orderId = $data['order_id'] ?? null;
    $collectionAddressId = $data['collection_address_id'] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    $result = mobile_api_start_tracking($userId, $orderId, $collectionAddressId);
    echo json_encode($result);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? '';
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $accuracy = $data['accuracy'] ?? null;
    $heading = $data['heading'] ?? null;
    $speed = $data['speed'] ?? null;
    
    if (empty($sessionId) || $latitude === null || $longitude === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session ID, latitude, and longitude required']);
        exit;
    }
    
    $result = mobile_api_update_location($sessionId, $latitude, $longitude, $accuracy, $heading, $speed);
    echo json_encode($result);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'stop') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? '';
    
    if (empty($sessionId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session ID required']);
        exit;
    }
    
    $result = mobile_api_stop_tracking($sessionId);
    echo json_encode(['success' => $result]);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'status') {
    $sessionId = $_GET['session_id'] ?? '';
    
    if (empty($sessionId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session ID required']);
        exit;
    }
    
    $status = mobile_api_get_tracking_status($sessionId);
    if ($status) {
        echo json_encode(['success' => true, 'status' => $status]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Tracking session not found']);
    }
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'history') {
    $sessionId = $_GET['session_id'] ?? '';
    
    if (empty($sessionId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session ID required']);
        exit;
    }
    
    $history = mobile_api_get_location_history($sessionId);
    echo json_encode(['success' => true, 'history' => $history]);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'eta') {
    $originLat = $_GET['origin_lat'] ?? null;
    $originLng = $_GET['origin_lng'] ?? null;
    $destLat = $_GET['dest_lat'] ?? null;
    $destLng = $_GET['dest_lng'] ?? null;
    
    if ($originLat === null || $originLng === null || $destLat === null || $destLng === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Origin and destination coordinates required']);
        exit;
    }
    
    $result = mobile_api_calculate_eta($originLat, $originLng, $destLat, $destLng);
    echo json_encode($result);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);

