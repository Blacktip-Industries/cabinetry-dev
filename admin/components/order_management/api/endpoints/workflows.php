<?php
/**
 * Order Management API - Workflows Endpoint
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/workflows.php';

$method = $_SERVER['REQUEST_METHOD'];
$pathParts = explode('/', trim($_GET['path'] ?? '', '/'));
$workflowId = $pathParts[1] ?? null;

switch ($method) {
    case 'GET':
        if ($workflowId) {
            // Get single workflow
            $workflow = order_management_get_workflow($workflowId);
            if ($workflow) {
                echo json_encode(['success' => true, 'data' => $workflow]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Workflow not found']);
            }
        } else {
            // List workflows
            $workflows = order_management_get_workflows();
            echo json_encode(['success' => true, 'data' => $workflows]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

