<?php
/**
 * Component Manager - Savepoints Integration
 * Integration with savepoints component
 */

require_once __DIR__ . '/functions.php';

/**
 * Check if savepoints available
 * @return bool True if savepoints is installed
 */
function component_manager_savepoints_available_check() {
    // Use function from functions.php
    if (function_exists('component_manager_savepoints_available')) {
        return component_manager_savepoints_available();
    }
    return false;
}

/**
 * Create savepoint via savepoints component
 * @param string $message Savepoint message
 * @param string $createdBy Created by
 * @return array Savepoint result
 */
function component_manager_create_savepoint($message, $createdBy = 'component_manager') {
    if (!component_manager_savepoints_available_check()) {
        return ['success' => false, 'error' => 'Savepoints component not available'];
    }
    
    // Load savepoints functions
    $savepointsPath = __DIR__ . '/../../savepoints';
    if (file_exists($savepointsPath . '/core/backup-operations.php')) {
        require_once $savepointsPath . '/core/backup-operations.php';
        
        if (function_exists('savepoints_create_savepoint')) {
            $result = savepoints_create_savepoint($message, $createdBy);
            if ($result['success']) {
                return ['success' => true, 'savepoint_id' => $result['savepoint_id']];
            }
            return $result;
        }
    }
    
    return ['success' => false, 'error' => 'Savepoints functions not available'];
}

/**
 * Restore from savepoint
 * @param int $savepointId Savepoint ID
 * @return array Restore result
 */
function component_manager_restore_from_savepoint($savepointId) {
    if (!component_manager_savepoints_available_check()) {
        return ['success' => false, 'error' => 'Savepoints component not available'];
    }
    
    // Load savepoints functions
    $savepointsPath = __DIR__ . '/../../savepoints';
    if (file_exists($savepointsPath . '/core/restore-operations.php')) {
        require_once $savepointsPath . '/core/restore-operations.php';
        
        if (function_exists('savepoints_restore')) {
            return savepoints_restore($savepointId, false);
        }
    }
    
    return ['success' => false, 'error' => 'Savepoints functions not available'];
}

/**
 * Get savepoint info
 * @param int $savepointId Savepoint ID
 * @return array|null Savepoint info or null
 */
function component_manager_get_savepoint_info($savepointId) {
    if (!component_manager_savepoints_available_check()) {
        return null;
    }
    
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM savepoints_history WHERE id = ?");
        $stmt->bind_param("i", $savepointId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting savepoint info: " . $e->getMessage());
        return null;
    }
}

