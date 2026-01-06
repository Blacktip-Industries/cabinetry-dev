<?php
/**
 * Formula Builder Component - REST API Functions
 * API key management and authentication
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Create API key
 * @param string $name Key name
 * @param int $userId User ID
 * @param array $permissions Permissions array
 * @param int $rateLimit Rate limit per hour
 * @param string $expiresAt Expiration date (optional)
 * @return array Result with API key and secret
 */
function formula_builder_create_api_key($name, $userId = null, $permissions = [], $rateLimit = 1000, $expiresAt = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Generate API key and secret
        $apiKey = bin2hex(random_bytes(32)); // 64 character hex string
        $apiSecret = bin2hex(random_bytes(32)); // 64 character hex string
        $hashedSecret = password_hash($apiSecret, PASSWORD_DEFAULT);
        
        $permissionsJson = json_encode($permissions);
        
        $tableName = formula_builder_get_table_name('api_keys');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (api_key, api_secret, name, user_id, permissions, rate_limit, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisis", $apiKey, $hashedSecret, $name, $userId, $permissionsJson, $rateLimit, $expiresAt);
        $stmt->execute();
        $keyId = $conn->insert_id;
        $stmt->close();
        
        return [
            'success' => true,
            'key_id' => $keyId,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret // Only returned once
        ];
    } catch (Exception $e) {
        error_log("Formula Builder: Error creating API key: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Validate API key
 * @param string $apiKey API key
 * @param string $apiSecret API secret (optional, for initial validation)
 * @return array|null Key data or null if invalid
 */
function formula_builder_validate_api_key($apiKey, $apiSecret = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = formula_builder_get_table_name('api_keys');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE api_key = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param("s", $apiKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $key = $result->fetch_assoc();
        $stmt->close();
        
        if (!$key) {
            return null;
        }
        
        // Check expiration
        if ($key['expires_at'] && strtotime($key['expires_at']) < time()) {
            return null;
        }
        
        // Validate secret if provided
        if ($apiSecret && !password_verify($apiSecret, $key['api_secret'])) {
            return null;
        }
        
        // Update last used
        $stmt = $conn->prepare("UPDATE {$tableName} SET last_used = NOW() WHERE id = ?");
        $stmt->bind_param("i", $key['id']);
        $stmt->execute();
        $stmt->close();
        
        $key['permissions'] = json_decode($key['permissions'], true) ?: [];
        unset($key['api_secret']); // Don't return hashed secret
        
        return $key;
    } catch (Exception $e) {
        error_log("Formula Builder: Error validating API key: " . $e->getMessage());
        return null;
    }
}

/**
 * Revoke API key
 * @param int $keyId Key ID
 * @return array Result
 */
function formula_builder_revoke_api_key($keyId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('api_keys');
        $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $keyId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error revoking API key: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get API keys
 * @param int $userId User ID (optional filter)
 * @return array API keys
 */
function formula_builder_get_api_keys($userId = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('api_keys');
        
        if ($userId) {
            $stmt = $conn->prepare("SELECT id, api_key, name, user_id, permissions, rate_limit, is_active, last_used, created_at, expires_at FROM {$tableName} WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $conn->prepare("SELECT id, api_key, name, user_id, permissions, rate_limit, is_active, last_used, created_at, expires_at FROM {$tableName} ORDER BY created_at DESC");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $keys = [];
        while ($row = $result->fetch_assoc()) {
            $row['permissions'] = json_decode($row['permissions'], true) ?: [];
            $keys[] = $row;
        }
        
        $stmt->close();
        return $keys;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting API keys: " . $e->getMessage());
        return [];
    }
}

/**
 * Check rate limit
 * @param string $apiKey API key
 * @return bool True if within rate limit
 */
function formula_builder_check_rate_limit($apiKey) {
    // Simple rate limiting - in production, use Redis or similar
    $key = formula_builder_validate_api_key($apiKey);
    if (!$key) {
        return false;
    }
    
    // TODO: Implement proper rate limiting with Redis or database tracking
    // For now, just check if key is valid
    return true;
}

/**
 * Authenticate API request
 * @return array|null Authenticated key data or null
 */
function formula_builder_authenticate_api_request() {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
    $apiSecret = $_SERVER['HTTP_X_API_SECRET'] ?? $_GET['api_secret'] ?? null;
    
    if (!$apiKey) {
        return null;
    }
    
    $key = formula_builder_validate_api_key($apiKey, $apiSecret);
    
    if (!$key) {
        return null;
    }
    
    // Check rate limit
    if (!formula_builder_check_rate_limit($apiKey)) {
        return null;
    }
    
    return $key;
}

