<?php
/**
 * Error Monitoring Component - Component Detection
 * Detects and manages component monitoring
 */

// Load required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Detect installed components by scanning /admin/components/
 * @return array Array of component names
 */
function error_monitoring_detect_components() {
    $components = [];
    $componentsPath = error_monitoring_get_root_path() . '/admin/components';
    
    if (!is_dir($componentsPath)) {
        return $components;
    }
    
    $dirs = scandir($componentsPath);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..' || $dir === 'error_monitoring') {
            continue;
        }
        
        $componentPath = $componentsPath . '/' . $dir;
        if (is_dir($componentPath)) {
            // Check if component has config.php (indicates it's installed)
            if (file_exists($componentPath . '/config.php') || file_exists($componentPath . '/config.example.php')) {
                $components[] = $dir;
            }
        }
    }
    
    return $components;
}

/**
 * Check if component is monitored
 * @param string $componentName Component name
 * @return bool True if monitored
 */
function error_monitoring_is_component_monitored($componentName) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return true; // Default to monitored if database unavailable
    }
    
    try {
        $tableName = error_monitoring_get_table_name('config');
        $key = 'component_monitored_' . $componentName;
        
        $stmt = $conn->prepare("SELECT config_value FROM {$tableName} WHERE config_key = ?");
        if (!$stmt) {
            return true; // Default to monitored
        }
        
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            return $row['config_value'] === '1';
        }
        
        // Default: monitor all components
        return true;
        
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to check component monitoring: " . $e->getMessage());
        return true; // Default to monitored
    }
}

/**
 * Enable monitoring for a component
 * @param string $componentName Component name
 * @return bool Success
 */
function error_monitoring_enable_component_monitoring($componentName) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('config');
        $key = 'component_monitored_' . $componentName;
        
        $stmt = $conn->prepare("
            INSERT INTO {$tableName} (config_key, config_value, updated_at)
            VALUES (?, '1', NOW())
            ON DUPLICATE KEY UPDATE config_value = '1', updated_at = NOW()
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("s", $key);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to enable component monitoring: " . $e->getMessage());
        return false;
    }
}

/**
 * Disable monitoring for a component
 * @param string $componentName Component name
 * @return bool Success
 */
function error_monitoring_disable_component_monitoring($componentName) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('config');
        $key = 'component_monitored_' . $componentName;
        
        $stmt = $conn->prepare("
            INSERT INTO {$tableName} (config_key, config_value, updated_at)
            VALUES (?, '0', NOW())
            ON DUPLICATE KEY UPDATE config_value = '0', updated_at = NOW()
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("s", $key);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to disable component monitoring: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all monitored components
 * @return array Array of component names
 */
function error_monitoring_get_monitored_components() {
    $allComponents = error_monitoring_detect_components();
    $monitored = [];
    
    foreach ($allComponents as $component) {
        if (error_monitoring_is_component_monitored($component)) {
            $monitored[] = $component;
        }
    }
    
    return $monitored;
}

