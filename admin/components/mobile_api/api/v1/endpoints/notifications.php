<?php
/**
 * Mobile API - Notifications Endpoints
 */

require_once __DIR__ . '/../../../core/notifications.php';

if ($method === 'POST' && empty($segments[1])) {
    $data = json_decode(file_get_contents('php://input'), true);
    $type = $data['type'] ?? '';
    $recipientType = $data['recipient_type'] ?? 'admin';
    
    if (empty($type)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Notification type required']);
        exit;
    }
    
    $result = mobile_api_send_notification($type, $recipientType, $data);
    echo json_encode($result);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'rules') {
    $conn = mobile_api_get_db_connection();
    if ($conn) {
        $result = $conn->query("SELECT * FROM mobile_api_notification_rules WHERE is_active = 1 ORDER BY rule_name");
        $rules = [];
        while ($row = $result->fetch_assoc()) {
            $rules[] = $row;
        }
        echo json_encode(['success' => true, 'rules' => $rules]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    }
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'rules') {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = mobile_api_create_notification_rule($data);
    echo json_encode($result);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);

