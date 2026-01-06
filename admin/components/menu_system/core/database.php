<?php
/**
 * Menu System Component - Database Functions
 * All functions prefixed with menu_system_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for menu system
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function menu_system_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('MENU_SYSTEM_DB_HOST') && !empty(MENU_SYSTEM_DB_HOST)) {
                $conn = new mysqli(
                    MENU_SYSTEM_DB_HOST,
                    MENU_SYSTEM_DB_USER ?? '',
                    MENU_SYSTEM_DB_PASS ?? '',
                    MENU_SYSTEM_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("Menu System: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Menu System: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Menu System: Database connection error: " . $e->getMessage());
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
function menu_system_get_table_name($tableName) {
    $prefix = defined('MENU_SYSTEM_TABLE_PREFIX') ? MENU_SYSTEM_TABLE_PREFIX : 'menu_system_';
    return $prefix . $tableName;
}

/**
 * Get parameter value from menu_system_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function menu_system_get_parameter($section, $name, $default = null) {
    $conn = menu_system_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = menu_system_get_table_name('parameters');
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
        error_log("Menu System: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Get all menus for a specific menu type
 * @param string $menuType 'admin' or 'frontend'
 * @return array Array of menu items
 */
function menu_system_get_menus($menuType = 'admin') {
    $conn = menu_system_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = menu_system_get_table_name('menus');
        $stmt = $conn->prepare("SELECT id, parent_id, title, icon, icon_svg_path, url, page_identifier, menu_order, is_active, menu_type, is_section_heading, is_pinned, section_heading_id FROM {$tableName} WHERE menu_type = ? AND is_active = 1 ORDER BY menu_order ASC, title ASC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("s", $menuType);
        $stmt->execute();
        $result = $stmt->get_result();
        $menus = [];
        
        while ($row = $result->fetch_assoc()) {
            $menus[] = $row;
        }
        
        $stmt->close();
        return $menus;
    } catch (mysqli_sql_exception $e) {
        error_log("Menu System: Error getting menus: " . $e->getMessage());
        return [];
    }
}

/**
 * Get icon by name from menu_system_icons table
 * @param string $name Icon name
 * @return array|null Icon data or null
 */
function menu_system_get_icon_by_name($name) {
    $conn = menu_system_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = menu_system_get_table_name('icons');
        $stmt = $conn->prepare("SELECT id, name, svg_path, description, category, style, fill, weight, grade, opsz, display_order FROM {$tableName} WHERE name = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $icon = $result->fetch_assoc();
        $stmt->close();
        
        return $icon ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Menu System: Error getting icon: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all icons from menu_system_icons table
 * @param string|null $sortOrder Sort order: 'name' or 'order'
 * @return array Array of icons
 */
function menu_system_get_all_icons($sortOrder = null) {
    $conn = menu_system_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = menu_system_get_table_name('icons');
        
        // Determine ORDER BY clause
        $orderBy = "category ASC, display_order ASC, name ASC";
        if ($sortOrder === "name") {
            $orderBy = "name ASC";
        } elseif ($sortOrder === "order") {
            $orderBy = "display_order ASC, name ASC";
        }
        
        $stmt = $conn->prepare("SELECT id, name, svg_path, description, category, style, fill, weight, grade, opsz, display_order FROM {$tableName} ORDER BY " . $orderBy);
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $icons = [];
        
        while ($row = $result->fetch_assoc()) {
            $icons[] = $row;
        }
        
        $stmt->close();
        return $icons;
    } catch (mysqli_sql_exception $e) {
        error_log("Menu System: Error getting icons: " . $e->getMessage());
        return [];
    }
}

/**
 * Migrate menu_system_menus table to ensure all columns exist
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function menu_system_migrate_menus_table($conn) {
    if ($conn === null) {
        return false;
    }
    
    $tableName = menu_system_get_table_name('menus');
    
    // Check and add columns if they don't exist
    $columnsToAdd = [
        'icon_svg_path' => "ALTER TABLE {$tableName} ADD COLUMN icon_svg_path TEXT NULL AFTER icon",
        'is_section_heading' => "ALTER TABLE {$tableName} ADD COLUMN is_section_heading TINYINT(1) DEFAULT 0 AFTER is_active",
        'section_heading_id' => "ALTER TABLE {$tableName} ADD COLUMN section_heading_id INT NULL AFTER parent_id",
        'is_pinned' => "ALTER TABLE {$tableName} ADD COLUMN is_pinned TINYINT(1) DEFAULT 0 AFTER is_section_heading"
    ];
    
    foreach ($columnsToAdd as $columnName => $sql) {
        $checkQuery = "SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'";
        $result = $conn->query($checkQuery);
        
        if ($result && $result->num_rows == 0) {
            if ($conn->query($sql) !== TRUE) {
                error_log("Menu System: Error adding {$columnName} column: " . $conn->error);
            } else {
                // Add foreign key for section_heading_id if needed
                if ($columnName === 'section_heading_id') {
                    $fkQuery = "ALTER TABLE {$tableName} ADD CONSTRAINT fk_{$tableName}_section_heading FOREIGN KEY (section_heading_id) REFERENCES {$tableName}(id) ON DELETE SET NULL";
                    @$conn->query($fkQuery);
                }
            }
        }
    }
    
    return true;
}

