<?php
/**
 * Header Export Handler
 * Exports header configuration (with or without images)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die('Unauthorized');
}

$headerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$includeImages = isset($_GET['images']) && $_GET['images'] === '1';

if ($headerId <= 0) {
    http_response_code(400);
    die('Invalid header ID');
}

$header = getScheduledHeaderById($headerId);
if (!$header) {
    http_response_code(404);
    die('Header not found');
}

// Prepare export data
$exportData = [
    'version' => '1.0',
    'exported_at' => date('Y-m-d H:i:s'),
    'header' => [
        'name' => $header['name'],
        'description' => $header['description'],
        'is_default' => $header['is_default'],
        'priority' => $header['priority'],
        'display_location' => $header['display_location'],
        'background_color' => $header['background_color'],
        'background_image' => $header['background_image'],
        'background_position' => $header['background_position'],
        'background_size' => $header['background_size'],
        'background_repeat' => $header['background_repeat'],
        'header_height' => $header['header_height'],
        'transition_type' => $header['transition_type'],
        'transition_duration' => $header['transition_duration'],
        'timezone' => $header['timezone'],
        'is_recurring' => $header['is_recurring'],
        'recurrence_type' => $header['recurrence_type'],
        'recurrence_day' => $header['recurrence_day'],
        'recurrence_month' => $header['recurrence_month'],
        'start_date' => $header['start_date'],
        'start_time' => $header['start_time'],
        'end_date' => $header['end_date'],
        'end_time' => $header['end_time'],
        'logo_path' => $header['logo_path'],
        'logo_position' => $header['logo_position'],
        'search_bar_visible' => $header['search_bar_visible'],
        'search_bar_style' => $header['search_bar_style'],
        'menu_items_visible' => $header['menu_items_visible'],
        'menu_items_style' => $header['menu_items_style'],
        'user_info_visible' => $header['user_info_visible'],
        'user_info_style' => $header['user_info_style']
    ],
    'images' => $header['images'] ?? [],
    'text_overlays' => $header['text_overlays'] ?? [],
    'ctas' => $header['ctas'] ?? []
];

// If including images, create a zip file
if ($includeImages) {
    $zip = new ZipArchive();
    $zipFilename = tempnam(sys_get_temp_dir(), 'header_export_') . '.zip';
    
    if ($zip->open($zipFilename, ZipArchive::CREATE) === TRUE) {
        // Add JSON config
        $zip->addFromString('header_config.json', json_encode($exportData, JSON_PRETTY_PRINT));
        
        // Add images
        foreach ($exportData['images'] as $image) {
            if (!empty($image['image_path'])) {
                $imagePath = __DIR__ . '/../../' . $image['image_path'];
                if (file_exists($imagePath)) {
                    $zip->addFile($imagePath, 'images/' . basename($imagePath));
                }
            }
            if (!empty($image['image_path_webp'])) {
                $webpPath = __DIR__ . '/../../' . $image['image_path_webp'];
                if (file_exists($webpPath)) {
                    $zip->addFile($webpPath, 'images/' . basename($webpPath));
                }
            }
        }
        
        $zip->close();
        
        // Send zip file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="header_' . $headerId . '_export.zip"');
        header('Content-Length: ' . filesize($zipFilename));
        readfile($zipFilename);
        unlink($zipFilename);
        exit;
    } else {
        http_response_code(500);
        die('Failed to create export file');
    }
} else {
    // Export JSON only
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="header_' . $headerId . '_export.json"');
    echo json_encode($exportData, JSON_PRETTY_PRINT);
    exit;
}

