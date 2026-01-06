<?php
/**
 * Layout Component - Permissions Functions
 * Permission management and enforcement
 */

require_once __DIR__ . '/database.php';

/**
 * Check permission
 * @param string $resourceType Resource type
 * @param int $resourceId Resource ID
 * @param string $permission Permission name
 * @param int|null $userId User ID
 * @return bool Has permission
 */
function layout_permissions_check($resourceType, $resourceId, $permission, $userId = null) {
    $userId = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$userId) {
        return false;
    }
    
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('permissions');
        
        // Check user-specific permission
        $stmt = $conn->prepare("SELECT is_granted FROM {$tableName} WHERE resource_type = ? AND resource_id = ? AND user_id = ? AND permission = ?");
        $stmt->bind_param("siis", $resourceType, $resourceId, $userId, $permission);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return (bool)$row['is_granted'];
        }
        
        $stmt->close();
        
        // Check role-based permission (if role system exists)
        // This would integrate with access component if available
        
        // Default: no permission
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Permissions: Error checking permission: " . $e->getMessage());
        return false;
    }
}

/**
 * Grant permission
 * @param string $resourceType Resource type
 * @param int $resourceId Resource ID
 * @param string $permission Permission name
 * @param int|null $userId User ID
 * @param int|null $roleId Role ID
 * @return bool Success
 */
function layout_permissions_grant($resourceType, $resourceId, $permission, $userId = null, $roleId = null) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('permissions');
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (resource_type, resource_id, user_id, role_id, permission, is_granted) VALUES (?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE is_granted = 1");
        $stmt->bind_param("siiis", $resourceType, $resourceId, $userId, $roleId, $permission);
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Permissions: Error granting permission: " . $e->getMessage());
        return false;
    }
}

