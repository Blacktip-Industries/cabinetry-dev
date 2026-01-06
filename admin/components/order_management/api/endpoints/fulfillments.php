<?php
/**
 * Order Management API - Fulfillments Endpoint
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/fulfillment.php';

$method = $_SERVER['REQUEST_METHOD'];
$pathParts = explode('/', trim($_GET['path'] ?? '', '/'));
$fulfillmentId = $pathParts[1] ?? null;

switch ($method) {
    case 'GET':
        if ($fulfillmentId) {
            // Get single fulfillment
            $fulfillment = order_management_get_fulfillment($fulfillmentId);
            if ($fulfillment) {
                echo json_encode(['success' => true, 'data' => $fulfillment]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Fulfillment not found']);
            }
        } else {
            // List fulfillments
            $orderId = $_GET['order_id'] ?? null;
            if ($orderId) {
                $fulfillments = order_management_get_order_fulfillments(intval($orderId));
                echo json_encode(['success' => true, 'data' => $fulfillments]);
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

