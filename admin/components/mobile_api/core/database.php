<?php
/**
 * Mobile API Component - Database Functions
 * All functions prefixed with mobile_api_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for mobile_api component
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function mobile_api_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('MOBILE_API_DB_HOST') && !empty(MOBILE_API_DB_HOST)) {
                $conn = new mysqli(
                    MOBILE_API_DB_HOST,
                    MOBILE_API_DB_USER ?? '',
                    MOBILE_API_DB_PASS ?? '',
                    MOBILE_API_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("Mobile API: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Mobile API: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Mobile API: Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Get parameter value from mobile_api_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function mobile_api_get_parameter($section, $name, $default = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $stmt = $conn->prepare("SELECT value FROM mobile_api_parameters WHERE section = ? AND parameter_name = ?");
        if (!$stmt) {
            return $default;
        }
        
        $stmt->bind_param("ss", $section, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['value'];
        }
        
        $stmt->close();
        return $default;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set parameter value in mobile_api_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $value Parameter value
 * @param string $description Optional description
 * @return bool Success
 */
function mobile_api_set_parameter($section, $name, $value, $description = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $value_str = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
        $description_str = $description ?? '';
        
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_parameters (section, parameter_name, description, value)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE value = ?, description = ?, updated_at = CURRENT_TIMESTAMP
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ssssss", $section, $name, $description_str, $value_str, $value_str, $description_str);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error setting parameter: " . $e->getMessage());
        return false;
    }
}

/**
 * Get config value from mobile_api_config table
 * @param string $key Config key
 * @param mixed $default Default value if not found
 * @return mixed Config value or default
 */
function mobile_api_get_config($key, $default = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $stmt = $conn->prepare("SELECT config_value FROM mobile_api_config WHERE config_key = ?");
        if (!$stmt) {
            return $default;
        }
        
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['config_value'];
        }
        
        $stmt->close();
        return $default;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting config: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set config value in mobile_api_config table
 * @param string $key Config key
 * @param mixed $value Config value
 * @return bool Success
 */
function mobile_api_set_config($key, $value) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $value_str = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
        
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_config (config_key, config_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE config_value = ?, updated_at = CURRENT_TIMESTAMP
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sss", $key, $value_str, $value_str);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error setting config: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if mobile_api component is installed
 * @return bool
 */
function mobile_api_is_installed() {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $result = $conn->query("SHOW TABLES LIKE 'mobile_api_config'");
        return $result && $result->num_rows > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get component version
 * @return string Version string
 */
function mobile_api_get_version() {
    if (defined('MOBILE_API_VERSION')) {
        return MOBILE_API_VERSION;
    }
    
    $version_file = __DIR__ . '/../VERSION';
    if (file_exists($version_file)) {
        return trim(file_get_contents($version_file));
    }
    
    return '1.0.0';
}

