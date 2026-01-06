<?php
/**
 * Order Management Component - Database Functions
 * All database operations for the order_management component
 */

/**
 * Get database connection
 * @return mysqli|null Database connection or null on failure
 */
function order_management_get_db_connection() {
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
    $host = defined('ORDER_MANAGEMENT_DB_HOST') ? ORDER_MANAGEMENT_DB_HOST : (defined('DB_HOST') ? DB_HOST : 'localhost');
    $user = defined('ORDER_MANAGEMENT_DB_USER') ? ORDER_MANAGEMENT_DB_USER : (defined('DB_USER') ? DB_USER : 'root');
    $pass = defined('ORDER_MANAGEMENT_DB_PASS') ? ORDER_MANAGEMENT_DB_PASS : (defined('DB_PASS') ? DB_PASS : '');
    $name = defined('ORDER_MANAGEMENT_DB_NAME') ? ORDER_MANAGEMENT_DB_NAME : (defined('DB_NAME') ? DB_NAME : '');
    
    try {
        $conn = new mysqli($host, $user, $pass, $name);
        
        if ($conn->connect_error) {
            error_log("Order Management: Database connection failed: " . $conn->connect_error);
            return null;
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Order Management: Database connection error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get table name with prefix
 * @param string $tableName Table name without prefix
 * @return string Full table name with prefix
 */
function order_management_get_table_name($tableName) {
    $prefix = defined('ORDER_MANAGEMENT_TABLE_PREFIX') ? ORDER_MANAGEMENT_TABLE_PREFIX : 'order_management_';
    return $prefix . $tableName;
}

/**
 * Check if component is installed
 * @return bool True if installed
 */
function order_management_is_installed() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('config');
    $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
    return $result && $result->num_rows > 0;
}

/**
 * Get component version
 * @return string|null Version or null
 */
function order_management_get_version() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('config');
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
 * Check if commerce component is available
 * @return bool True if commerce component is installed
 */
function order_management_is_commerce_available() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $result = $conn->query("SHOW TABLES LIKE 'commerce_orders'");
    return $result && $result->num_rows > 0;
}

/**
 * Check if payment_processing component is available
 * @return bool True if payment_processing component is installed
 */
function order_management_is_payment_processing_available() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $result = $conn->query("SHOW TABLES LIKE 'payment_processing_transactions'");
    return $result && $result->num_rows > 0;
}

/**
 * Check if inventory component is available
 * @return bool True if inventory component is installed
 */
function order_management_is_inventory_available() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $result = $conn->query("SHOW TABLES LIKE 'inventory_items'");
    return $result && $result->num_rows > 0;
}

/**
 * Check if email_marketing component is available
 * @return bool True if email_marketing component is installed
 */
function order_management_is_email_marketing_available() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $result = $conn->query("SHOW TABLES LIKE 'email_marketing_templates'");
    return $result && $result->num_rows > 0;
}

