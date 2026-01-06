<?php
/**
 * Layout Component - Database Functions
 * Database functions with layout_ prefix
 */

/**
 * Get database connection
 * Tries to use base system connection first, then component's own connection
 * @return mysqli|null Database connection or null on failure
 */
function layout_get_db_connection() {
    // Try base system connection first
    if (function_exists('getDBConnection')) {
        $conn = getDBConnection();
        if ($conn !== null) {
            return $conn;
        }
    }
    
    // Fall back to component's own connection
    if (defined('LAYOUT_DB_HOST') && defined('LAYOUT_DB_USER') && defined('LAYOUT_DB_NAME')) {
        try {
            $conn = new mysqli(
                LAYOUT_DB_HOST,
                LAYOUT_DB_USER,
                defined('LAYOUT_DB_PASS') ? LAYOUT_DB_PASS : '',
                LAYOUT_DB_NAME,
                defined('LAYOUT_DB_PORT') ? LAYOUT_DB_PORT : 3306
            );
            
            if ($conn->connect_error) {
                error_log("Layout Component: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            $conn->set_charset("utf8mb4");
            return $conn;
        } catch (Exception $e) {
            error_log("Layout Component: Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return null;
}

/**
 * Get table name with prefix
 * Supports all design system tables
 * @param string $tableName Table name without prefix
 * @return string Full table name with prefix
 */
function layout_get_table_name($tableName) {
    $prefix = defined('LAYOUT_TABLE_PREFIX') ? LAYOUT_TABLE_PREFIX : 'layout_';
    
    // Map common table names for design system
    $tableMap = [
        'element_templates' => 'layout_element_templates',
        'design_systems' => 'layout_design_systems',
        'design_system_elements' => 'layout_design_system_elements',
        'element_template_versions' => 'layout_element_template_versions',
        'template_exports' => 'layout_template_exports',
        'component_templates' => 'layout_component_templates',
        'component_dependencies' => 'layout_component_dependencies',
        'ai_processing_queue' => 'layout_ai_processing_queue',
        'collaboration_sessions' => 'layout_collaboration_sessions',
        'collaboration_comments' => 'layout_collaboration_comments',
        'approval_workflows' => 'layout_approval_workflows',
        'permissions' => 'layout_permissions',
        'audit_logs' => 'layout_audit_logs',
        'analytics_events' => 'layout_analytics_events',
        'test_results' => 'layout_test_results',
        'collections' => 'layout_collections',
        'collection_items' => 'layout_collection_items',
        'starter_kits' => 'layout_starter_kits',
        'bulk_operations' => 'layout_bulk_operations',
        'search_index' => 'layout_search_index',
        'cache' => 'layout_cache',
        'performance_metrics' => 'layout_performance_metrics',
        'performance_budgets' => 'layout_performance_budgets',
        'marketplace_layouts' => 'layout_marketplace_layouts',
        'marketplace_reviews' => 'layout_marketplace_reviews',
        'animations' => 'layout_animations'
    ];
    
    if (isset($tableMap[$tableName])) {
        return $tableMap[$tableName];
    }
    
    return $prefix . $tableName;
}

/**
 * Get parameter value from layout_parameters table
 * Falls back to base system getParameter if available
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function layout_get_parameter($section, $name, $default = null) {
    // Try base system getParameter first
    if (function_exists('getParameter')) {
        $value = getParameter($section, $name, null);
        if ($value !== null) {
            return $value;
        }
    }
    
    // Fall back to component's own parameters table
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = layout_get_table_name('parameters');
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
        error_log("Layout Component: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Get config value from layout_config table
 * @param string $key Config key
 * @param mixed $default Default value if not found
 * @return mixed Config value or default
 */
function layout_get_config($key, $default = null) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = layout_get_table_name('config');
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
        error_log("Layout Component: Error getting config: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set config value in layout_config table
 * @param string $key Config key
 * @param string $value Config value
 * @return bool True on success, false on failure
 */
function layout_set_config($key, $value) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('config');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sss", $key, $value, $value);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component: Error setting config: " . $e->getMessage());
        return false;
    }
}


