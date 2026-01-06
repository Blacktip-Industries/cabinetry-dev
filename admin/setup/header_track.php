<?php
/**
 * CTA Click Tracking Handler
 * Handles AJAX requests for tracking CTA clicks
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$headerId = isset($input['header_id']) ? (int)$input['header_id'] : 0;
$ctaId = isset($input['cta_id']) ? (int)$input['cta_id'] : null;
$location = $input['location'] ?? 'admin';
$eventType = $input['event_type'] ?? 'click';
$conversionValue = isset($input['conversion_value']) ? (float)$input['conversion_value'] : null;

if ($headerId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid header ID']);
    exit;
}

$result = trackHeaderEvent($headerId, $eventType, $location, $ctaId, $conversionValue);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to track event']);
}

