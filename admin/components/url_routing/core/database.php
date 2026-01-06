<?php
/**
 * URL Routing Component - Database Functions
 * All functions prefixed with url_routing_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for URL routing
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function url_routing_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('URL_ROUTING_DB_HOST') && !empty(URL_ROUTING_DB_HOST)) {
                $conn = new mysqli(
                    URL_ROUTING_DB_HOST,
                    URL_ROUTING_DB_USER ?? '',
                    URL_ROUTING_DB_PASS ?? '',
                    URL_ROUTING_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("URL Routing: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("URL Routing: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("URL Routing: Database connection error: " . $e->getMessage());
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
function url_routing_get_table_name($tableName) {
    $prefix = defined('URL_ROUTING_TABLE_PREFIX') ? URL_ROUTING_TABLE_PREFIX : 'url_routing_';
    return $prefix . $tableName;
}

/**
 * Get route from database by slug
 * @param string $slug Route slug
 * @return array|null Route data or null if not found
 */
function url_routing_get_route_from_db($slug) {
    $conn = url_routing_get_db_connection();
    if (!$conn) return null;
    
    try {
        $tableName = url_routing_get_table_name('routes');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE slug = ? AND active = 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $route = $result->fetch_assoc();
        $stmt->close();
        
        return $route;
    } catch (mysqli_sql_exception $e) {
        error_log("URL Routing: Error getting route: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all active routes from database
 * @return array Array of routes
 */
function url_routing_get_all_routes() {
    $conn = url_routing_get_db_connection();
    if (!$conn) return [];
    
    try {
        $tableName = url_routing_get_table_name('routes');
        $result = $conn->query("SELECT * FROM {$tableName} WHERE active = 1 ORDER BY slug ASC");
        
        $routes = [];
        while ($row = $result->fetch_assoc()) {
            $routes[] = $row;
        }
        
        return $routes;
    } catch (mysqli_sql_exception $e) {
        error_log("URL Routing: Error getting all routes: " . $e->getMessage());
        return [];
    }
}

/**
 * Get parameter value from url_routing_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function url_routing_get_parameter($section, $name, $default = null) {
    $conn = url_routing_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = url_routing_get_table_name('parameters');
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
        error_log("URL Routing: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

