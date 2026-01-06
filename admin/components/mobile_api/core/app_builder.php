<?php
/**
 * Mobile API Component - App Builder
 * Visual app builder engine
 */

/**
 * Scan component manifests and get available features
 * @return array Available features
 */
function mobile_api_get_available_features() {
    require_once __DIR__ . '/component_scanner.php';
    return mobile_api_get_available_features();
}

/**
 * Save app layout configuration
 * @param string $layoutName Layout name
 * @param array $layoutConfig Layout configuration
 * @param bool $setAsDefault Set as default layout
 * @return array Save result
 */
function mobile_api_save_app_layout($layoutName, $layoutConfig, $setAsDefault = false) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $configJson = json_encode($layoutConfig);
        
        // If setting as default, unset other defaults
        if ($setAsDefault) {
            $conn->query("UPDATE mobile_api_app_layouts SET is_default = 0");
        }
        
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_app_layouts 
            (layout_name, layout_config, is_default, is_active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                layout_config = VALUES(layout_config),
                is_default = VALUES(is_default),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $isDefault = $setAsDefault ? 1 : 0;
        $stmt->bind_param("ssi", $layoutName, $configJson, $isDefault);
        $result = $stmt->execute();
        $layoutId = $stmt->insert_id;
        $stmt->close();
        
        return [
            'success' => $result,
            'layout_id' => $layoutId
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error saving app layout: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Generate app shell from layout configuration
 * @param int|null $layoutId Layout ID (uses default if null)
 * @return array Generated app shell
 */
function mobile_api_generate_app_shell($layoutId = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        if ($layoutId) {
            $stmt = $conn->prepare("SELECT * FROM mobile_api_app_layouts WHERE id = ? AND is_active = 1");
            $stmt->bind_param("i", $layoutId);
        } else {
            $stmt = $conn->prepare("SELECT * FROM mobile_api_app_layouts WHERE is_default = 1 AND is_active = 1 LIMIT 1");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $layout = $result->fetch_assoc();
        $stmt->close();
        
        if (!$layout) {
            return ['success' => false, 'error' => 'Layout not found'];
        }
        
        $config = json_decode($layout['layout_config'], true);
        
        // Get available component features
        $features = mobile_api_get_available_features();
        
        // Generate app shell structure
        $appShell = [
            'layout_id' => $layout['id'],
            'layout_name' => $layout['layout_name'],
            'navigation' => $config['navigation'] ?? [],
            'screens' => $config['screens'] ?? [],
            'theme' => $config['theme'] ?? [],
            'features' => $features
        ];
        
        return [
            'success' => true,
            'app_shell' => $appShell
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error generating app shell: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Preview layout
 * @param array $layoutConfig Layout configuration
 * @return array Preview data
 */
function mobile_api_preview_layout($layoutConfig) {
    // Get available features
    $features = mobile_api_get_available_features();
    
    // Build preview structure
    $preview = [
        'navigation' => $layoutConfig['navigation'] ?? [],
        'screens' => $layoutConfig['screens'] ?? [],
        'theme' => $layoutConfig['theme'] ?? [],
        'available_features' => $features
    ];
    
    return [
        'success' => true,
        'preview' => $preview
    ];
}

/**
 * Get default app layout
 * @return array|null Layout data or null
 */
function mobile_api_get_default_layout() {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $result = $conn->query("
            SELECT * FROM mobile_api_app_layouts 
            WHERE is_default = 1 AND is_active = 1 
            LIMIT 1
        ");
        
        return $result->fetch_assoc();
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting default layout: " . $e->getMessage());
        return null;
    }
}

