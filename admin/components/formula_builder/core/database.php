<?php
/**
 * Formula Builder Component - Database Functions
 * All functions prefixed with formula_builder_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for formula builder component
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function formula_builder_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('FORMULA_BUILDER_DB_HOST') && !empty(FORMULA_BUILDER_DB_HOST)) {
                $conn = new mysqli(
                    FORMULA_BUILDER_DB_HOST,
                    FORMULA_BUILDER_DB_USER ?? '',
                    FORMULA_BUILDER_DB_PASS ?? '',
                    FORMULA_BUILDER_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("Formula Builder: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Formula Builder: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Formula Builder: Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Get table name with prefix
 * @param string $tableName Table name without prefix
 * @return string Full table name with prefix
 */
function formula_builder_get_table_name($tableName) {
    $prefix = defined('FORMULA_BUILDER_TABLE_PREFIX') ? FORMULA_BUILDER_TABLE_PREFIX : 'formula_builder_';
    return $prefix . $tableName;
}

/**
 * Check if component is installed
 * @return bool True if installed
 */
function formula_builder_is_installed() {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = formula_builder_get_table_name('config');
        $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        return $result && $result->num_rows > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get component version
 * @return string|null Version string or null
 */
function formula_builder_get_version() {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = formula_builder_get_table_name('config');
        $stmt = $conn->prepare("SELECT config_value FROM {$tableName} WHERE config_key = 'version' LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['config_value'] : null;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting version: " . $e->getMessage());
        return null;
    }
}

