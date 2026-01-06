<?php
/**
 * Theme Component - Database Functions
 * All functions prefixed with theme_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for theme component
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function theme_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('THEME_DB_HOST') && !empty(THEME_DB_HOST)) {
                $conn = new mysqli(
                    THEME_DB_HOST,
                    THEME_DB_USER ?? '',
                    THEME_DB_PASS ?? '',
                    THEME_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("Theme: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Theme: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Theme: Database connection error: " . $e->getMessage());
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
function theme_get_table_name($tableName) {
    $prefix = defined('THEME_TABLE_PREFIX') ? THEME_TABLE_PREFIX : 'theme_';
    return $prefix . $tableName;
}

/**
 * Get parameter value from theme_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function theme_get_parameter($section, $name, $default = null) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = theme_get_table_name('parameters');
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
        error_log("Theme: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set parameter value in theme_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param string $value Parameter value
 * @param string|null $description Parameter description
 * @param float|null $minRange Minimum range value
 * @param float|null $maxRange Maximum range value
 * @return bool Success
 */
function theme_set_parameter($section, $name, $value, $description = null, $minRange = null, $maxRange = null) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = theme_get_table_name('parameters');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, description, value, min_range, max_range) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description), min_range = VALUES(min_range), max_range = VALUES(max_range), updated_at = CURRENT_TIMESTAMP");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ssssdd", $section, $name, $description, $value, $minRange, $maxRange);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Theme: Error setting parameter: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all parameters for a section
 * @param string $section Parameter section
 * @return array Array of parameters
 */
function theme_get_section_parameters($section) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = theme_get_table_name('parameters');
        $stmt = $conn->prepare("SELECT parameter_name, description, value, min_range, max_range FROM {$tableName} WHERE section = ? ORDER BY parameter_name ASC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("s", $section);
        $stmt->execute();
        $result = $stmt->get_result();
        $parameters = [];
        
        while ($row = $result->fetch_assoc()) {
            $parameters[$row['parameter_name']] = $row;
        }
        
        $stmt->close();
        return $parameters;
    } catch (mysqli_sql_exception $e) {
        error_log("Theme: Error getting section parameters: " . $e->getMessage());
        return [];
    }
}

/**
 * Get active theme
 * @return array|null Theme data or null
 */
function theme_get_active_theme() {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = theme_get_table_name('themes');
        $stmt = $conn->prepare("SELECT id, theme_name, theme_type, config_json FROM {$tableName} WHERE is_active = 1 LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $theme = $result->fetch_assoc();
        $stmt->close();
        
        if ($theme) {
            $theme['config'] = json_decode($theme['config_json'], true);
        }
        
        return $theme ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Theme: Error getting active theme: " . $e->getMessage());
        return null;
    }
}

/**
 * Switch active theme
 * @param int $themeId Theme ID to activate
 * @return bool Success
 */
function theme_switch_theme($themeId) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = theme_get_table_name('themes');
        
        // Deactivate all themes
        $conn->query("UPDATE {$tableName} SET is_active = 0");
        
        // Activate selected theme
        $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = 1 WHERE id = ?");
        $stmt->bind_param("i", $themeId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Theme: Error switching theme: " . $e->getMessage());
        return false;
    }
}

