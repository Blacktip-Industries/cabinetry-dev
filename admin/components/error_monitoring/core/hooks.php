<?php
/**
 * Error Monitoring Component - Hook/Event System
 * Allows components to register hooks for error events
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Register component hook
 * @param string $hookName Hook name
 * @param string $callback Callback function name
 * @param string $componentName Component name
 * @return bool Success
 */
function error_monitoring_register_hook($hookName, $callback, $componentName) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('hooks');
        $priority = 10;
        
        $stmt = $conn->prepare("
            INSERT INTO {$tableName} (hook_name, callback, component_name, priority, is_active, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE callback = VALUES(callback), priority = VALUES(priority), updated_at = NOW()
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sssi", $hookName, $callback, $componentName, $priority);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to register hook: " . $e->getMessage());
        return false;
    }
}

/**
 * Trigger hook for all registered components
 * @param string $hookName Hook name
 * @param array $data Hook data
 * @return void
 */
function error_monitoring_trigger_hook($hookName, $data = []) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('hooks');
        $stmt = $conn->prepare("SELECT callback, component_name FROM {$tableName} WHERE hook_name = ? AND is_active = 1 ORDER BY priority ASC");
        if (!$stmt) {
            return;
        }
        
        $stmt->bind_param("s", $hookName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $callback = $row['callback'];
            if (function_exists($callback)) {
                try {
                    call_user_func($callback, $data);
                } catch (Exception $e) {
                    error_log("Error Monitoring: Hook callback failed ({$callback}): " . $e->getMessage());
                }
            }
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to trigger hook: " . $e->getMessage());
    }
}

/**
 * Get all registered hooks
 * @return array Array of hooks
 */
function error_monitoring_get_registered_hooks() {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = error_monitoring_get_table_name('hooks');
        $result = $conn->query("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY hook_name, priority");
        $hooks = [];
        
        while ($row = $result->fetch_assoc()) {
            $hooks[] = $row;
        }
        
        return $hooks;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Unregister hook
 * @param string $hookName Hook name
 * @param string $componentName Component name
 * @return bool Success
 */
function error_monitoring_unregister_hook($hookName, $componentName) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('hooks');
        $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = 0 WHERE hook_name = ? AND component_name = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ss", $hookName, $componentName);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to unregister hook: " . $e->getMessage());
        return false;
    }
}

