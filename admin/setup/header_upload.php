<?php
/**
 * Header Image Upload Handler
 * Handles image uploads with optimization (resize, WebP conversion)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, SVG']);
    exit;
}

// Validate file size
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum size: 5MB']);
    exit;
}

// Get header height for optimization (optional parameter)
$headerHeight = isset($_POST['header_height']) ? (int)$_POST['header_height'] : 200;
$isAIGenerated = isset($_POST['is_ai_generated']) && $_POST['is_ai_generated'] === 'true';

// Create upload directories if they don't exist
$baseDir = __DIR__ . '/../../uploads/headers/';
$dirs = [
    $baseDir . 'originals',
    $baseDir . 'optimized',
    $baseDir . 'webp'
];

if ($isAIGenerated) {
    $dirs[] = $baseDir . 'ai-generated';
}

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('header_', true) . '.' . $extension;
$originalPath = $baseDir . ($isAIGenerated ? 'ai-generated/' : 'originals/') . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

// Get original image dimensions
$imageInfo = getimagesize($originalPath);
$originalWidth = $imageInfo[0];
$originalHeight = $imageInfo[1];

// Optimize image (only for raster images, not SVG)
$optimizedPath = null;
$webpPath = null;
$optimizedWidth = $originalWidth;
$optimizedHeight = $originalHeight;

if ($mimeType !== 'image/svg+xml') {
    // Load image based on type
    $image = null;
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($originalPath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($originalPath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($originalPath);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($originalPath);
            break;
    }
    
    if ($image) {
        // Calculate optimized dimensions (maintain aspect ratio, max height = header height)
        $aspectRatio = $originalWidth / $originalHeight;
        if ($originalHeight > $headerHeight) {
            $optimizedHeight = $headerHeight;
            $optimizedWidth = (int)($headerHeight * $aspectRatio);
        }
        
        // Create optimized image
        $optimizedImage = imagescale($image, $optimizedWidth, $optimizedHeight);
        
        // Save optimized version
        $optimizedFilename = pathinfo($filename, PATHINFO_FILENAME) . '_opt.' . $extension;
        $optimizedPath = $baseDir . 'optimized/' . $optimizedFilename;
        
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($optimizedImage, $optimizedPath, 85);
                break;
            case 'image/png':
                imagepng($optimizedImage, $optimizedPath, 8);
                break;
            case 'image/gif':
                imagegif($optimizedImage, $optimizedPath);
                break;
            case 'image/webp':
                imagewebp($optimizedImage, $optimizedPath, 85);
                break;
        }
        
        // Create WebP version
        if (function_exists('imagewebp')) {
            $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
            $webpPath = $baseDir . 'webp/' . $webpFilename;
            imagewebp($optimizedImage, $webpPath, 85);
        }
        
        imagedestroy($image);
        imagedestroy($optimizedImage);
    }
}

// Return response
$response = [
    'success' => true,
    'image' => [
        'image_path' => 'uploads/headers/' . ($isAIGenerated ? 'ai-generated/' : 'originals/') . $filename,
        'image_path_webp' => $webpPath ? 'uploads/headers/webp/' . pathinfo($filename, PATHINFO_FILENAME) . '.webp' : null,
        'original_width' => $originalWidth,
        'original_height' => $originalHeight,
        'optimized_width' => $optimizedWidth,
        'optimized_height' => $optimizedHeight,
        'is_ai_generated' => $isAIGenerated
    ]
];

echo json_encode($response);

