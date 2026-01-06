<?php
/**
 * Access Component - Permission Functions
 * Handles permission checking, role permissions, custom permissions
 */

require_once __DIR__ . '/database.php';

/**
 * Check if user has permission
 * @param int $userId User ID
 * @param string $permissionKey Permission key
 * @param int|null $accountId Account ID (for account-scoped permissions)
 * @return bool True if user has permission
 */
function access_user_has_permission($userId, $permissionKey, $accountId = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    // Get permission
    $permission = access_get_permission($permissionKey);
    if (!$permission) {
        return false;
    }
    
    // Check custom user permission override (most specific)
    $customPermission = access_get_user_permission($userId, $permission['id'], $accountId);
    if ($customPermission !== null) {
        return $customPermission;
    }
    
    // Get user accounts
    $userAccounts = access_get_user_accounts($userId);
    if (empty($userAccounts)) {
        return false;
    }
    
    // Check role permissions for each account
    foreach ($userAccounts as $userAccount) {
        // If account_id is specified, only check that account
        if ($accountId !== null && $userAccount['account_id'] != $accountId) {
            continue;
        }
        
        if (!empty($userAccount['role_id'])) {
            $rolePermissions = access_get_role_permissions($userAccount['role_id']);
            foreach ($rolePermissions as $rolePerm) {
                if ($rolePerm['permission_key'] === $permissionKey) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

/**
 * Get user permission override (custom permission)
 * @param int $userId User ID
 * @param int $permissionId Permission ID
 * @param int|null $accountId Account ID (null for global)
 * @return bool|null True if granted, false if denied, null if no override
 */
function access_get_user_permission($userId, $permissionId, $accountId = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        // First check account-specific permission
        if ($accountId !== null) {
            $stmt = $conn->prepare("SELECT granted FROM access_user_permissions WHERE user_id = ? AND account_id = ? AND permission_id = ?");
            $stmt->bind_param("iii", $userId, $accountId, $permissionId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row) {
                return (bool)$row['granted'];
            }
        }
        
        // Check global permission (account_id is NULL)
        $stmt = $conn->prepare("SELECT granted FROM access_user_permissions WHERE user_id = ? AND account_id IS NULL AND permission_id = ?");
        $stmt->bind_param("ii", $userId, $permissionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            return (bool)$row['granted'];
        }
        
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting user permission: " . $e->getMessage());
        return null;
    }
}

/**
 * Set user permission override
 * @param int $userId User ID
 * @param int $permissionId Permission ID
 * @param bool $granted True to grant, false to deny
 * @param int|null $accountId Account ID (null for global)
 * @return bool Success
 */
function access_set_user_permission($userId, $permissionId, $granted, $accountId = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $grantedInt = $granted ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO access_user_permissions (user_id, account_id, permission_id, granted) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE granted = VALUES(granted), updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("iiii", $userId, $accountId, $permissionId, $grantedInt);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error setting user permission: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove user permission override
 * @param int $userId User ID
 * @param int $permissionId Permission ID
 * @param int|null $accountId Account ID (null for global)
 * @return bool Success
 */
function access_remove_user_permission($userId, $permissionId, $accountId = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        if ($accountId !== null) {
            $stmt = $conn->prepare("DELETE FROM access_user_permissions WHERE user_id = ? AND account_id = ? AND permission_id = ?");
            $stmt->bind_param("iii", $userId, $accountId, $permissionId);
        } else {
            $stmt = $conn->prepare("DELETE FROM access_user_permissions WHERE user_id = ? AND account_id IS NULL AND permission_id = ?");
            $stmt->bind_param("ii", $userId, $permissionId);
        }
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error removing user permission: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all user permissions (role + custom)
 * @param int $userId User ID
 * @param int|null $accountId Account ID
 * @return array Permissions list with source (role/custom)
 */
function access_get_all_user_permissions($userId, $accountId = null) {
    $permissions = [];
    
    // Get user accounts
    $userAccounts = access_get_user_accounts($userId);
    if (empty($userAccounts)) {
        return [];
    }
    
    foreach ($userAccounts as $userAccount) {
        // If account_id is specified, only check that account
        if ($accountId !== null && $userAccount['account_id'] != $accountId) {
            continue;
        }
        
        // Get role permissions
        if (!empty($userAccount['role_id'])) {
            $rolePermissions = access_get_role_permissions($userAccount['role_id']);
            foreach ($rolePermissions as $perm) {
                $permissions[$perm['permission_key']] = [
                    'permission' => $perm,
                    'source' => 'role',
                    'role_id' => $userAccount['role_id'],
                    'account_id' => $userAccount['account_id']
                ];
            }
        }
    }
    
    // Get custom permissions
    $conn = access_get_db_connection();
    if ($conn !== null) {
        try {
            if ($accountId !== null) {
                $stmt = $conn->prepare("SELECT up.*, p.* FROM access_user_permissions up INNER JOIN access_permissions p ON up.permission_id = p.id WHERE up.user_id = ? AND (up.account_id = ? OR up.account_id IS NULL)");
                $stmt->bind_param("ii", $userId, $accountId);
            } else {
                $stmt = $conn->prepare("SELECT up.*, p.* FROM access_user_permissions up INNER JOIN access_permissions p ON up.permission_id = p.id WHERE up.user_id = ?");
                $stmt->bind_param("i", $userId);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $permissions[$row['permission_key']] = [
                    'permission' => $row,
                    'source' => 'custom',
                    'granted' => (bool)$row['granted'],
                    'account_id' => $row['account_id']
                ];
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Access: Error getting custom permissions: " . $e->getMessage());
        }
    }
    
    return $permissions;
}

/**
 * Assign role to user in account
 * @param int $userId User ID
 * @param int $accountId Account ID
 * @param int $roleId Role ID
 * @return bool Success
 */
function access_assign_role_to_user($userId, $accountId, $roleId) {
    return access_add_user_to_account($userId, $accountId, $roleId, false);
}

/**
 * Get user role in account
 * @param int $userId User ID
 * @param int $accountId Account ID
 * @return array|null Role data or null
 */
function access_get_user_role_in_account($userId, $accountId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT r.* FROM access_roles r INNER JOIN access_user_accounts ua ON r.id = ua.role_id WHERE ua.user_id = ? AND ua.account_id = ?");
        $stmt->bind_param("ii", $userId, $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $role = $result->fetch_assoc();
        $stmt->close();
        return $role;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting user role: " . $e->getMessage());
        return null;
    }
}

