<?php
/**
 * Component Manager - Version Functions
 * Version comparison and management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Compare versions
 * @param string $version1 First version
 * @param string $version2 Second version
 * @return int -1 if version1 < version2, 0 if equal, 1 if version1 > version2
 */
function component_manager_compare_versions($version1, $version2) {
    return version_compare($version1, $version2);
}

/**
 * Get installed version
 * @param string $componentName Component name
 * @return string|null Installed version or null
 */
function component_manager_get_installed_version($componentName) {
    $component = component_manager_get_component($componentName);
    return $component ? $component['installed_version'] : null;
}

/**
 * Get available version (from VERSION file)
 * @param string $componentName Component name
 * @return string|null Available version or null
 */
function component_manager_get_available_version($componentName) {
    $component = component_manager_get_component($componentName);
    if (!$component) {
        return null;
    }
    
    $componentPath = $component['component_path'];
    return component_manager_get_component_version_file($componentName, $componentPath);
}

/**
 * Check if update available
 * @param string $componentName Component name
 * @return bool True if update available
 */
function component_manager_is_update_available($componentName) {
    $installed = component_manager_get_installed_version($componentName);
    $available = component_manager_get_available_version($componentName);
    
    if ($installed === null || $available === null) {
        return false;
    }
    
    return component_manager_compare_versions($installed, $available) < 0;
}

/**
 * Get version history
 * @param string $componentName Component name
 * @return array Version history
 */
function component_manager_get_version_history($componentName) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = component_manager_get_table_name('update_history');
        $stmt = $conn->prepare("SELECT from_version, to_version, started_at, status FROM {$tableName} WHERE component_name = ? ORDER BY started_at DESC");
        $stmt->bind_param("s", $componentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        $stmt->close();
        return $history;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting version history: " . $e->getMessage());
        return [];
    }
}

