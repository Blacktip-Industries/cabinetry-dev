<?php
/**
 * Component Manager - Database Functions
 * Database functions with component_manager_ prefix
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection
 * Tries to use base system connection first, then component's own connection
 * @return mysqli|null Database connection or null on failure
 */
function component_manager_get_db_connection() {
    // Try base system connection first
    if (function_exists('getDBConnection')) {
        $conn = getDBConnection();
        if ($conn !== null) {
            return $conn;
        }
    }
    
    // Fall back to component's own connection
    if (defined('COMPONENT_MANAGER_DB_HOST') && defined('COMPONENT_MANAGER_DB_USER') && defined('COMPONENT_MANAGER_DB_NAME')) {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(
                COMPONENT_MANAGER_DB_HOST,
                COMPONENT_MANAGER_DB_USER,
                defined('COMPONENT_MANAGER_DB_PASS') ? COMPONENT_MANAGER_DB_PASS : '',
                COMPONENT_MANAGER_DB_NAME,
                defined('COMPONENT_MANAGER_DB_PORT') ? COMPONENT_MANAGER_DB_PORT : 3306
            );
            
            if ($conn->connect_error) {
                error_log("Component Manager: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            $conn->set_charset("utf8mb4");
            return $conn;
        } catch (Exception $e) {
            error_log("Component Manager: Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return null;
}

/**
 * Get table name with prefix
 * @param string $tableName Table name without prefix
 * @return string Full table name with prefix
 */
function component_manager_get_table_name($tableName) {
    $prefix = defined('COMPONENT_MANAGER_TABLE_PREFIX') ? COMPONENT_MANAGER_TABLE_PREFIX : 'component_manager_';
    return $prefix . $tableName;
}

/**
 * Get parameter value from component_manager_parameters table
 * Falls back to base system getParameter if available
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function component_manager_get_parameter($section, $name, $default = null) {
    // Try base system getParameter first
    if (function_exists('getParameter')) {
        $value = getParameter($section, $name, null);
        if ($value !== null) {
            return $value;
        }
    }
    
    // Fall back to component's own parameters table
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = component_manager_get_table_name('parameters');
        $stmt = $conn->prepare("SELECT value FROM {$tableName} WHERE section = ? AND parameter_name = ?");
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
        error_log("Component Manager: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set parameter value in component_manager_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param string $value Parameter value
 * @param string $description Optional description
 * @return bool Success status
 */
function component_manager_set_parameter($section, $name, $value, $description = null) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = component_manager_get_table_name('parameters');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, description, value) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = COALESCE(VALUES(description), description)");
        $stmt->bind_param("ssss", $section, $name, $description, $value);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error setting parameter: " . $e->getMessage());
        return false;
    }
}

/**
 * Get config value from component_manager_config table
 * @param string $key Config key
 * @param mixed $default Default value if not found
 * @return mixed Config value or default
 */
function component_manager_get_config($key, $default = null) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = component_manager_get_table_name('config');
        $stmt = $conn->prepare("SELECT config_value FROM {$tableName} WHERE config_key = ?");
        if (!$stmt) {
            return $default;
        }
        
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['config_value'] : $default;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting config: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set config value in component_manager_config table
 * @param string $key Config key
 * @param string $value Config value
 * @return bool Success status
 */
function component_manager_set_config($key, $value) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = component_manager_get_table_name('config');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
        $stmt->bind_param("ss", $key, $value);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error setting config: " . $e->getMessage());
        return false;
    }
}

