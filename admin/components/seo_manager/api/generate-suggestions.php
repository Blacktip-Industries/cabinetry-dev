<?php
/**
 * SEO Manager Component - Generate Suggestions API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/content-optimizer.php';

if (defined('SEO_MANAGER_NOT_INSTALLED')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Component not installed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pageId = $input['page_id'] ?? $_POST['page_id'] ?? 0;

if (empty($pageId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Page ID is required']);
    exit;
}

$result = seo_manager_optimize_page_content($pageId);

echo json_encode($result);

