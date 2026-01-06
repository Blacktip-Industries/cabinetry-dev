<?php
/**
 * Order Management API - Returns Endpoint
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/returns.php';

$method = $_SERVER['REQUEST_METHOD'];
$pathParts = explode('/', trim($_GET['path'] ?? '', '/'));
$returnId = $pathParts[1] ?? null;

switch ($method) {
    case 'GET':
        if ($returnId) {
            // Get single return
            $return = order_management_get_return($returnId);
            if ($return) {
                echo json_encode(['success' => true, 'data' => $return]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Return not found']);
            }
        } else {
            // List returns
            $orderId = $_GET['order_id'] ?? null;
            if ($orderId) {
                $returns = order_management_get_order_returns(intval($orderId));
                echo json_encode(['success' => true, 'data' => $returns]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'order_id parameter required']);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

