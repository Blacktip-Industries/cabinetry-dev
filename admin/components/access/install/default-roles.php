<?php
/**
 * Access Component - Default Roles and Permissions
 * Creates default roles: admin, user, viewer with their permissions
 * @param mysqli $conn Database connection
 * @return array ['success' => bool, 'roles_inserted' => int, 'permissions_inserted' => int, 'errors' => array]
 */
function access_insert_default_roles($conn) {
    $rolesInserted = 0;
    $permissionsInserted = 0;
    $errors = [];
    
    // Define default permissions
    $permissions = [
        // Account permissions
        ['key' => 'view_accounts', 'name' => 'View Accounts', 'description' => 'View account listings and details', 'category' => 'Accounts'],
        ['key' => 'create_accounts', 'name' => 'Create Accounts', 'description' => 'Create new accounts', 'category' => 'Accounts'],
        ['key' => 'edit_accounts', 'name' => 'Edit Accounts', 'description' => 'Edit existing accounts', 'category' => 'Accounts'],
        ['key' => 'delete_accounts', 'name' => 'Delete Accounts', 'description' => 'Delete accounts', 'category' => 'Accounts'],
        ['key' => 'approve_accounts', 'name' => 'Approve Accounts', 'description' => 'Approve pending account registrations', 'category' => 'Accounts'],
        
        // User permissions
        ['key' => 'view_users', 'name' => 'View Users', 'description' => 'View user listings and details', 'category' => 'Users'],
        ['key' => 'create_users', 'name' => 'Create Users', 'description' => 'Create new users', 'category' => 'Users'],
        ['key' => 'edit_users', 'name' => 'Edit Users', 'description' => 'Edit existing users', 'category' => 'Users'],
        ['key' => 'delete_users', 'name' => 'Delete Users', 'description' => 'Delete users', 'category' => 'Users'],
        ['key' => 'manage_user_permissions', 'name' => 'Manage User Permissions', 'description' => 'Manage user roles and permissions', 'category' => 'Users'],
        
        // Order permissions
        ['key' => 'place_orders', 'name' => 'Place Orders', 'description' => 'Place new orders', 'category' => 'Orders'],
        ['key' => 'view_orders', 'name' => 'View Orders', 'description' => 'View order listings and details', 'category' => 'Orders'],
        ['key' => 'edit_orders', 'name' => 'Edit Orders', 'description' => 'Edit existing orders', 'category' => 'Orders'],
        ['key' => 'cancel_orders', 'name' => 'Cancel Orders', 'description' => 'Cancel orders', 'category' => 'Orders'],
        
        // Quote permissions
        ['key' => 'request_quotes', 'name' => 'Request Quotes', 'description' => 'Request new quotes', 'category' => 'Quotes'],
        ['key' => 'view_quotes', 'name' => 'View Quotes', 'description' => 'View quote listings and details', 'category' => 'Quotes'],
        ['key' => 'edit_quotes', 'name' => 'Edit Quotes', 'description' => 'Edit existing quotes', 'category' => 'Quotes'],
        ['key' => 'approve_quotes', 'name' => 'Approve Quotes', 'description' => 'Approve quotes', 'category' => 'Quotes'],
        
        // Reports permissions
        ['key' => 'view_reports', 'name' => 'View Reports', 'description' => 'View reports and analytics', 'category' => 'Reports'],
        ['key' => 'export_reports', 'name' => 'Export Reports', 'description' => 'Export reports to various formats', 'category' => 'Reports'],
        
        // System permissions
        ['key' => 'manage_roles', 'name' => 'Manage Roles', 'description' => 'Manage roles and permissions', 'category' => 'System'],
        ['key' => 'manage_account_types', 'name' => 'Manage Account Types', 'description' => 'Manage account types and fields', 'category' => 'System'],
        ['key' => 'manage_settings', 'name' => 'Manage Settings', 'description' => 'Manage system settings', 'category' => 'System'],
        ['key' => 'view_audit_log', 'name' => 'View Audit Log', 'description' => 'View audit logs', 'category' => 'System'],
    ];
    
    // Insert permissions
    $permissionIds = [];
    foreach ($permissions as $perm) {
        try {
            $stmt = $conn->prepare("INSERT INTO access_permissions (permission_key, permission_name, description, category, is_system_permission) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE permission_name = VALUES(permission_name), description = VALUES(description), category = VALUES(category)");
            $stmt->bind_param("ssss",
                $perm['key'],
                $perm['name'],
                $perm['description'],
                $perm['category']
            );
            
            if ($stmt->execute()) {
                $permissionId = $conn->insert_id;
                if ($permissionId === 0) {
                    // Permission already exists, get its ID
                    $getIdStmt = $conn->prepare("SELECT id FROM access_permissions WHERE permission_key = ?");
                    $getIdStmt->bind_param("s", $perm['key']);
                    $getIdStmt->execute();
                    $idResult = $getIdStmt->get_result();
                    $idRow = $idResult->fetch_assoc();
                    $permissionId = $idRow['id'];
                    $getIdStmt->close();
                }
                $permissionIds[$perm['key']] = $permissionId;
                $permissionsInserted++;
            } else {
                $errors[] = "Failed to insert permission: " . $perm['key'];
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error inserting permission {$perm['key']}: " . $e->getMessage();
        }
    }
    
    // Define default roles
    $roles = [
        [
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Full system administrator with all permissions',
            'is_system_role' => 1,
            'permissions' => array_keys($permissionIds) // All permissions
        ],
        [
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Standard user with basic permissions',
            'is_system_role' => 1,
            'permissions' => [
                'view_accounts', 'view_users', 'place_orders', 'view_orders', 
                'request_quotes', 'view_quotes', 'view_reports'
            ]
        ],
        [
            'name' => 'Viewer',
            'slug' => 'viewer',
            'description' => 'Read-only access to view information',
            'is_system_role' => 1,
            'permissions' => [
                'view_accounts', 'view_users', 'view_orders', 'view_quotes', 'view_reports'
            ]
        ]
    ];
    
    // Insert roles and assign permissions
    foreach ($roles as $role) {
        try {
            // Insert role
            $stmt = $conn->prepare("INSERT INTO access_roles (name, slug, description, is_system_role, is_active) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE description = VALUES(description)");
            $stmt->bind_param("sssi",
                $role['name'],
                $role['slug'],
                $role['description'],
                $role['is_system_role']
            );
            
            if ($stmt->execute()) {
                $roleId = $conn->insert_id;
                if ($roleId === 0) {
                    // Role already exists, get its ID
                    $getIdStmt = $conn->prepare("SELECT id FROM access_roles WHERE slug = ?");
                    $getIdStmt->bind_param("s", $role['slug']);
                    $getIdStmt->execute();
                    $idResult = $getIdStmt->get_result();
                    $idRow = $idResult->fetch_assoc();
                    $roleId = $idRow['id'];
                    $getIdStmt->close();
                }
                $rolesInserted++;
                
                // Assign permissions to role
                foreach ($role['permissions'] as $permKey) {
                    if (isset($permissionIds[$permKey])) {
                        try {
                            $permStmt = $conn->prepare("INSERT INTO access_role_permissions (role_id, permission_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE role_id = VALUES(role_id)");
                            $permStmt->bind_param("ii", $roleId, $permissionIds[$permKey]);
                            $permStmt->execute();
                            $permStmt->close();
                        } catch (mysqli_sql_exception $e) {
                            $errors[] = "Error assigning permission {$permKey} to role {$role['slug']}: " . $e->getMessage();
                        }
                    }
                }
            } else {
                $errors[] = "Failed to insert role: " . $role['name'];
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error inserting role {$role['name']}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'roles_inserted' => $rolesInserted,
        'permissions_inserted' => $permissionsInserted,
        'errors' => $errors
    ];
}

