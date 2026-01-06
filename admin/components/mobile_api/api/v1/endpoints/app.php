<?php
/**
 * Mobile API - App Builder Endpoints
 */

require_once __DIR__ . '/../../../core/app_builder.php';
require_once __DIR__ . '/../../../core/manifest.php';
require_once __DIR__ . '/../../../core/service_worker.php';

if ($method === 'GET' && ($segments[1] ?? '') === 'features') {
    $features = mobile_api_get_available_features();
    echo json_encode(['success' => true, 'features' => $features]);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'layout') {
    $layoutId = $_GET['layout_id'] ?? null;
    $result = mobile_api_generate_app_shell($layoutId);
    echo json_encode($result);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'layout') {
    $data = json_decode(file_get_contents('php://input'), true);
    $layoutName = $data['layout_name'] ?? '';
    $layoutConfig = $data['layout_config'] ?? [];
    $setAsDefault = isset($data['set_as_default']) ? (bool)$data['set_as_default'] : false;
    
    if (empty($layoutName) || empty($layoutConfig)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Layout name and config required']);
        exit;
    }
    
    $result = mobile_api_save_app_layout($layoutName, $layoutConfig, $setAsDefault);
    echo json_encode($result);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'preview') {
    $data = json_decode(file_get_contents('php://input'), true);
    $layoutConfig = $data['layout_config'] ?? [];
    
    if (empty($layoutConfig)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Layout config required']);
        exit;
    }
    
    $result = mobile_api_preview_layout($layoutConfig);
    echo json_encode($result);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'manifest') {
    $manifest = mobile_api_generate_manifest();
    header('Content-Type: application/json');
    echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'service-worker') {
    $sw = mobile_api_generate_service_worker();
    header('Content-Type: application/javascript');
    echo $sw;
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);

