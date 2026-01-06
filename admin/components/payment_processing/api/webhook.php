<?php
/**
 * Payment Processing Component - Webhook Receiver
 * Receives webhook events from payment gateways
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/webhook-handler.php';

// Get raw input
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Get gateway ID from query parameter or header
$gatewayId = $_GET['gateway_id'] ?? null;
if (empty($gatewayId)) {
    // Try to detect gateway from headers or payload
    // This would need to be implemented per gateway
    http_response_code(400);
    echo json_encode(['error' => 'Gateway ID required']);
    exit;
}

// Get signature from headers
$signature = null;
foreach ($headers as $key => $value) {
    $lowerKey = strtolower($key);
    if (in_array($lowerKey, ['x-signature', 'stripe-signature', 'paypal-transmission-sig', 'signature'])) {
        $signature = $value;
        break;
    }
}

// Decode payload
$payloadData = json_decode($payload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $payloadData = $payload; // Keep as string if not JSON
}

// Process webhook
$result = payment_processing_process_webhook($gatewayId, $payloadData, $signature);

// Return response
if ($result['success']) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Webhook processed']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Webhook processing failed']);
}

