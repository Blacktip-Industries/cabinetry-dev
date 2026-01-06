<?php
/**
 * Mobile API Component - Authentication System
 * Multi-method authentication (API keys, JWT, OAuth2, session-based)
 */

/**
 * Main authentication router
 * @param array $request Request data
 * @return array Authentication result with user context
 */
function mobile_api_authenticate_request($request = null) {
    if ($request === null) {
        $request = $_SERVER;
    }
    
    // Check for API key in header or query
    $apiKey = $request['HTTP_X_API_KEY'] ?? $request['HTTP_AUTHORIZATION'] ?? $_GET['api_key'] ?? null;
    
    // Extract Bearer token if present
    if ($apiKey && strpos($apiKey, 'Bearer ') === 0) {
        $apiKey = substr($apiKey, 7);
    }
    
    // Try API key authentication first
    if ($apiKey) {
        $result = mobile_api_validate_api_key($apiKey);
        if ($result['success']) {
            return $result;
        }
    }
    
    // Try JWT token
    $jwtToken = $apiKey ?? ($request['HTTP_AUTHORIZATION'] ?? null);
    if ($jwtToken && strpos($jwtToken, 'Bearer ') === 0) {
        $jwtToken = substr($jwtToken, 7);
    }
    
    if ($jwtToken && strlen($jwtToken) > 50) { // JWT tokens are longer
        $result = mobile_api_validate_jwt($jwtToken);
        if ($result['success']) {
            return $result;
        }
    }
    
    // Try session-based auth (if access component available)
    if (function_exists('access_get_current_user')) {
        $user = access_get_current_user();
        if ($user) {
            return [
                'success' => true,
                'user_id' => $user['id'] ?? null,
                'auth_method' => 'session',
                'user' => $user
            ];
        }
    }
    
    return [
        'success' => false,
        'error' => 'Authentication required',
        'auth_method' => null
    ];
}

/**
 * Validate API key
 * @param string $apiKey API key
 * @return array Validation result
 */
function mobile_api_validate_api_key($apiKey) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM mobile_api_keys 
            WHERE api_key = ? AND is_active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->bind_param("s", $apiKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $key = $result->fetch_assoc();
        $stmt->close();
        
        if (!$key) {
            return ['success' => false, 'error' => 'Invalid API key'];
        }
        
        // Update last used timestamp
        $updateStmt = $conn->prepare("UPDATE mobile_api_keys SET last_used_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $key['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Log API usage
        mobile_api_log_event('api_key_auth', 'authentication', [
            'key_id' => $key['id'],
            'key_name' => $key['key_name']
        ]);
        
        return [
            'success' => true,
            'user_id' => null, // API keys may not be user-specific
            'auth_method' => 'api_key',
            'api_key_id' => $key['id'],
            'permissions' => json_decode($key['permissions'] ?? '[]', true),
            'rate_limit' => $key['rate_limit_per_minute']
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error validating API key: " . $e->getMessage());
        return ['success' => false, 'error' => 'Authentication error'];
    }
}

/**
 * Generate JWT token
 * @param int $userId User ID
 * @param array $payload Additional payload data
 * @return array Token data
 */
function mobile_api_generate_jwt($userId, $payload = []) {
    $secret = mobile_api_get_parameter('Authentication', 'jwt_secret', '');
    if (empty($secret)) {
        return ['success' => false, 'error' => 'JWT secret not configured'];
    }
    
    $expirationHours = (int)mobile_api_get_parameter('Authentication', 'jwt_expiration_hours', 24);
    $expiresAt = time() + ($expirationHours * 3600);
    $refreshExpiresAt = time() + (($expirationHours * 2) * 3600);
    
    $tokenPayload = [
        'user_id' => $userId,
        'iat' => time(),
        'exp' => $expiresAt,
        ...$payload
    ];
    
    // Simple JWT encoding (would use proper JWT library in production)
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode($tokenPayload));
    $signature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);
    $signature = base64_encode($signature);
    
    $token = $header . '.' . $payload . '.' . $signature;
    
    // Generate refresh token
    $refreshToken = bin2hex(random_bytes(32));
    
    // Store tokens in database
    $conn = mobile_api_get_db_connection();
    if ($conn) {
        try {
            $tokenHash = hash('sha256', $token);
            $refreshTokenHash = hash('sha256', $refreshToken);
            
            $stmt = $conn->prepare("
                INSERT INTO mobile_api_jwt_tokens 
                (user_id, token_hash, refresh_token_hash, expires_at, refresh_expires_at, device_info)
                VALUES (?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?)
            ");
            
            $deviceInfo = json_encode($payload['device_info'] ?? []);
            $stmt->bind_param("isssis", $userId, $tokenHash, $refreshTokenHash, $expiresAt, $refreshExpiresAt, $deviceInfo);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Mobile API: Error storing JWT token: " . $e->getMessage());
        }
    }
    
    return [
        'success' => true,
        'token' => $token,
        'refresh_token' => $refreshToken,
        'expires_at' => $expiresAt,
        'expires_in' => $expirationHours * 3600
    ];
}

/**
 * Validate JWT token
 * @param string $token JWT token
 * @return array Validation result
 */
function mobile_api_validate_jwt($token) {
    $secret = mobile_api_get_parameter('Authentication', 'jwt_secret', '');
    if (empty($secret)) {
        return ['success' => false, 'error' => 'JWT secret not configured'];
    }
    
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return ['success' => false, 'error' => 'Invalid token format'];
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Verify signature
    $expectedSignature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);
    $expectedSignature = base64_encode($expectedSignature);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return ['success' => false, 'error' => 'Invalid token signature'];
    }
    
    // Decode payload
    $payloadData = json_decode(base64_decode($payload), true);
    if (!$payloadData) {
        return ['success' => false, 'error' => 'Invalid token payload'];
    }
    
    // Check expiration
    if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
        return ['success' => false, 'error' => 'Token expired'];
    }
    
    // Verify token exists in database
    $conn = mobile_api_get_db_connection();
    if ($conn) {
        $tokenHash = hash('sha256', $token);
        $stmt = $conn->prepare("
            SELECT * FROM mobile_api_jwt_tokens 
            WHERE token_hash = ? AND is_active = 1 AND expires_at > NOW()
        ");
        $stmt->bind_param("s", $tokenHash);
        $stmt->execute();
        $result = $stmt->get_result();
        $tokenRecord = $result->fetch_assoc();
        $stmt->close();
        
        if (!$tokenRecord) {
            return ['success' => false, 'error' => 'Token not found or expired'];
        }
    }
    
    return [
        'success' => true,
        'user_id' => $payloadData['user_id'] ?? null,
        'auth_method' => 'jwt',
        'payload' => $payloadData
    ];
}

/**
 * Refresh JWT token
 * @param string $refreshToken Refresh token
 * @return array New token data
 */
function mobile_api_refresh_token($refreshToken) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $refreshTokenHash = hash('sha256', $refreshToken);
        $stmt = $conn->prepare("
            SELECT * FROM mobile_api_jwt_tokens 
            WHERE refresh_token_hash = ? AND is_active = 1 AND refresh_expires_at > NOW()
        ");
        $stmt->bind_param("s", $refreshTokenHash);
        $stmt->execute();
        $result = $stmt->get_result();
        $tokenRecord = $result->fetch_assoc();
        $stmt->close();
        
        if (!$tokenRecord) {
            return ['success' => false, 'error' => 'Invalid or expired refresh token'];
        }
        
        // Generate new token
        $newToken = mobile_api_generate_jwt($tokenRecord['user_id']);
        
        // Deactivate old token
        $updateStmt = $conn->prepare("UPDATE mobile_api_jwt_tokens SET is_active = 0 WHERE id = ?");
        $updateStmt->bind_param("i", $tokenRecord['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        return $newToken;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error refreshing token: " . $e->getMessage());
        return ['success' => false, 'error' => 'Token refresh failed'];
    }
}

/**
 * Create API key
 * @param string $keyName Key name
 * @param array $permissions Permissions
 * @param int $rateLimit Rate limit per minute
 * @param string|null $expiresAt Expiration date
 * @return array Created key data
 */
function mobile_api_create_api_key($keyName, $permissions = [], $rateLimit = 60, $expiresAt = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $apiKey = mobile_api_generate_api_key();
        $apiSecret = mobile_api_generate_api_key(32);
        $permissionsJson = json_encode($permissions);
        
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_keys 
            (key_name, api_key, api_secret, permissions, rate_limit_per_minute, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $expiresAtValue = $expiresAt ? date('Y-m-d H:i:s', strtotime($expiresAt)) : null;
        $stmt->bind_param("ssssis", $keyName, $apiKey, $apiSecret, $permissionsJson, $rateLimit, $expiresAtValue);
        $stmt->execute();
        $keyId = $conn->insert_id;
        $stmt->close();
        
        return [
            'success' => true,
            'key_id' => $keyId,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error creating API key: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Revoke API key
 * @param int $keyId Key ID
 * @return bool Success
 */
function mobile_api_revoke_api_key($keyId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE mobile_api_keys SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $keyId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error revoking API key: " . $e->getMessage());
        return false;
    }
}

