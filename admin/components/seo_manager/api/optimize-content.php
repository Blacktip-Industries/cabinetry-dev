<?php
/**
 * SEO Manager Component - Optimize Content API
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
$url = $input['url'] ?? $_POST['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'URL is required']);
    exit;
}

$page = seo_manager_get_page_by_url($url);
if (!$page) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Page not found']);
    exit;
}

$result = seo_manager_optimize_page_content($page['id']);

if ($result['success']) {
    echo json_encode(['success' => true, 'suggestions' => $result['suggestions'] ?? 0]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unknown error']);
}

