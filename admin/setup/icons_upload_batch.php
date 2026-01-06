<?php
/**
 * Batch Icon Upload Endpoint
 * Handles AJAX batch uploads of icon files (up to 20 per request)
 */

// Suppress any output before JSON
ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Check authentication
// Use checkAuth() instead of isLoggedIn() as that's what's available in auth.php
if (!function_exists('checkAuth') || !checkAuth()) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Clear any output buffer
ob_end_clean();
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if files were uploaded (check both inputs)
$filesArray = null;
if (isset($_FILES['svg_files']) && is_array($_FILES['svg_files']['name'])) {
    $filesArray = $_FILES['svg_files'];
} elseif (isset($_FILES['svg_files_folder']) && is_array($_FILES['svg_files_folder']['name'])) {
    $filesArray = $_FILES['svg_files_folder'];
}

if (!$filesArray || !is_array($filesArray['name'])) {
    echo json_encode(['success' => false, 'error' => 'No files uploaded']);
    exit;
}

// Get category
$category = trim($_POST['category'] ?? '');

if (empty($category)) {
    echo json_encode(['success' => false, 'error' => 'Category is required']);
    exit;
}

$uploadedCount = 0;
$skippedCount = 0;
$errors = [];
$results = [];

// Process each uploaded file
foreach ($filesArray['name'] as $key => $fileName) {
    // Check if this icon should be skipped (duplicate with same SVG)
    if (isset($_POST['icon_skip'][$key]) && $_POST['icon_skip'][$key] === '1') {
        $skippedCount++;
        $results[] = [
            'file' => $fileName,
            'status' => 'skipped',
            'message' => 'Icon already exists with identical SVG content'
        ];
        continue;
    }
    
    if ($filesArray['error'][$key] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if ($fileExtension === 'svg') {
            $fileContent = file_get_contents($filesArray['tmp_name'][$key]);
            
            // Extract SVG paths from the uploaded file
            $dom = new DOMDocument();
            $libXmlErrors = libxml_use_internal_errors(true);
            $loaded = @$dom->loadXML($fileContent);
            libxml_use_internal_errors($libXmlErrors);
            
            $svgPath = '';
            $viewBox = '0 0 24 24'; // Default viewBox
            
            if ($loaded) {
                $svgElement = $dom->getElementsByTagName('svg')->item(0);
                if ($svgElement) {
                    // Extract viewBox from original SVG
                    if ($svgElement->hasAttribute('viewBox')) {
                        $viewBox = $svgElement->getAttribute('viewBox');
                    } elseif ($svgElement->hasAttribute('width') && $svgElement->hasAttribute('height')) {
                        $width = $svgElement->getAttribute('width');
                        $height = $svgElement->getAttribute('height');
                        // Remove 'px' if present
                        $width = preg_replace('/px$/', '', $width);
                        $height = preg_replace('/px$/', '', $height);
                        $viewBox = "0 0 {$width} {$height}";
                    }
                    
                    $innerHTML = '';
                    foreach ($svgElement->childNodes as $child) {
                        if ($child->nodeType === XML_ELEMENT_NODE) {
                            // Skip rect elements that are likely backgrounds
                            if ($child->nodeName === 'rect' && $child instanceof DOMElement) {
                                $width = $child->getAttribute('width');
                                $height = $child->getAttribute('height');
                                $x = $child->getAttribute('x') ?: '0';
                                $y = $child->getAttribute('y') ?: '0';
                                
                                // Skip full-size background rects
                                if (($width === '24' || $width === '100%') && 
                                    ($height === '24' || $height === '100%') && 
                                    ($x === '0' || $x === '') && 
                                    ($y === '0' || $y === '')) {
                                    continue;
                                }
                            }
                            $innerHTML .= $dom->saveHTML($child);
                        }
                    }
                    if (!empty(trim($innerHTML))) {
                        // Store viewBox as a data attribute in a comment
                        $svgPath = '<!--viewBox:' . $viewBox . '-->' . trim($innerHTML);
                    }
                }
            } else {
                // Fallback: try to extract path data and viewBox using regex
                // Extract viewBox
                if (preg_match('/viewBox=["\']([^"\']+)["\']/i', $fileContent, $vbMatches)) {
                    $viewBox = $vbMatches[1];
                } elseif (preg_match('/width=["\']([^"\']+)["\']/i', $fileContent, $wMatches) && 
                          preg_match('/height=["\']([^"\']+)["\']/i', $fileContent, $hMatches)) {
                    $width = preg_replace('/px$/', '', $wMatches[1]);
                    $height = preg_replace('/px$/', '', $hMatches[1]);
                    $viewBox = "0 0 {$width} {$height}";
                }
                
                // Extract all SVG elements (not just paths)
                if (preg_match_all('/<(path|circle|rect|ellipse|polygon|polyline|line|g)[^>]*>[\s\S]*?<\/\1>/i', $fileContent, $matches)) {
                    $pathData = implode('', $matches[0]);
                    if (!empty($pathData)) {
                        $svgPath = '<!--viewBox:' . $viewBox . '-->' . $pathData;
                    }
                } elseif (preg_match_all('/<path[^>]*d="([^"]+)"[^>]*>/i', $fileContent, $matches)) {
                    $pathData = '';
                    foreach ($matches[0] as $pathTag) {
                        $pathData .= $pathTag;
                    }
                    if (!empty($pathData)) {
                        $svgPath = '<!--viewBox:' . $viewBox . '-->' . $pathData;
                    }
                }
            }
            
            if (!empty($svgPath)) {
                // Get icon name from form (user may have edited it)
                $iconName = trim($_POST['icon_names'][$key] ?? '');
                
                // If not provided, generate from filename
                if (empty($iconName)) {
                    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                    $iconName = preg_replace('/[^a-z0-9_]/i', '_', strtolower($baseName));
                    $iconName = preg_replace('/_+/', '_', $iconName);
                    $iconName = trim($iconName, '_');
                }
                
                if (empty($iconName)) {
                    $iconName = 'icon_' . ($key + 1);
                }
                
                // Get description and order for this icon
                $description = trim($_POST['icon_descriptions'][$key] ?? '');
                $displayOrder = (int)($_POST['icon_orders'][$key] ?? 0);
                
                $iconData = [
                    'name' => $iconName,
                    'svg_path' => $svgPath,
                    'description' => $description,
                    'category' => $category,
                    'display_order' => $displayOrder
                ];
                
                $saveResult = saveIcon($iconData);
                if ($saveResult['success']) {
                    $uploadedCount++;
                    $results[] = [
                        'file' => $fileName,
                        'status' => 'success',
                        'icon_name' => $iconName,
                        'message' => 'Icon uploaded successfully'
                    ];
                } else {
                    $errorMsg = $saveResult['error'] ?? 'Unknown error';
                    $errors[] = "Failed to save {$fileName}: {$errorMsg}";
                    $results[] = [
                        'file' => $fileName,
                        'status' => 'error',
                        'message' => $errorMsg
                    ];
                }
            } else {
                $errorMsg = "Could not extract SVG from {$fileName}";
                $errors[] = $errorMsg;
                $results[] = [
                    'file' => $fileName,
                    'status' => 'error',
                    'message' => $errorMsg
                ];
            }
        } else {
            $errorMsg = "{$fileName} is not an SVG file";
            $errors[] = $errorMsg;
            $results[] = [
                'file' => $fileName,
                'status' => 'error',
                'message' => $errorMsg
            ];
        }
    } else {
        $errorMsg = "Upload error for {$fileName}: " . $filesArray['error'][$key];
        $errors[] = $errorMsg;
        $results[] = [
            'file' => $fileName,
            'status' => 'error',
            'message' => $errorMsg
        ];
    }
}

// Return JSON response
echo json_encode([
    'success' => $uploadedCount > 0 || $skippedCount > 0,
    'uploaded' => $uploadedCount,
    'skipped' => $skippedCount,
    'errors' => $errors,
    'results' => $results
]);

