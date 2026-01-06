<?php
/**
 * Layout Component - Preview Page
 * Display preview of templates and design systems
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/preview_engine.php';
require_once __DIR__ . '/../../includes/config.php';

$previewType = $_GET['type'] ?? '';
$resourceId = (int)($_GET['id'] ?? 0);
$properties = [];

if (isset($_GET['props'])) {
    $properties = json_decode(base64_decode($_GET['props']), true) ?: [];
}

if ($previewType === 'element_template' && $resourceId > 0) {
    $preview = layout_preview_element_template($resourceId, $properties);
    echo $preview;
    exit;
} elseif ($previewType === 'design_system' && $resourceId > 0) {
    $preview = layout_preview_design_system($resourceId);
    echo $preview;
    exit;
} else {
    header('HTTP/1.0 404 Not Found');
    echo 'Preview not found';
    exit;
}

