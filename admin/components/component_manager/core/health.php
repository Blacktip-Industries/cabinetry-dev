<?php
/**
 * Component Manager - Health Check Functions
 * Health check functions
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Run health check on component
 * @param string $componentName Component name
 * @return array Health check result
 */
function component_manager_check_health($componentName) {
    $component = component_manager_get_component($componentName);
    if (!$component) {
        return [
            'status' => 'error',
            'message' => 'Component not found in registry',
            'checks' => []
        ];
    }
    
    $checks = [];
    $hasErrors = false;
    $hasWarnings = false;
    
    // Check 1: Component directory exists
    $componentPath = $component['component_path'];
    if (is_dir($componentPath)) {
        $checks[] = ['name' => 'Directory exists', 'status' => 'success'];
    } else {
        $checks[] = ['name' => 'Directory exists', 'status' => 'error', 'message' => 'Component directory not found'];
        $hasErrors = true;
    }
    
    // Check 2: config.php exists
    if (file_exists($componentPath . '/config.php')) {
        $checks[] = ['name' => 'config.php exists', 'status' => 'success'];
    } else {
        $checks[] = ['name' => 'config.php exists', 'status' => 'warning', 'message' => 'config.php not found'];
        $hasWarnings = true;
    }
    
    // Check 3: VERSION file exists
    if (file_exists($componentPath . '/VERSION')) {
        $checks[] = ['name' => 'VERSION file exists', 'status' => 'success'];
    } else {
        $checks[] = ['name' => 'VERSION file exists', 'status' => 'warning', 'message' => 'VERSION file not found'];
        $hasWarnings = true;
    }
    
    // Check 4: Database config table exists
    $conn = component_manager_get_db_connection();
    if ($conn !== null) {
        $configTable = $componentName . '_config';
        $result = $conn->query("SHOW TABLES LIKE '{$configTable}'");
        if ($result->num_rows > 0) {
            $checks[] = ['name' => 'Database config table exists', 'status' => 'success'];
            
            // Check 5: Version matches
            $versionFile = component_manager_get_component_version_file($componentName, $componentPath);
            if ($versionFile) {
                $stmt = $conn->prepare("SELECT config_value FROM {$configTable} WHERE config_key = 'version'");
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                if ($row && $row['config_value'] === $versionFile) {
                    $checks[] = ['name' => 'Version matches', 'status' => 'success'];
                } else {
                    $checks[] = ['name' => 'Version matches', 'status' => 'warning', 'message' => 'Version mismatch'];
                    $hasWarnings = true;
                }
            }
        } else {
            $checks[] = ['name' => 'Database config table exists', 'status' => 'error', 'message' => 'Config table not found'];
            $hasErrors = true;
        }
    }
    
    // Determine overall status
    $status = 'healthy';
    $message = 'Component is healthy';
    
    if ($hasErrors) {
        $status = 'error';
        $message = 'Component has errors';
    } elseif ($hasWarnings) {
        $status = 'warning';
        $message = 'Component has warnings';
    }
    
    // Update health status in registry
    component_manager_update_status($componentName, $component['status'], null, $status, $message);
    
    return [
        'status' => $status,
        'message' => $message,
        'checks' => $checks,
        'checked_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Run health check on all components
 * @return array Health check results
 */
function component_manager_check_all_health() {
    $components = component_manager_list_components();
    $results = [];
    
    foreach ($components as $component) {
        $results[$component['component_name']] = component_manager_check_health($component['component_name']);
    }
    
    return $results;
}

/**
 * Get health check results
 * @param string $componentName Component name
 * @return array Health status
 */
function component_manager_get_health_status($componentName) {
    $component = component_manager_get_component($componentName);
    if (!$component) {
        return null;
    }
    
    return [
        'health_status' => $component['health_status'],
        'health_message' => $component['health_message'],
        'health_last_checked_at' => $component['health_last_checked_at']
    ];
}

/**
 * Register custom health check function
 * @param string $componentName Component name
 * @param string $functionName Function name
 * @return bool Success status
 */
function component_manager_register_health_check($componentName, $functionName) {
    // Store custom health check function name
    // This would typically be stored in a registry or config
    // For now, components can call their own health check functions
    return true;
}

