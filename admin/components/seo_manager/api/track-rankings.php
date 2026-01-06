<?php
/**
 * SEO Manager Component - Track Rankings API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/rank-tracker.php';

if (defined('SEO_MANAGER_NOT_INSTALLED')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Component not installed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$result = seo_manager_track_ranking(
    $input['keyword_id'] ?? 0,
    $input['search_engine'] ?? 'google',
    $input['position'] ?? null,
    $input
);

if ($result) {
    echo json_encode(['success' => true, 'ranking_id' => $result]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to track ranking']);
}

