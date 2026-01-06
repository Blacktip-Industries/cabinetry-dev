<?php
/**
 * Order Management API - Orders Endpoint
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$pathParts = explode('/', trim($_GET['path'] ?? '', '/'));
$orderId = $pathParts[1] ?? null;

switch ($method) {
    case 'GET':
        if ($orderId) {
            // Get single order
            if (!order_management_is_commerce_available()) {
                http_response_code(503);
                echo json_encode(['success' => false, 'error' => 'Commerce component not available']);
                exit;
            }
            
            $conn = order_management_get_db_connection();
            $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();
            
            if ($order) {
                echo json_encode(['success' => true, 'data' => $order]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Order not found']);
            }
        } else {
            // List orders
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            if (!order_management_is_commerce_available()) {
                http_response_code(503);
                echo json_encode(['success' => false, 'error' => 'Commerce component not available']);
                exit;
            }
            
            $conn = order_management_get_db_connection();
            $query = "SELECT * FROM commerce_orders ORDER BY id DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            $stmt->close();
            
            echo json_encode(['success' => true, 'data' => $orders, 'count' => count($orders)]);
        }
        break;
        
    case 'POST':
        // Create order (would integrate with commerce component)
        http_response_code(501);
        echo json_encode(['success' => false, 'error' => 'Not implemented']);
        break;
        
    case 'PUT':
        // Update order
        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Order ID required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        // Update logic would go here
        http_response_code(501);
        echo json_encode(['success' => false, 'error' => 'Not implemented']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

