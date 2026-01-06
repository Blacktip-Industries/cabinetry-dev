<?php
/**
 * Mobile API Component - Component Scanner
 * Scans installed components for mobile_api.json manifests
 */

/**
 * Scan all components for mobile_api.json manifests
 * @return array Discovered component features
 */
function mobile_api_scan_components() {
    $componentsDir = __DIR__ . '/../../';
    $discovered = [];
    
    if (!is_dir($componentsDir)) {
        return $discovered;
    }
    
    $dirs = scandir($componentsDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..' || $dir === 'mobile_api') {
            continue;
        }
        
        $componentPath = $componentsDir . $dir;
        if (!is_dir($componentPath)) {
            continue;
        }
        
        $manifestFile = $componentPath . '/mobile_api.json';
        if (file_exists($manifestFile)) {
            $manifest = mobile_api_parse_manifest($manifestFile, $dir);
            if ($manifest) {
                $discovered[$dir] = $manifest;
            }
        }
    }
    
    return $discovered;
}

/**
 * Parse component mobile manifest file
 * @param string $manifestFile Path to mobile_api.json
 * @param string $componentName Component name
 * @return array|null Parsed manifest or null
 */
function mobile_api_parse_manifest($manifestFile, $componentName) {
    if (!file_exists($manifestFile)) {
        return null;
    }
    
    $content = file_get_contents($manifestFile);
    if (empty($content)) {
        return null;
    }
    
    $manifest = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Mobile API: Error parsing manifest for {$componentName}: " . json_last_error_msg());
        return null;
    }
    
    // Validate manifest structure
    if (!isset($manifest['component_name']) || !isset($manifest['mobile_features'])) {
        error_log("Mobile API: Invalid manifest structure for {$componentName}");
        return null;
    }
    
    // Ensure component_name matches
    $manifest['component_name'] = $componentName;
    
    return $manifest;
}

/**
 * Register component features in database
 * @param string $componentName Component name
 * @param array $manifest Parsed manifest
 * @return array Result with registered feature counts
 */
function mobile_api_register_features($componentName, $manifest) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $registered = [
        'screens' => 0,
        'navigation' => 0,
        'endpoints' => 0,
        'permissions' => 0
    ];
    $errors = [];
    
    $features = $manifest['mobile_features'] ?? [];
    
    // Register screens
    if (isset($features['screens']) && is_array($features['screens'])) {
        foreach ($features['screens'] as $screen) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO mobile_api_component_features 
                    (component_name, feature_type, feature_name, feature_config, is_enabled, display_order)
                    VALUES (?, 'screen', ?, ?, 1, ?)
                    ON DUPLICATE KEY UPDATE 
                        feature_config = VALUES(feature_config),
                        updated_at = CURRENT_TIMESTAMP
                ");
                
                $featureName = $screen['id'] ?? $screen['name'] ?? 'unknown';
                $config = json_encode($screen);
                $displayOrder = $screen['display_order'] ?? 0;
                
                $stmt->bind_param("sssi", $componentName, $featureName, $config, $displayOrder);
                if ($stmt->execute()) {
                    $registered['screens']++;
                }
                $stmt->close();
            } catch (Exception $e) {
                $errors[] = "Error registering screen {$featureName}: " . $e->getMessage();
            }
        }
    }
    
    // Register navigation
    if (isset($features['navigation']) && is_array($features['navigation'])) {
        foreach ($features['navigation'] as $nav) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO mobile_api_component_features 
                    (component_name, feature_type, feature_name, feature_config, is_enabled, display_order)
                    VALUES (?, 'navigation', ?, ?, 1, ?)
                    ON DUPLICATE KEY UPDATE 
                        feature_config = VALUES(feature_config),
                        updated_at = CURRENT_TIMESTAMP
                ");
                
                $featureName = $nav['id'] ?? $nav['label'] ?? 'unknown';
                $config = json_encode($nav);
                $displayOrder = $nav['display_order'] ?? 0;
                
                $stmt->bind_param("sssi", $componentName, $featureName, $config, $displayOrder);
                if ($stmt->execute()) {
                    $registered['navigation']++;
                }
                $stmt->close();
            } catch (Exception $e) {
                $errors[] = "Error registering navigation {$featureName}: " . $e->getMessage();
            }
        }
    }
    
    // Register API endpoints
    if (isset($features['api_endpoints']) && is_array($features['api_endpoints'])) {
        foreach ($features['api_endpoints'] as $endpoint) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO mobile_api_endpoints 
                    (component_name, endpoint_path, endpoint_method, endpoint_name, description, requires_auth, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                        endpoint_name = VALUES(endpoint_name),
                        description = VALUES(description),
                        updated_at = CURRENT_TIMESTAMP
                ");
                
                $path = $endpoint['path'] ?? '/';
                $method = strtoupper($endpoint['method'] ?? 'GET');
                $name = $endpoint['name'] ?? $endpoint['path'] ?? 'Unknown';
                $description = $endpoint['description'] ?? '';
                $requiresAuth = isset($endpoint['requires_auth']) ? ($endpoint['requires_auth'] ? 1 : 0) : 1;
                
                $stmt->bind_param("sssssi", $componentName, $path, $method, $name, $description, $requiresAuth);
                if ($stmt->execute()) {
                    $registered['endpoints']++;
                }
                $stmt->close();
            } catch (Exception $e) {
                $errors[] = "Error registering endpoint {$path}: " . $e->getMessage();
            }
        }
    }
    
    // Register permissions
    if (isset($features['permissions']) && is_array($features['permissions'])) {
        foreach ($features['permissions'] as $permission) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO mobile_api_component_features 
                    (component_name, feature_type, feature_name, feature_config, is_enabled, display_order)
                    VALUES (?, 'permission', ?, ?, 1, 0)
                    ON DUPLICATE KEY UPDATE 
                        feature_config = VALUES(feature_config),
                        updated_at = CURRENT_TIMESTAMP
                ");
                
                $featureName = $permission['id'] ?? $permission['name'] ?? 'unknown';
                $config = json_encode($permission);
                
                $stmt->bind_param("sss", $componentName, $featureName, $config);
                if ($stmt->execute()) {
                    $registered['permissions']++;
                }
                $stmt->close();
            } catch (Exception $e) {
                $errors[] = "Error registering permission {$featureName}: " . $e->getMessage();
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'registered' => $registered,
        'errors' => $errors
    ];
}

/**
 * Get all available component features
 * @param string|null $componentName Filter by component name
 * @return array Component features
 */
function mobile_api_get_available_features($componentName = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $query = "SELECT * FROM mobile_api_component_features WHERE is_enabled = 1";
        if ($componentName) {
            $query .= " AND component_name = ?";
        }
        $query .= " ORDER BY component_name, feature_type, display_order";
        
        $stmt = $conn->prepare($query);
        if ($componentName) {
            $stmt->bind_param("s", $componentName);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $features = [];
        while ($row = $result->fetch_assoc()) {
            $features[] = $row;
        }
        
        $stmt->close();
        return $features;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting features: " . $e->getMessage());
        return [];
    }
}

/**
 * Sync all component manifests to database
 * @return array Result with sync statistics
 */
function mobile_api_sync_component_manifests() {
    $discovered = mobile_api_scan_components();
    $synced = 0;
    $errors = [];
    
    foreach ($discovered as $componentName => $manifest) {
        $result = mobile_api_register_features($componentName, $manifest);
        if ($result['success']) {
            $synced++;
        } else {
            $errors = array_merge($errors, $result['errors'] ?? []);
        }
    }
    
    return [
        'success' => empty($errors),
        'synced' => $synced,
        'total' => count($discovered),
        'errors' => $errors
    ];
}

