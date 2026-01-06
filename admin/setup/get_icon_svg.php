<?php
/**
 * AJAX endpoint to generate and cache Material Icon SVGs on-demand
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$iconName = $_GET['name'] ?? '';
$style = $_GET['style'] ?? 'outlined';
$fill = isset($_GET['fill']) ? (int)$_GET['fill'] : 0;
$weight = isset($_GET['weight']) ? (int)$_GET['weight'] : 400;
$grade = isset($_GET['grade']) ? (int)$_GET['grade'] : 0;
$opsz = isset($_GET['opsz']) ? (int)$_GET['opsz'] : 24;
$iconId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($iconName)) {
    echo json_encode(['success' => false, 'error' => 'Icon name required']);
    exit;
}

// Generate SVG from Iconify API
$svgPath = getIconSVGFromAPI($iconName, $style, $fill, $weight, $grade, $opsz);

if ($svgPath !== false) {
    // Cache the SVG in database if we have an icon ID
    $cached = false;
    if ($iconId > 0) {
        $cached = cacheIconSVG($iconId, $svgPath);
    }
    
    echo json_encode([
        'success' => true,
        'svg' => $svgPath,
        'cached' => $cached
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate SVG'
    ]);
}

