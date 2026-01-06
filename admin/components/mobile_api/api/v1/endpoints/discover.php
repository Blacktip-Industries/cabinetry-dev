<?php
/**
 * Mobile API - Endpoint Discovery
 */

if ($method === 'GET') {
    $endpoints = mobile_api_get_endpoints(true);
    
    echo json_encode([
        'success' => true,
        'endpoints' => $endpoints,
        'count' => count($endpoints)
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);

