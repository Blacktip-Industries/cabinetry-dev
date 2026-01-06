<?php
/**
 * Order Management Component - Permissions Functions
 * Role-based access control
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if user has permission
 * @param int $userId User ID
 * @param string $permission Permission name
 * @return bool True if has permission
 */
function order_management_user_has_permission($userId, $permission) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('user_permissions');
    
    // Check direct permission
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE user_id = ? AND permission = ? AND is_granted = 1 LIMIT 1");
    $stmt->bind_param("is", $userId, $permission);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasDirect = $result->num_rows > 0;
    $stmt->close();
    
    if ($hasDirect) {
        return true;
    }
    
    // Check role permissions
    $rolesTable = order_management_get_table_name('user_roles');
    $rolePermissionsTable = order_management_get_table_name('role_permissions');
    
    $query = "SELECT rp.permission FROM {$rolesTable} ur
             INNER JOIN {$rolePermissionsTable} rp ON ur.role_id = rp.role_id
             WHERE ur.user_id = ? AND rp.permission = ? AND rp.is_granted = 1
             LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $userId, $permission);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasRole = $result->num_rows > 0;
    $stmt->close();
    
    return $hasRole;
}

/**
 * Grant permission to user
 * @param int $userId User ID
 * @param string $permission Permission name
 * @return array Result
 */
function order_management_grant_permission($userId, $permission) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('user_permissions');
    
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE user_id = ? AND permission = ? LIMIT 1");
    $stmt->bind_param("is", $userId, $permission);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        // Update
        $stmt = $conn->prepare("UPDATE {$tableName} SET is_granted = 1, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $existing['id']);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO {$tableName} (user_id, permission, is_granted, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("is", $userId, $permission);
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
}

