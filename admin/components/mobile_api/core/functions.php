<?php
/**
 * Mobile API Component - Core Helper Functions
 * All functions prefixed with mobile_api_ to avoid conflicts
 */

/**
 * Get component base URL
 * @return string Base URL
 */
function mobile_api_get_base_url() {
    if (defined('MOBILE_API_BASE_URL') && !empty(MOBILE_API_BASE_URL)) {
        return MOBILE_API_BASE_URL;
    }
    
    // Fallback: try to detect from current request
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = dirname(dirname(dirname(dirname($script))));
    
    return $protocol . $host . $path;
}

/**
 * Get component admin URL
 * @return string Admin URL
 */
function mobile_api_get_admin_url() {
    if (defined('MOBILE_API_ADMIN_URL') && !empty(MOBILE_API_ADMIN_URL)) {
        return MOBILE_API_ADMIN_URL;
    }
    
    return mobile_api_get_base_url() . '/admin/components/mobile_api';
}

/**
 * Get component root path
 * @return string Root path
 */
function mobile_api_get_root_path() {
    if (defined('MOBILE_API_ROOT_PATH') && !empty(MOBILE_API_ROOT_PATH)) {
        return MOBILE_API_ROOT_PATH;
    }
    
    return dirname(dirname(__DIR__));
}

/**
 * Get component admin path
 * @return string Admin path
 */
function mobile_api_get_admin_path() {
    if (defined('MOBILE_API_ADMIN_PATH') && !empty(MOBILE_API_ADMIN_PATH)) {
        return MOBILE_API_ADMIN_PATH;
    }
    
    return mobile_api_get_root_path() . '/admin/components/mobile_api';
}

/**
 * Sanitize output
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function mobile_api_sanitize($data) {
    if (is_array($data)) {
        return array_map('mobile_api_sanitize', $data);
    }
    
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Validate API key format
 * @param string $api_key API key to validate
 * @return bool Valid format
 */
function mobile_api_validate_api_key_format($api_key) {
    return !empty($api_key) && strlen($api_key) >= 32 && strlen($api_key) <= 64 && ctype_alnum($api_key);
}

/**
 * Generate random API key
 * @param int $length Key length (default 64)
 * @return string Generated API key
 */
function mobile_api_generate_api_key($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate unique tracking session ID
 * @return string Session ID
 */
function mobile_api_generate_tracking_session_id() {
    return bin2hex(random_bytes(32));
}

/**
 * Check if HTTPS is enabled
 * @return bool
 */
function mobile_api_is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

/**
 * Log analytics event
 * @param string $event_type Event type
 * @param string $category Event category
 * @param array $metadata Additional metadata
 * @return bool Success
 */
function mobile_api_log_event($event_type, $category, $metadata = []) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $metadata_json = !empty($metadata) ? json_encode($metadata) : null;
        
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_analytics (event_type, event_category, user_id, metadata)
            VALUES (?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ssis", $event_type, $category, $user_id, $metadata_json);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error logging event: " . $e->getMessage());
        return false;
    }
}

/**
 * Get component parameter value
 * @param string $section Parameter section
 * @param string $parameterName Parameter name
 * @param mixed $defaultValue Default value if not found
 * @return mixed Parameter value
 */
function mobile_api_get_parameter($section, $parameterName, $defaultValue = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return $defaultValue;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT value FROM mobile_api_parameters 
            WHERE section = ? AND parameter_name = ?
        ");
        $stmt->bind_param("ss", $section, $parameterName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['value'] : $defaultValue;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting parameter: " . $e->getMessage());
        return $defaultValue;
    }
}

/**
 * Set component parameter value
 * @param string $section Parameter section
 * @param string $parameterName Parameter name
 * @param mixed $value Parameter value
 * @param string|null $description Parameter description
 * @return bool Success
 */
function mobile_api_set_parameter($section, $parameterName, $value, $description = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_parameters (section, parameter_name, value, description)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                value = VALUES(value),
                description = COALESCE(VALUES(description), description),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->bind_param("ssss", $section, $parameterName, $value, $description);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error setting parameter: " . $e->getMessage());
        return false;
    }
}

