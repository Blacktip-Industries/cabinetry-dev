<?php
/**
 * Component Manager - Uninstall Functions
 * Uninstall coordination
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/backup.php';
require_once __DIR__ . '/dependencies.php';

/**
 * Uninstall component
 * @param string $componentName Component name
 * @param bool $createBackup Create backup before uninstall
 * @param bool $removeData Remove component data
 * @return array Uninstall result
 */
function component_manager_uninstall_component($componentName, $createBackup = true, $removeData = false) {
    // Implementation: Create backup, run uninstall.php, remove from registry, cleanup
    return ['success' => false, 'error' => 'Not yet implemented'];
}

/**
 * Uninstall components in reverse dependency order
 * @param array $componentNames Component names
 * @param bool $createBackup Create backup
 * @return array Uninstall results
 */
function component_manager_uninstall_components_ordered($componentNames, $createBackup = true) {
    // Get reverse dependency order (dependents first)
    $orderResult = component_manager_get_installation_order($componentNames);
    if (!$orderResult['success']) {
        return ['success' => false, 'error' => $orderResult['error']];
    }
    
    // Reverse the order
    $reversedOrder = array_reverse($orderResult['ordered']);
    
    $results = [];
    foreach ($reversedOrder as $name) {
        $results[$name] = component_manager_uninstall_component($name, $createBackup);
    }
    
    return ['success' => true, 'results' => $results];
}

/**
 * Get uninstall order (reverse dependency order)
 * @param array $componentNames Component names
 * @return array Components in uninstall order
 */
function component_manager_get_uninstall_order($componentNames) {
    $orderResult = component_manager_get_installation_order($componentNames);
    if (!$orderResult['success']) {
        return $orderResult;
    }
    
    return ['success' => true, 'ordered' => array_reverse($orderResult['ordered'])];
}

/**
 * Get uninstall preview
 * @param string $componentName Component name
 * @return array Uninstall preview
 */
function component_manager_get_uninstall_preview($componentName) {
    $component = component_manager_get_component($componentName);
    if (!$component) {
        return ['success' => false, 'error' => 'Component not found'];
    }
    
    // Check for dependents
    $allComponents = component_manager_list_components();
    $dependents = [];
    
    foreach ($allComponents as $comp) {
        $dependencies = $comp['dependencies'] ?? [];
        foreach ($dependencies as $dep) {
            $depName = is_array($dep) ? $dep['name'] : $dep;
            if ($depName === $componentName) {
                $dependents[] = $comp['component_name'];
            }
        }
    }
    
    return [
        'component' => $component,
        'dependents' => $dependents,
        'will_affect_dependents' => !empty($dependents)
    ];
}

/**
 * Cleanup after uninstall
 * @param string $componentName Component name
 * @return bool Success status
 */
function component_manager_cleanup_after_uninstall($componentName) {
    // Remove from registry
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = component_manager_get_table_name('registry');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE component_name = ?");
        $stmt->bind_param("s", $componentName);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error cleaning up after uninstall: " . $e->getMessage());
        return false;
    }
}

