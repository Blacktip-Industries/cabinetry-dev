<?php
/**
 * Access Component - Database Functions
 * All functions prefixed with access_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for access component
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function access_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('ACCESS_DB_HOST') && !empty(ACCESS_DB_HOST)) {
                $conn = new mysqli(
                    ACCESS_DB_HOST,
                    ACCESS_DB_USER ?? '',
                    ACCESS_DB_PASS ?? '',
                    ACCESS_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("Access: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Access: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Access: Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Get parameter value from access_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function access_get_parameter($section, $name, $default = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $stmt = $conn->prepare("SELECT value FROM access_parameters WHERE section = ? AND parameter_name = ?");
        if (!$stmt) {
            return $default;
        }
        
        $stmt->bind_param("ss", $section, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['value'] : $default;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set parameter value in access_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $value Parameter value
 * @return bool Success
 */
function access_set_parameter($section, $name, $value) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_parameters (section, parameter_name, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("sss", $section, $name, $value);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error setting parameter: " . $e->getMessage());
        return false;
    }
}

// ========== USER FUNCTIONS ==========

/**
 * Get user by ID
 * @param int $userId User ID
 * @return array|null User data or null
 */
function access_get_user($userId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting user: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user by email
 * @param string $email User email
 * @return array|null User data or null
 */
function access_get_user_by_email($email) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting user by email: " . $e->getMessage());
        return null;
    }
}

/**
 * Create new user
 * @param array $userData User data
 * @return int|false User ID on success, false on failure
 */
function access_create_user($userData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_users (email, username, password_hash, first_name, last_name, phone, timezone, language, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss",
            $userData['email'],
            $userData['username'] ?? null,
            $userData['password_hash'],
            $userData['first_name'] ?? null,
            $userData['last_name'] ?? null,
            $userData['phone'] ?? null,
            $userData['timezone'] ?? 'UTC',
            $userData['language'] ?? 'en',
            $userData['status'] ?? 'pending_verification'
        );
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            $stmt->close();
            return $userId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error creating user: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user
 * @param int $userId User ID
 * @param array $userData User data to update
 * @return bool Success
 */
function access_update_user($userId, $userData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $fields = [];
    $values = [];
    $types = '';
    
    $allowedFields = ['email', 'username', 'password_hash', 'first_name', 'last_name', 'phone', 'avatar_url', 'timezone', 'language', 'status', 'email_verified', 'two_factor_enabled', 'two_factor_secret', 'backup_codes', 'last_login', 'last_login_ip', 'failed_login_attempts', 'locked_until', 'metadata', 'preferences'];
    
    foreach ($userData as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $fields[] = "$key = ?";
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $values[] = $userId;
    $types .= 'i';
    
    try {
        $sql = "UPDATE access_users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error updating user: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete user
 * @param int $userId User ID
 * @return bool Success
 */
function access_delete_user($userId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM access_users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error deleting user: " . $e->getMessage());
        return false;
    }
}

/**
 * List users with filters
 * @param array $filters Filters (status, search, limit, offset)
 * @return array Users list
 */
function access_list_users($filters = []) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['search'])) {
        $where[] = "(email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }
    
    $sql = "SELECT * FROM access_users";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY created_at DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = (int)$filters['limit'];
        $types .= 'i';
        
        if (!empty($filters['offset'])) {
            $sql .= " OFFSET ?";
            $params[] = (int)$filters['offset'];
            $types .= 'i';
        }
    }
    
    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
        return $users;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error listing users: " . $e->getMessage());
        return [];
    }
}

// ========== ACCOUNT FUNCTIONS ==========

/**
 * Get account by ID
 * @param int $accountId Account ID
 * @return array|null Account data or null
 */
function access_get_account($accountId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT a.*, at.name as account_type_name, at.slug as account_type_slug FROM access_accounts a LEFT JOIN access_account_types at ON a.account_type_id = at.id WHERE a.id = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $account = $result->fetch_assoc();
        $stmt->close();
        return $account;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting account: " . $e->getMessage());
        return null;
    }
}

/**
 * Create new account
 * @param array $accountData Account data
 * @return int|false Account ID on success, false on failure
 */
function access_create_account($accountData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_accounts (account_type_id, account_name, account_code, email, phone, status, metadata, custom_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss",
            $accountData['account_type_id'],
            $accountData['account_name'],
            $accountData['account_code'] ?? null,
            $accountData['email'] ?? null,
            $accountData['phone'] ?? null,
            $accountData['status'] ?? 'pending',
            $accountData['metadata'] ?? null,
            $accountData['custom_data'] ?? null
        );
        
        if ($stmt->execute()) {
            $accountId = $conn->insert_id;
            $stmt->close();
            return $accountId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error creating account: " . $e->getMessage());
        return false;
    }
}

/**
 * Update account
 * @param int $accountId Account ID
 * @param array $accountData Account data to update
 * @return bool Success
 */
function access_update_account($accountId, $accountData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $fields = [];
    $values = [];
    $types = '';
    
    $allowedFields = ['account_type_id', 'account_name', 'account_code', 'email', 'phone', 'status', 'approved_at', 'approved_by', 'expiry_date', 'metadata', 'custom_data'];
    
    foreach ($accountData as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $fields[] = "$key = ?";
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $values[] = $accountId;
    $types .= 'i';
    
    try {
        $sql = "UPDATE access_accounts SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error updating account: " . $e->getMessage());
        return false;
    }
}

/**
 * List accounts with filters
 * @param array $filters Filters (account_type_id, status, search, limit, offset)
 * @return array Accounts list
 */
function access_list_accounts($filters = []) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['account_type_id'])) {
        $where[] = "a.account_type_id = ?";
        $params[] = (int)$filters['account_type_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['status'])) {
        $where[] = "a.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['search'])) {
        $where[] = "(a.account_name LIKE ? OR a.email LIKE ? OR a.account_code LIKE ?)";
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }
    
    $sql = "SELECT a.*, at.name as account_type_name, at.slug as account_type_slug FROM access_accounts a LEFT JOIN access_account_types at ON a.account_type_id = at.id";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY a.created_at DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = (int)$filters['limit'];
        $types .= 'i';
        
        if (!empty($filters['offset'])) {
            $sql .= " OFFSET ?";
            $params[] = (int)$filters['offset'];
            $types .= 'i';
        }
    }
    
    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $accounts = [];
        while ($row = $result->fetch_assoc()) {
            $accounts[] = $row;
        }
        $stmt->close();
        return $accounts;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error listing accounts: " . $e->getMessage());
        return [];
    }
}

// ========== ACCOUNT TYPE FUNCTIONS ==========

/**
 * Get account type by ID
 * @param int $accountTypeId Account type ID
 * @return array|null Account type data or null
 */
function access_get_account_type($accountTypeId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_account_types WHERE id = ?");
        $stmt->bind_param("i", $accountTypeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $accountType = $result->fetch_assoc();
        $stmt->close();
        return $accountType;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting account type: " . $e->getMessage());
        return null;
    }
}

/**
 * Get account type by slug
 * @param string $slug Account type slug
 * @return array|null Account type data or null
 */
function access_get_account_type_by_slug($slug) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_account_types WHERE slug = ? AND is_active = 1");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $accountType = $result->fetch_assoc();
        $stmt->close();
        return $accountType;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting account type by slug: " . $e->getMessage());
        return null;
    }
}

/**
 * List account types
 * @param bool $activeOnly Only return active account types
 * @return array Account types list
 */
function access_list_account_types($activeOnly = true) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM access_account_types";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY display_order ASC, name ASC";
        
        $result = $conn->query($sql);
        $accountTypes = [];
        while ($row = $result->fetch_assoc()) {
            $accountTypes[] = $row;
        }
        return $accountTypes;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error listing account types: " . $e->getMessage());
        return [];
    }
}

/**
 * Get account type fields
 * @param int $accountTypeId Account type ID
 * @return array Fields list
 */
function access_get_account_type_fields($accountTypeId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_account_type_fields WHERE account_type_id = ? ORDER BY display_order ASC, section ASC");
        $stmt->bind_param("i", $accountTypeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $fields = [];
        while ($row = $result->fetch_assoc()) {
            $fields[] = $row;
        }
        $stmt->close();
        return $fields;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting account type fields: " . $e->getMessage());
        return [];
    }
}

// ========== ROLE FUNCTIONS ==========

/**
 * Get role by ID
 * @param int $roleId Role ID
 * @return array|null Role data or null
 */
function access_get_role($roleId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_roles WHERE id = ?");
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $role = $result->fetch_assoc();
        $stmt->close();
        return $role;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting role: " . $e->getMessage());
        return null;
    }
}

/**
 * Get role by slug
 * @param string $slug Role slug
 * @return array|null Role data or null
 */
function access_get_role_by_slug($slug) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_roles WHERE slug = ? AND is_active = 1");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $role = $result->fetch_assoc();
        $stmt->close();
        return $role;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting role by slug: " . $e->getMessage());
        return null;
    }
}

/**
 * List roles
 * @param bool $activeOnly Only return active roles
 * @return array Roles list
 */
function access_list_roles($activeOnly = true) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM access_roles";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        
        $result = $conn->query($sql);
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        return $roles;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error listing roles: " . $e->getMessage());
        return [];
    }
}

// ========== USER-ACCOUNT RELATIONSHIP FUNCTIONS ==========

/**
 * Add user to account
 * @param int $userId User ID
 * @param int $accountId Account ID
 * @param int|null $roleId Role ID
 * @param bool $isPrimary Is primary account
 * @return bool Success
 */
function access_add_user_to_account($userId, $accountId, $roleId = null, $isPrimary = false) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_user_accounts (user_id, account_id, role_id, is_primary_account) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), is_primary_account = VALUES(is_primary_account)");
        $isPrimaryInt = $isPrimary ? 1 : 0;
        $stmt->bind_param("iiii", $userId, $accountId, $roleId, $isPrimaryInt);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error adding user to account: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user accounts
 * @param int $userId User ID
 * @return array Accounts list
 */
function access_get_user_accounts($userId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT ua.*, a.*, at.name as account_type_name, r.name as role_name FROM access_user_accounts ua LEFT JOIN access_accounts a ON ua.account_id = a.id LEFT JOIN access_account_types at ON a.account_type_id = at.id LEFT JOIN access_roles r ON ua.role_id = r.id WHERE ua.user_id = ? ORDER BY ua.is_primary_account DESC, ua.joined_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $accounts = [];
        while ($row = $result->fetch_assoc()) {
            $accounts[] = $row;
        }
        $stmt->close();
        return $accounts;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting user accounts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get account users
 * @param int $accountId Account ID
 * @return array Users list
 */
function access_get_account_users($accountId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT ua.*, u.*, r.name as role_name FROM access_user_accounts ua LEFT JOIN access_users u ON ua.user_id = u.id LEFT JOIN access_roles r ON ua.role_id = r.id WHERE ua.account_id = ? ORDER BY ua.is_primary_account DESC, ua.joined_at DESC");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
        return $users;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting account users: " . $e->getMessage());
        return [];
    }
}

/**
 * Remove user from account
 * @param int $userId User ID
 * @param int $accountId Account ID
 * @return bool Success
 */
function access_remove_user_from_account($userId, $accountId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM access_user_accounts WHERE user_id = ? AND account_id = ?");
        $stmt->bind_param("ii", $userId, $accountId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error removing user from account: " . $e->getMessage());
        return false;
    }
}

// ========== PERMISSION FUNCTIONS ==========

/**
 * Get permission by key
 * @param string $permissionKey Permission key
 * @return array|null Permission data or null
 */
function access_get_permission($permissionKey) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_permissions WHERE permission_key = ?");
        $stmt->bind_param("s", $permissionKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $permission = $result->fetch_assoc();
        $stmt->close();
        return $permission;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting permission: " . $e->getMessage());
        return null;
    }
}

/**
 * Get role permissions
 * @param int $roleId Role ID
 * @return array Permissions list
 */
function access_get_role_permissions($roleId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT p.* FROM access_permissions p INNER JOIN access_role_permissions rp ON p.id = rp.permission_id WHERE rp.role_id = ?");
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }
        $stmt->close();
        return $permissions;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting role permissions: " . $e->getMessage());
        return [];
    }
}

/**
 * List all permissions
 * @param string|null $category Filter by category
 * @return array Permissions list
 */
function access_list_permissions($category = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM access_permissions";
        if ($category) {
            $sql .= " WHERE category = ?";
        }
        $sql .= " ORDER BY category ASC, permission_name ASC";
        
        $stmt = $conn->prepare($sql);
        if ($category) {
            $stmt->bind_param("s", $category);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }
        $stmt->close();
        return $permissions;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error listing permissions: " . $e->getMessage());
        return [];
    }
}

