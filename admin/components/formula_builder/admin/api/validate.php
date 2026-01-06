<?php
/**
 * Formula Builder Component - Validation API Endpoint
 * Provides real-time validation via AJAX
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/validation_realtime.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$formulaCode = $input['formula_code'] ?? '';
$formulaId = isset($input['formula_id']) ? (int)$input['formula_id'] : null;

if (empty($formulaCode)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formula code is required']);
    exit;
}

$validationResult = formula_builder_validate_realtime($formulaCode, $formulaId);
$monacoMarkers = formula_builder_format_validation_for_monaco($validationResult);

echo json_encode([
    'success' => $validationResult['success'],
    'errors' => $validationResult['errors'],
    'warnings' => $validationResult['warnings'],
    'security_warnings' => $validationResult['security_warnings'],
    'performance_warnings' => $validationResult['performance_warnings'],
    'suggestions' => $validationResult['suggestions'],
    'markers' => $monacoMarkers
]);

