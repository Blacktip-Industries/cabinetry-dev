<?php
/**
 * Savepoints Component - Database Functions
 * All functions prefixed with savepoints_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for savepoints
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function savepoints_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('SAVEPOINTS_DB_HOST') && !empty(SAVEPOINTS_DB_HOST)) {
                $conn = new mysqli(
                    SAVEPOINTS_DB_HOST,
                    SAVEPOINTS_DB_USER ?? '',
                    SAVEPOINTS_DB_PASS ?? '',
                    SAVEPOINTS_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("Savepoints: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Savepoints: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Savepoints: Database connection error: " . $e->getMessage());
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
function savepoints_get_table_name($tableName) {
    $prefix = defined('SAVEPOINTS_TABLE_PREFIX') ? SAVEPOINTS_TABLE_PREFIX : 'savepoints_';
    return $prefix . $tableName;
}

/**
 * Get parameter value from savepoints_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function savepoints_get_parameter($section, $name, $default = null) {
    $conn = savepoints_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = savepoints_get_table_name('parameters');
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
        error_log("Savepoints: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set parameter value in savepoints_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param string $value Parameter value
 * @param string|null $description Parameter description
 * @return bool Success
 */
function savepoints_set_parameter($section, $name, $value, $description = null) {
    $conn = savepoints_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = savepoints_get_table_name('parameters');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, value, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = ?, description = ?, updated_at = CURRENT_TIMESTAMP");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ssssss", $section, $name, $value, $description, $value, $description);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Savepoints: Error setting parameter: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all savepoints from history
 * @param int $limit Maximum number of savepoints to return (0 = all)
 * @param string $orderBy Order by field (default: 'timestamp')
 * @param string $orderDirection Order direction (ASC or DESC, default: DESC)
 * @return array Array of savepoint records
 */
function savepoints_get_history($limit = 0, $orderBy = 'timestamp', $orderDirection = 'DESC') {
    $conn = savepoints_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = savepoints_get_table_name('history');
        $validOrderBy = ['id', 'timestamp', 'created_at', 'commit_hash'];
        $orderBy = in_array($orderBy, $validOrderBy) ? $orderBy : 'timestamp';
        $orderDirection = strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT * FROM {$tableName} ORDER BY {$orderBy} {$orderDirection}";
        if ($limit > 0) {
            $query .= " LIMIT " . intval($limit);
        }
        
        $result = $conn->query($query);
        if (!$result) {
            return [];
        }
        
        $savepoints = [];
        while ($row = $result->fetch_assoc()) {
            $savepoints[] = $row;
        }
        
        return $savepoints;
    } catch (mysqli_sql_exception $e) {
        error_log("Savepoints: Error getting history: " . $e->getMessage());
        return [];
    }
}

/**
 * Get savepoint by ID
 * @param int $id Savepoint ID
 * @return array|null Savepoint record or null if not found
 */
function savepoints_get_by_id($id) {
    $conn = savepoints_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = savepoints_get_table_name('history');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $savepoint = $result->fetch_assoc();
        $stmt->close();
        
        return $savepoint ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Savepoints: Error getting savepoint by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get savepoint by commit hash
 * @param string $commitHash Git commit hash
 * @return array|null Savepoint record or null if not found
 */
function savepoints_get_by_commit_hash($commitHash) {
    $conn = savepoints_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = savepoints_get_table_name('history');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE commit_hash = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $commitHash);
        $stmt->execute();
        $result = $stmt->get_result();
        $savepoint = $result->fetch_assoc();
        $stmt->close();
        
        return $savepoint ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Savepoints: Error getting savepoint by commit hash: " . $e->getMessage());
        return null;
    }
}

/**
 * Create savepoint record in history
 * @param string $commitHash Git commit hash (can be null)
 * @param string $message Savepoint message
 * @param string $sqlFilePath Path to SQL backup file (relative to project root)
 * @param string $createdBy Creator identifier ('web', 'cli', etc.)
 * @param string|null $pushStatus Push status ('success', 'failed', 'skipped')
 * @param string|null $filesystemStatus Filesystem backup status
 * @param string|null $databaseStatus Database backup status
 * @return int|false Savepoint ID on success, false on failure
 */
function savepoints_create_history_record($commitHash, $message, $sqlFilePath, $createdBy = 'web', $pushStatus = null, $filesystemStatus = null, $databaseStatus = null) {
    $conn = savepoints_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = savepoints_get_table_name('history');
        
        // Get current timestamp
        if (function_exists('formatSystemDateTime')) {
            $timestamp = formatSystemDateTime();
        } else {
            $timestamp = date('Y-m-d H:i:s');
        }
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (commit_hash, message, timestamp, sql_file_path, created_by, push_status, filesystem_backup_status, database_backup_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ssssssss", 
            $commitHash,
            $message,
            $timestamp,
            $sqlFilePath,
            $createdBy,
            $pushStatus,
            $filesystemStatus,
            $databaseStatus
        );
        
        $success = $stmt->execute();
        $savepointId = $conn->insert_id;
        $stmt->close();
        
        return $success ? $savepointId : false;
    } catch (mysqli_sql_exception $e) {
        error_log("Savepoints: Error creating history record: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete savepoint record from history
 * @param int $id Savepoint ID
 * @return bool Success
 */
function savepoints_delete_history_record($id) {
    $conn = savepoints_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = savepoints_get_table_name('history');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Savepoints: Error deleting history record: " . $e->getMessage());
        return false;
    }
}

