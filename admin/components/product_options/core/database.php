<?php
/**
 * Product Options Component - Database Functions
 * All functions prefixed with product_options_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for product options component
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function product_options_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('PRODUCT_OPTIONS_DB_HOST') && !empty(PRODUCT_OPTIONS_DB_HOST)) {
                $conn = new mysqli(
                    PRODUCT_OPTIONS_DB_HOST,
                    PRODUCT_OPTIONS_DB_USER ?? '',
                    PRODUCT_OPTIONS_DB_PASS ?? '',
                    PRODUCT_OPTIONS_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("Product Options: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Product Options: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Product Options: Database connection error: " . $e->getMessage());
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
function product_options_get_table_name($tableName) {
    $prefix = defined('PRODUCT_OPTIONS_TABLE_PREFIX') ? PRODUCT_OPTIONS_TABLE_PREFIX : 'product_options_';
    return $prefix . $tableName;
}

/**
 * Get option by ID
 * @param int $optionId Option ID
 * @return array|null Option data or null
 */
function product_options_get_option($optionId) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = product_options_get_table_name('options');
        $stmt = $conn->prepare("SELECT o.*, dt.datatype_key, dt.datatype_name, g.name as group_name, g.slug as group_slug 
                                FROM {$tableName} o
                                LEFT JOIN " . product_options_get_table_name('datatypes') . " dt ON o.datatype_id = dt.id
                                LEFT JOIN " . product_options_get_table_name('groups') . " g ON o.group_id = g.id
                                WHERE o.id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $optionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $option = $result->fetch_assoc();
        $stmt->close();
        
        if ($option) {
            $option['config'] = json_decode($option['config'], true);
        }
        
        return $option ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting option: " . $e->getMessage());
        return null;
    }
}

/**
 * Get option by slug
 * @param string $slug Option slug
 * @return array|null Option data or null
 */
function product_options_get_option_by_slug($slug) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = product_options_get_table_name('options');
        $stmt = $conn->prepare("SELECT o.*, dt.datatype_key, dt.datatype_name, g.name as group_name, g.slug as group_slug 
                                FROM {$tableName} o
                                LEFT JOIN " . product_options_get_table_name('datatypes') . " dt ON o.datatype_id = dt.id
                                LEFT JOIN " . product_options_get_table_name('groups') . " g ON o.group_id = g.id
                                WHERE o.slug = ? AND o.is_active = 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $option = $result->fetch_assoc();
        $stmt->close();
        
        if ($option) {
            $option['config'] = json_decode($option['config'], true);
        }
        
        return $option ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting option by slug: " . $e->getMessage());
        return null;
    }
}

/**
 * Get options by group
 * @param int|null $groupId Group ID (null for all)
 * @param bool $activeOnly Only get active options
 * @return array Array of options
 */
function product_options_get_options_by_group($groupId = null, $activeOnly = true) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = product_options_get_table_name('options');
        $where = [];
        $params = [];
        $types = '';
        
        if ($groupId !== null) {
            $where[] = "o.group_id = ?";
            $params[] = $groupId;
            $types .= 'i';
        }
        
        if ($activeOnly) {
            $where[] = "o.is_active = 1";
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT o.*, dt.datatype_key, dt.datatype_name, g.name as group_name, g.slug as group_slug 
                  FROM {$tableName} o
                  LEFT JOIN " . product_options_get_table_name('datatypes') . " dt ON o.datatype_id = dt.id
                  LEFT JOIN " . product_options_get_table_name('groups') . " g ON o.group_id = g.id
                  {$whereClause}
                  ORDER BY o.display_order ASC, o.name ASC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $options = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['config'] = json_decode($row['config'], true);
            $options[] = $row;
        }
        
        $stmt->close();
        return $options;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting options by group: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all options
 * @param bool $activeOnly Only get active options
 * @return array Array of options
 */
function product_options_get_all_options($activeOnly = true) {
    return product_options_get_options_by_group(null, $activeOnly);
}

/**
 * Save option (create or update)
 * @param array $optionData Option data
 * @return array Result with success status and option ID
 */
function product_options_save_option($optionData) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = product_options_get_table_name('options');
        
        // Prepare config JSON
        $config = isset($optionData['config']) ? (is_array($optionData['config']) ? json_encode($optionData['config']) : $optionData['config']) : '{}';
        
        if (isset($optionData['id']) && !empty($optionData['id'])) {
            // Update existing option
            $stmt = $conn->prepare("UPDATE {$tableName} SET 
                                    name = ?, label = ?, slug = ?, description = ?, 
                                    datatype_id = ?, group_id = ?, config = ?, 
                                    is_required = ?, is_active = ?, display_order = ?, 
                                    pricing_enabled = ?
                                    WHERE id = ?");
            
            $stmt->bind_param("ssssiisiiii",
                $optionData['name'],
                $optionData['label'],
                $optionData['slug'],
                $optionData['description'] ?? null,
                $optionData['datatype_id'],
                $optionData['group_id'] ?? null,
                $config,
                $optionData['is_required'] ?? 0,
                $optionData['is_active'] ?? 1,
                $optionData['display_order'] ?? 0,
                $optionData['pricing_enabled'] ?? 0,
                $optionData['id']
            );
            
            $stmt->execute();
            $stmt->close();
            
            return ['success' => true, 'id' => $optionData['id']];
        } else {
            // Create new option
            $stmt = $conn->prepare("INSERT INTO {$tableName} 
                                    (name, label, slug, description, datatype_id, group_id, config, 
                                     is_required, is_active, display_order, pricing_enabled) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssiisiiii",
                $optionData['name'],
                $optionData['label'],
                $optionData['slug'],
                $optionData['description'] ?? null,
                $optionData['datatype_id'],
                $optionData['group_id'] ?? null,
                $config,
                $optionData['is_required'] ?? 0,
                $optionData['is_active'] ?? 1,
                $optionData['display_order'] ?? 0,
                $optionData['pricing_enabled'] ?? 0
            );
            
            $stmt->execute();
            $optionId = $conn->insert_id;
            $stmt->close();
            
            return ['success' => true, 'id' => $optionId];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error saving option: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete option
 * @param int $optionId Option ID
 * @return array Result with success status
 */
function product_options_delete_option($optionId) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = product_options_get_table_name('options');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $optionId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error deleting option: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get parameter value from product_options_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function product_options_get_parameter($section, $name, $default = null) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = product_options_get_table_name('parameters');
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
        error_log("Product Options: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Get all groups
 * @param bool $activeOnly Only get active groups
 * @return array Array of groups
 */
function product_options_get_all_groups($activeOnly = true) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = product_options_get_table_name('groups');
        $where = $activeOnly ? "WHERE is_active = 1" : "";
        $query = "SELECT * FROM {$tableName} {$where} ORDER BY display_order ASC, name ASC";
        
        $result = $conn->query($query);
        $groups = [];
        
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
        
        return $groups;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting groups: " . $e->getMessage());
        return [];
    }
}

/**
 * Get option values for an option
 * @param int $optionId Option ID
 * @param bool $activeOnly Only get active values
 * @return array Array of option values
 */
function product_options_get_option_values($optionId, $activeOnly = true) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = product_options_get_table_name('values');
        $where = "WHERE option_id = ?";
        $params = [$optionId];
        $types = 'i';
        
        if ($activeOnly) {
            $where .= " AND is_active = 1";
        }
        
        $query = "SELECT * FROM {$tableName} {$where} ORDER BY display_order ASC, value_label ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $values = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['value_data']) {
                $row['value_data'] = json_decode($row['value_data'], true);
            }
            $values[] = $row;
        }
        
        $stmt->close();
        return $values;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting option values: " . $e->getMessage());
        return [];
    }
}

