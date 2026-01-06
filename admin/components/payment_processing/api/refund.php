<?php
/**
 * Payment Processing Component - Refund API Endpoint
 * Handles refund processing requests
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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Validate required fields
if (empty($input['transaction_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required field: transaction_id']);
    exit;
}

// Process refund
$result = payment_processing_process_refund($input);

// Return response
if ($result['success']) {
    http_response_code(200);
} else {
    http_response_code(400);
}

echo json_encode($result);

