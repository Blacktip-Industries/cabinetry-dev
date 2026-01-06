<?php
/**
 * Layout Component - Starter Kits Functions
 * Starter kit creation and wizard interface
 */

require_once __DIR__ . '/database.php';

/**
 * Create starter kit
 * @param array $data Starter kit data
 * @return array Result
 */
function layout_starter_kit_create($data) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('starter_kits');
        
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? null;
        $kitType = $data['kit_type'] ?? null;
        $industry = $data['industry'] ?? null;
        $kitData = json_encode($data['kit_data'] ?? []);
        $previewImage = $data['preview_image'] ?? null;
        
        // Auto-generate thumbnail if not provided
        if (!$previewImage && !empty($data['kit_data']['element_templates'])) {
            require_once __DIR__ . '/thumbnail_generator.php';
            // Generate thumbnail for first template in kit
            $firstTemplateId = $data['kit_data']['element_templates'][0] ?? null;
            if ($firstTemplateId) {
                $thumbnailResult = layout_generate_thumbnail($firstTemplateId, 'element_template');
                if ($thumbnailResult['success']) {
                    $previewImage = $thumbnailResult['path'];
                }
            }
        }
        
        $isFeatured = isset($data['is_featured']) ? (int)$data['is_featured'] : 0;
        $createdBy = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (name, description, kit_type, industry, kit_data, preview_image, is_featured, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssii", $name, $description, $kitType, $industry, $kitData, $previewImage, $isFeatured, $createdBy);
        
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Starter Kits: Error creating kit: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get starter kit
 * @param int $kitId Kit ID
 * @return array|null Kit data
 */
function layout_starter_kit_get($kitId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('starter_kits');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $kitId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $kit = $result->fetch_assoc();
            $stmt->close();
            $kit['kit_data'] = json_decode($kit['kit_data'], true) ?? [];
            return $kit;
        }
        
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Starter Kits: Error getting kit: " . $e->getMessage());
        return null;
    }
}

/**
 * Apply starter kit
 * @param int $kitId Kit ID
 * @return array Result with created resources
 */
function layout_starter_kit_apply($kitId) {
    $kit = layout_starter_kit_get($kitId);
    if (!$kit) {
        return ['success' => false, 'error' => 'Starter kit not found'];
    }
    
    $kitData = $kit['kit_data'];
    $created = [];
    
    // Create element templates from kit
    if (isset($kitData['element_templates'])) {
        require_once __DIR__ . '/element_templates.php';
        foreach ($kitData['element_templates'] as $templateData) {
            $result = layout_element_template_create($templateData);
            if ($result['success']) {
                $created['element_templates'][] = $result['id'];
            }
        }
    }
    
    // Create design systems from kit
    if (isset($kitData['design_systems'])) {
        require_once __DIR__ . '/design_systems.php';
        foreach ($kitData['design_systems'] as $systemData) {
            $result = layout_design_system_create($systemData);
            if ($result['success']) {
                $created['design_systems'][] = $result['id'];
            }
        }
    }
    
    // Update usage count
    $conn = layout_get_db_connection();
    if ($conn) {
        $tableName = layout_get_table_name('starter_kits');
        $stmt = $conn->prepare("UPDATE {$tableName} SET usage_count = usage_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $kitId);
        $stmt->execute();
        $stmt->close();
    }
    
    return ['success' => true, 'created' => $created];
}

