<?php
/**
 * AI Image Generation Handler
 * Handles AI image generation requests
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$prompt = trim($input['prompt'] ?? '');
$headerId = isset($input['header_id']) ? (int)$input['header_id'] : null;
$variations = isset($input['variations']) ? (int)$input['variations'] : 1;
$service = $input['service'] ?? 'dalle3';

if (empty($prompt)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Prompt is required']);
    exit;
}

// Get API settings
$conn = getDBConnection();
$apiKey = null;
$apiUrl = null;

if ($conn) {
    createAIImageGenerationSettingsTable($conn);
    
    // Get API key
    $stmt = $conn->prepare("SELECT setting_value FROM ai_image_generation_settings WHERE setting_key = ?");
    $keyName = $service === 'dalle3' ? 'openai_api_key' : 'stable_diffusion_api_key';
    $stmt->bind_param("s", $keyName);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $apiKey = $row['setting_value'];
    }
    $stmt->close();
}

if (empty($apiKey)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'API key not configured. Please configure it in Settings.']);
    exit;
}

// Generate images based on service
$generatedImages = [];

if ($service === 'dalle3') {
    // DALL-E 3 API call
    $apiUrl = 'https://api.openai.com/v1/images/generations';
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    // DALL-E 3 only supports 1 image per request
    $requestData = [
        'model' => 'dall-e-3',
        'prompt' => $prompt,
        'n' => 1,
        'size' => '1024x1024',
        'quality' => 'standard'
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData['data'][0]['url'])) {
            $imageUrl = $responseData['data'][0]['url'];
            
            // Download and save image
            $imageData = file_get_contents($imageUrl);
            $filename = uniqid('ai_generated_', true) . '.png';
            $savePath = __DIR__ . '/../../uploads/headers/ai-generated/' . $filename;
            
            // Ensure directory exists
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($savePath, $imageData);
            
            // Create optimized versions (similar to upload handler)
            $imageInfo = getimagesize($savePath);
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            
            // Load and optimize image
            $image = imagecreatefrompng($savePath);
            if ($image) {
                // Create optimized version (max 1024px height)
                $maxHeight = 1024;
                $aspectRatio = $originalWidth / $originalHeight;
                if ($originalHeight > $maxHeight) {
                    $optimizedHeight = $maxHeight;
                    $optimizedWidth = (int)($maxHeight * $aspectRatio);
                    $optimizedImage = imagescale($image, $optimizedWidth, $optimizedHeight);
                } else {
                    $optimizedImage = $image;
                    $optimizedWidth = $originalWidth;
                    $optimizedHeight = $originalHeight;
                }
                
                // Save optimized version
                $optimizedFilename = pathinfo($filename, PATHINFO_FILENAME) . '_opt.png';
                $optimizedPath = __DIR__ . '/../../uploads/headers/optimized/' . $optimizedFilename;
                $optDir = dirname($optimizedPath);
                if (!is_dir($optDir)) {
                    mkdir($optDir, 0755, true);
                }
                imagepng($optimizedImage, $optimizedPath, 8);
                
                // Create WebP version
                if (function_exists('imagewebp')) {
                    $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
                    $webpPath = __DIR__ . '/../../uploads/headers/webp/' . $webpFilename;
                    $webpDir = dirname($webpPath);
                    if (!is_dir($webpDir)) {
                        mkdir($webpDir, 0755, true);
                    }
                    imagewebp($optimizedImage, $webpPath, 85);
                }
                
                imagedestroy($image);
                if ($optimizedImage !== $image) {
                    imagedestroy($optimizedImage);
                }
            }
            
            $generatedImages[] = [
                'image_path' => 'uploads/headers/ai-generated/' . $filename,
                'image_path_webp' => isset($webpPath) ? 'uploads/headers/webp/' . pathinfo($filename, PATHINFO_FILENAME) . '.webp' : null,
                'original_width' => $originalWidth,
                'original_height' => $originalHeight,
                'optimized_width' => $optimizedWidth ?? $originalWidth,
                'optimized_height' => $optimizedHeight ?? $originalHeight
            ];
            
            // Save generation record
            if ($conn && $headerId) {
                $stmt = $conn->prepare("INSERT INTO scheduled_header_ai_generations 
                    (header_id, prompt, ai_service, variations_generated, selected_variation) 
                    VALUES (?, ?, ?, 1, 0)");
                $stmt->bind_param("iss", $headerId, $prompt, $service);
                $stmt->execute();
                $generationId = $conn->insert_id;
                $stmt->close();
                
                // Track usage/cost
                $cost = 0.04; // DALL-E 3 standard pricing
                $usageStmt = $conn->prepare("INSERT INTO ai_generation_usage 
                    (generation_id, cost, prompt_length, variations_count) 
                    VALUES (?, ?, ?, 1)");
                $promptLength = strlen($prompt);
                $usageStmt->bind_param("idii", $generationId, $cost, $promptLength);
                $usageStmt->execute();
                $usageStmt->close();
            }
        }
    } else {
        $errorData = json_decode($response, true);
        http_response_code($httpCode);
        echo json_encode([
            'success' => false, 
            'error' => $errorData['error']['message'] ?? 'Failed to generate image'
        ]);
        exit;
    }
} else {
    // Other services can be added here
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported service']);
    exit;
}

if (empty($generatedImages)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to generate images']);
    exit;
}

echo json_encode([
    'success' => true,
    'images' => $generatedImages
]);

