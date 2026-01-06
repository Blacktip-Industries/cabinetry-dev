<?php
/**
 * Mobile API Component - API Gateway
 * Endpoint discovery, registration, and routing
 */

/**
 * Discover API endpoints from installed components
 * @return array Discovered endpoints
 */
function mobile_api_discover_endpoints() {
    $componentsDir = __DIR__ . '/../../';
    $discovered = [];
    
    if (!is_dir($componentsDir)) {
        return $discovered;
    }
    
    // Scan all component directories
    $dirs = scandir($componentsDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..' || $dir === 'mobile_api') {
            continue;
        }
        
        $componentPath = $componentsDir . $dir;
        if (!is_dir($componentPath)) {
            continue;
        }
        
        // Check for API directory
        $apiDir = $componentPath . '/api';
        if (is_dir($apiDir)) {
            $endpoints = mobile_api_scan_component_api($dir, $apiDir);
            $discovered = array_merge($discovered, $endpoints);
        }
    }
    
    return $discovered;
}

/**
 * Scan a component's API directory for endpoints
 * @param string $componentName Component name
 * @param string $apiDir API directory path
 * @return array Discovered endpoints
 */
function mobile_api_scan_component_api($componentName, $apiDir) {
    $endpoints = [];
    
    // Check for index.php
    $indexFile = $apiDir . '/index.php';
    if (file_exists($indexFile)) {
        $endpoints[] = [
            'component' => $componentName,
            'path' => '/',
            'method' => 'GET',
            'name' => 'API Index',
            'description' => 'Main API endpoint for ' . $componentName,
            'file' => $indexFile
        ];
    }
    
    // Check for v1 directory
    $v1Dir = $apiDir . '/v1';
    if (is_dir($v1Dir)) {
        $v1Index = $v1Dir . '/index.php';
        if (file_exists($v1Index)) {
            $endpoints[] = [
                'component' => $componentName,
                'path' => '/v1',
                'method' => 'GET',
                'name' => 'API v1 Index',
                'description' => 'API v1 endpoint for ' . $componentName,
                'file' => $v1Index
            ];
        }
    }
    
    // Check for endpoints directory
    $endpointsDir = $apiDir . '/endpoints';
    if (is_dir($endpointsDir)) {
        $files = scandir($endpointsDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }
            
            $endpointName = pathinfo($file, PATHINFO_FILENAME);
            $endpoints[] = [
                'component' => $componentName,
                'path' => '/endpoints/' . $endpointName,
                'method' => 'GET', // Default, could be parsed from file
                'name' => ucwords(str_replace(['_', '-'], ' ', $endpointName)),
                'description' => 'Endpoint: ' . $endpointName,
                'file' => $endpointsDir . '/' . $file
            ];
        }
    }
    
    return $endpoints;
}

/**
 * Register discovered endpoint in database
 * @param array $endpoint Endpoint data
 * @return bool Success
 */
function mobile_api_register_endpoint($endpoint) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_endpoints 
            (component_name, endpoint_path, endpoint_method, endpoint_name, description, requires_auth, is_active)
            VALUES (?, ?, ?, ?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE 
                endpoint_name = VALUES(endpoint_name),
                description = VALUES(description),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->bind_param("sssss",
            $endpoint['component'],
            $endpoint['path'],
            $endpoint['method'],
            $endpoint['name'],
            $endpoint['description']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error registering endpoint: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all registered endpoints
 * @param bool $activeOnly Only return active endpoints
 * @return array Endpoints
 */
function mobile_api_get_endpoints($activeOnly = true) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $query = "SELECT * FROM mobile_api_endpoints";
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY component_name, endpoint_path";
        
        $result = $conn->query($query);
        $endpoints = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $endpoints[] = $row;
            }
        }
        
        return $endpoints;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting endpoints: " . $e->getMessage());
        return [];
    }
}

/**
 * Route API request to component endpoint
 * @param string $component Component name
 * @param string $path Endpoint path
 * @param string $method HTTP method
 * @return array Response data
 */
function mobile_api_route_request($component, $path, $method = 'GET') {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM mobile_api_endpoints 
            WHERE component_name = ? AND endpoint_path = ? AND endpoint_method = ? AND is_active = 1
        ");
        $stmt->bind_param("sss", $component, $path, $method);
        $stmt->execute();
        $result = $stmt->get_result();
        $endpoint = $result->fetch_assoc();
        $stmt->close();
        
        if (!$endpoint) {
            return ['success' => false, 'error' => 'Endpoint not found'];
        }
        
        // Log API usage
        mobile_api_log_event('api_request', 'api_usage', [
            'component' => $component,
            'endpoint' => $path,
            'method' => $method
        ]);
        
        return [
            'success' => true,
            'endpoint' => $endpoint
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error routing request: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Transform API response for mobile optimization
 * @param array $data Response data
 * @param array $transformConfig Transformation configuration
 * @return array Transformed data
 */
function mobile_api_transform_response($data, $transformConfig = []) {
    if (empty($transformConfig)) {
        return $data;
    }
    
    // Apply transformations based on config
    // This is a placeholder - would implement actual transformation logic
    return $data;
}

/**
 * Sync discovered endpoints to database
 * @return array Result with count of registered endpoints
 */
function mobile_api_sync_endpoints() {
    $discovered = mobile_api_discover_endpoints();
    $registered = 0;
    $errors = [];
    
    foreach ($discovered as $endpoint) {
        if (mobile_api_register_endpoint($endpoint)) {
            $registered++;
        } else {
            $errors[] = "Failed to register: {$endpoint['component']}{$endpoint['path']}";
        }
    }
    
    return [
        'success' => empty($errors),
        'registered' => $registered,
        'total' => count($discovered),
        'errors' => $errors
    ];
}

