<?php
/**
 * Payment Processing Component - Subscription API Endpoint
 * Handles subscription management requests
 */

require_once __DIR__ . '/../includes/payment-processor.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if component is installed
if (!payment_processing_is_installed()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Payment Processing component is not installed']);
    exit;
}

// Get action from query parameter or request body
$action = $_GET['action'] ?? $_POST['action'] ?? 'create';

// Get request data
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

switch ($action) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $result = payment_processing_create_subscription($input);
        break;
        
    case 'cancel':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        if (empty($input['subscription_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required field: subscription_id']);
            exit;
        }
        
        $result = payment_processing_cancel_subscription($input['subscription_id']);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;
}

// Return response
if ($result['success']) {
    http_response_code(200);
} else {
    http_response_code(400);
}

echo json_encode($result);

