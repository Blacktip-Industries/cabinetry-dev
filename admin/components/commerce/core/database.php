<?php
/**
 * Commerce Component - Database Functions
 * All database operations for the commerce component
 */

/**
 * Get database connection
 * @return mysqli|null Database connection or null on failure
 */
function commerce_get_db_connection() {
    static $conn = null;
    
    if ($conn !== null) {
        return $conn;
    }
    
    // Try to load config
    $configPath = __DIR__ . '/../config.php';
    if (!file_exists($configPath)) {
        // Try to detect from base system
        $baseConfigs = [
            __DIR__ . '/../../../config/database.php',
            __DIR__ . '/../../includes/config.php',
            __DIR__ . '/../../../config.php'
        ];
        
        foreach ($baseConfigs as $baseConfig) {
            if (file_exists($baseConfig)) {
                require_once $baseConfig;
                break;
            }
        }
    } else {
        require_once $configPath;
    }
    
    // Use component-specific constants if available, otherwise fall back to base system
    $host = defined('COMMERCE_DB_HOST') ? COMMERCE_DB_HOST : (defined('DB_HOST') ? DB_HOST : 'localhost');
    $user = defined('COMMERCE_DB_USER') ? COMMERCE_DB_USER : (defined('DB_USER') ? DB_USER : 'root');
    $pass = defined('COMMERCE_DB_PASS') ? COMMERCE_DB_PASS : (defined('DB_PASS') ? DB_PASS : '');
    $name = defined('COMMERCE_DB_NAME') ? COMMERCE_DB_NAME : (defined('DB_NAME') ? DB_NAME : '');
    
    try {
        $conn = new mysqli($host, $user, $pass, $name);
        
        if ($conn->connect_error) {
            error_log("Commerce: Database connection failed: " . $conn->connect_error);
            return null;
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Commerce: Database connection error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get table name with prefix
 * @param string $tableName Table name without prefix
 * @return string Full table name with prefix
 */
function commerce_get_table_name($tableName) {
    $prefix = defined('COMMERCE_TABLE_PREFIX') ? COMMERCE_TABLE_PREFIX : 'commerce_';
    return $prefix . $tableName;
}

/**
 * Check if component is installed
 * @return bool True if installed
 */
function commerce_is_installed() {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('config');
    $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
    return $result && $result->num_rows > 0;
}

/**
 * Get component version
 * @return string|null Version or null
 */
function commerce_get_version() {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('config');
    $stmt = $conn->prepare("SELECT config_value FROM {$tableName} WHERE config_key = 'version' LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['config_value'] : null;
    }
    
    return null;
}

/**
 * Get parameter value
 * @param string $parameterName Parameter name
 * @param mixed $defaultValue Default value if not found
 * @return mixed Parameter value or default
 */
function commerce_get_parameter($parameterName, $default = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    $tableName = commerce_get_table_name('parameters');
    $stmt = $conn->prepare("SELECT value FROM {$tableName} WHERE parameter_name = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $parameterName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['value'] : $default;
    }
    
    return $default;
}

/**
 * Set parameter value
 * @param string $parameterName Parameter name
 * @param mixed $value Parameter value
 * @return bool Success
 */
function commerce_set_parameter($parameterName, $value) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('parameters');
    $valueStr = is_array($value) ? json_encode($value) : (string)$value;
    $stmt = $conn->prepare("INSERT INTO {$tableName} (parameter_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP");
    if ($stmt) {
        $stmt->bind_param("ss", $parameterName, $valueStr);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

