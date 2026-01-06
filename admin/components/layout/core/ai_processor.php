<?php
/**
 * Layout Component - AI Processor Functions
 * AI image processing and template generation
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/element_templates.php';

/**
 * Process image through AI and generate template
 * @param string $imagePath Path to uploaded image
 * @param string $imageType Image MIME type
 * @return array Result with queue_id and template_id
 */
function layout_ai_process_image($imagePath, $imageType) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('ai_processing_queue');
        $createdBy = $_SESSION['user_id'] ?? null;
        
        // Add to processing queue
        $stmt = $conn->prepare("INSERT INTO {$tableName} (image_path, image_type, processing_status, created_by) VALUES (?, ?, 'pending', ?)");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param("ssi", $imagePath, $imageType, $createdBy);
        
        if ($stmt->execute()) {
            $queueId = $conn->insert_id;
            $stmt->close();
            
            // For now, return queue ID
            // In full implementation, this would trigger AI processing
            // For MVP, we'll create a placeholder template that user can edit
            
            // Create a basic template from the image
            $templateData = [
                'name' => 'Template from Image ' . date('Y-m-d H:i:s'),
                'description' => 'Template generated from uploaded image (needs refinement)',
                'element_type' => 'button', // Default, user can change
                'html' => '<div class="template-placeholder">Template from image - please edit</div>',
                'css' => '/* CSS will be generated from image analysis */',
                'js' => '',
                'is_published' => 0
            ];
            
            $templateResult = layout_element_template_create($templateData);
            
            if ($templateResult['success']) {
                // Update queue with template ID
                $updateStmt = $conn->prepare("UPDATE {$tableName} SET processing_result = ? WHERE id = ?");
                $processingResult = json_encode(['template_id' => $templateResult['id'], 'status' => 'completed', 'method' => 'manual_edit_required']);
                $updateStmt->bind_param("si", $processingResult, $queueId);
                $updateStmt->execute();
                $updateStmt->close();
                
                return [
                    'success' => true,
                    'queue_id' => $queueId,
                    'template_id' => $templateResult['id'],
                    'message' => 'Image uploaded. Please edit the generated template.'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to create template: ' . ($templateResult['error'] ?? 'Unknown error')];
            }
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout AI Processor: Error processing image: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Process image with AI service (OpenAI/Anthropic)
 * @param int $queueId Queue ID
 * @param string $apiKey API key for AI service
 * @param string $service Service name ('openai' or 'anthropic')
 * @return array Result
 */
function layout_ai_process_with_service($queueId, $apiKey, $service = 'openai') {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('ai_processing_queue');
        
        // Get queue item
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $queueId);
        $stmt->execute();
        $result = $stmt->get_result();
        $queueItem = $result->fetch_assoc();
        $stmt->close();
        
        if (!$queueItem || $queueItem['processing_status'] !== 'pending') {
            return ['success' => false, 'error' => 'Queue item not found or already processed'];
        }
        
        // Update status to processing
        $updateStmt = $conn->prepare("UPDATE {$tableName} SET processing_status = 'processing', ai_service = ? WHERE id = ?");
        $updateStmt->bind_param("si", $service, $queueId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Read image file
        $imagePath = $queueItem['image_path'];
        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => 'Image file not found'];
        }
        
        $imageData = file_get_contents($imagePath);
        $imageBase64 = base64_encode($imageData);
        
        // Call AI service
        // This is a placeholder - full implementation would call OpenAI Vision API or similar
        $aiResult = [
            'html' => '<button class="btn">Button</button>',
            'css' => '.btn { padding: 10px 20px; background: #007bff; color: white; }',
            'js' => '',
            'properties' => ['text' => ['type' => 'string', 'default' => 'Button']],
            'colors' => ['primary' => '#007bff'],
            'spacing' => ['padding' => '10px 20px']
        ];
        
        // Create template from AI result
        $templateData = [
            'name' => 'AI Generated Template ' . date('Y-m-d H:i:s'),
            'description' => 'Template generated by AI from image',
            'element_type' => 'button',
            'html' => $aiResult['html'],
            'css' => $aiResult['css'],
            'js' => $aiResult['js'],
            'properties' => $aiResult['properties'],
            'is_published' => 0
        ];
        
        $templateResult = layout_element_template_create($templateData);
        
        if ($templateResult['success']) {
            // Update queue with result
            $processingResult = json_encode([
                'template_id' => $templateResult['id'],
                'status' => 'completed',
                'ai_result' => $aiResult
            ]);
            
            $updateStmt = $conn->prepare("UPDATE {$tableName} SET processing_status = 'completed', processing_result = ?, completed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->bind_param("si", $processingResult, $queueId);
            $updateStmt->execute();
            $updateStmt->close();
            
            return [
                'success' => true,
                'template_id' => $templateResult['id'],
                'queue_id' => $queueId
            ];
        } else {
            // Update queue with error
            $errorResult = json_encode(['error' => $templateResult['error']]);
            $updateStmt = $conn->prepare("UPDATE {$tableName} SET processing_status = 'failed', error_message = ?, processing_result = ? WHERE id = ?");
            $errorMsg = $templateResult['error'] ?? 'Unknown error';
            $updateStmt->bind_param("ssi", $errorMsg, $errorResult, $queueId);
            $updateStmt->execute();
            $updateStmt->close();
            
            return ['success' => false, 'error' => $templateResult['error']];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout AI Processor: Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get AI processing queue status
 * @param int $queueId Queue ID
 * @return array|null Queue item data
 */
function layout_ai_get_queue_status($queueId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('ai_processing_queue');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $queueId);
        $stmt->execute();
        $result = $stmt->get_result();
        $queueItem = $result->fetch_assoc();
        $stmt->close();
        
        if ($queueItem && isset($queueItem['processing_result'])) {
            $queueItem['processing_result'] = json_decode($queueItem['processing_result'], true);
        }
        
        return $queueItem;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout AI Processor: Error getting queue status: " . $e->getMessage());
        return null;
    }
}

