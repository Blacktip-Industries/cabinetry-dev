<?php
/**
 * Mobile API - Authentication Endpoints
 */

require_once __DIR__ . '/../../../core/authentication.php';

if ($method === 'POST' && ($segments[1] ?? '') === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    // Validate credentials (would integrate with access component)
    if (function_exists('access_validate_credentials')) {
        $user = access_validate_credentials($username, $password);
        if ($user) {
            $token = mobile_api_generate_jwt($user['id'], ['username' => $username]);
            echo json_encode($token);
            exit;
        }
    }
    
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'refresh') {
    $data = json_decode(file_get_contents('php://input'), true);
    $refreshToken = $data['refresh_token'] ?? '';
    
    $result = mobile_api_refresh_token($refreshToken);
    echo json_encode($result);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'api-key') {
    // Create API key (admin only)
    $data = json_decode(file_get_contents('php://input'), true);
    $keyName = $data['key_name'] ?? '';
    $permissions = $data['permissions'] ?? [];
    $rateLimit = $data['rate_limit'] ?? 60;
    
    $result = mobile_api_create_api_key($keyName, $permissions, $rateLimit);
    echo json_encode($result);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);

