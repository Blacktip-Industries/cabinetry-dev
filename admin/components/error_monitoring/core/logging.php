<?php
/**
 * Error Monitoring Component - Error Logging Functions
 * Handles error logging, retrieval, and resolution
 */

// Load required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Load dependent modules (with fallback if not yet created)
if (file_exists(__DIR__ . '/queue.php')) {
    require_once __DIR__ . '/queue.php';
}
if (file_exists(__DIR__ . '/hooks.php')) {
    require_once __DIR__ . '/hooks.php';
}
if (file_exists(__DIR__ . '/notifications.php')) {
    require_once __DIR__ . '/notifications.php';
}

/**
 * Log error to database
 * @param string $level Error level (critical/high/medium/low)
 * @param string $message Error message
 * @param array $context Error context (error_type, file, line, function, stack_trace, context)
 * @return int|false Error ID or false on failure
 */
function error_monitoring_log_error($level, $message, $context = []) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        // Fallback to file logging
        return error_monitoring_log_to_file($message, $context);
    }
    
    try {
        $tableName = error_monitoring_get_table_name('logs');
        
        // Extract context data
        $errorType = $context['error_type'] ?? 'php_error';
        $file = $context['file'] ?? null;
        $line = $context['line'] ?? null;
        $function = $context['function'] ?? null;
        $stackTrace = $context['stack_trace'] ?? null;
        $errorContext = $context['context'] ?? [];
        $componentName = error_monitoring_detect_component_from_file($file);
        
        // Get current user info
        $userId = error_monitoring_get_current_user_id();
        $ipAddress = error_monitoring_get_current_ip();
        $userAgent = error_monitoring_get_current_user_agent();
        $environment = error_monitoring_get_current_environment();
        
        // Get memory and execution time
        $memoryUsage = memory_get_usage(true);
        $executionTime = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        
        // Sanitize context
        $errorContext = error_monitoring_sanitize_context($errorContext);
        
        // Prepare JSON fields
        $errorContextJson = json_encode($errorContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $performanceData = json_encode([
            'memory_usage' => $memoryUsage,
            'execution_time' => $executionTime,
        ], JSON_UNESCAPED_UNICODE);
        
        // Insert error
        $stmt = $conn->prepare("
            INSERT INTO {$tableName} (
                error_level, error_type, error_message, stack_trace, file, line, function,
                component_name, error_context, user_id, ip_address, user_agent,
                memory_usage, execution_time, environment, performance_data,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            error_log("Error Monitoring: Failed to prepare statement: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param(
            "sssssisssisssdss",
            $level,
            $errorType,
            $message,
            $stackTrace,
            $file,
            $line,
            $function,
            $componentName,
            $errorContextJson,
            $userId,
            $ipAddress,
            $userAgent,
            $memoryUsage,
            $executionTime,
            $environment,
            $performanceData
        );
        
        $result = $stmt->execute();
        $errorId = $result ? $conn->insert_id : false;
        $stmt->close();
        
        if ($errorId) {
            // Trigger hooks
            error_monitoring_trigger_hook('error_logged', ['error_id' => $errorId, 'error' => compact('level', 'message', 'context')]);
            
            // Queue for grouping (async)
            error_monitoring_queue_error(['error_id' => $errorId, 'action' => 'group']);
            
            // Check notifications
            error_monitoring_check_notification_rules(['error_id' => $errorId, 'level' => $level]);
        }
        
        return $errorId;
        
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to log error: " . $e->getMessage());
        // Fallback to file logging
        error_monitoring_log_to_file($message, $context);
        return false;
    }
}

/**
 * Get errors with filtering
 * @param array $filters Filter options (level, component, date_from, date_to, resolved, search, limit, offset)
 * @return array Array of errors
 */
function error_monitoring_get_errors($filters = []) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = error_monitoring_get_table_name('logs');
        
        $where = [];
        $params = [];
        $types = '';
        
        // Apply filters
        if (!empty($filters['level'])) {
            $where[] = "error_level = ?";
            $params[] = $filters['level'];
            $types .= 's';
        }
        
        if (!empty($filters['component'])) {
            $where[] = "component_name = ?";
            $params[] = $filters['component'];
            $types .= 's';
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        if (isset($filters['resolved'])) {
            $where[] = "is_resolved = ?";
            $params[] = $filters['resolved'] ? 1 : 0;
            $types .= 'i';
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(error_message LIKE ? OR file LIKE ? OR component_name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        
        $sql = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $errors = [];
        
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields
            if (!empty($row['error_context'])) {
                $row['error_context'] = json_decode($row['error_context'], true);
            }
            if (!empty($row['performance_data'])) {
                $row['performance_data'] = json_decode($row['performance_data'], true);
            }
            if (!empty($row['tags'])) {
                $row['tags'] = json_decode($row['tags'], true);
            }
            $errors[] = $row;
        }
        
        $stmt->close();
        return $errors;
        
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to get errors: " . $e->getMessage());
        return [];
    }
}

/**
 * Get error details by ID
 * @param int $errorId Error ID
 * @return array|null Error data or null
 */
function error_monitoring_get_error_details($errorId) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('logs');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $errorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $error = $result->fetch_assoc();
        $stmt->close();
        
        if ($error) {
            // Decode JSON fields
            if (!empty($error['error_context'])) {
                $error['error_context'] = json_decode($error['error_context'], true);
            }
            if (!empty($error['performance_data'])) {
                $error['performance_data'] = json_decode($error['performance_data'], true);
            }
            if (!empty($error['tags'])) {
                $error['tags'] = json_decode($error['tags'], true);
            }
        }
        
        return $error ?: null;
        
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to get error details: " . $e->getMessage());
        return null;
    }
}

/**
 * Mark error as resolved
 * @param int $errorId Error ID
 * @param int $userId User ID who resolved it
 * @return bool Success
 */
function error_monitoring_mark_resolved($errorId, $userId) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('logs');
        $stmt = $conn->prepare("UPDATE {$tableName} SET is_resolved = 1, resolved_at = NOW(), resolved_by = ? WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ii", $userId, $errorId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Log history
            error_monitoring_log_history($errorId, 'resolved', $userId, ['resolved_by' => $userId]);
            
            // Trigger hooks
            error_monitoring_trigger_hook('error_resolved', ['error_id' => $errorId, 'user_id' => $userId]);
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to mark error as resolved: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread error count for admin user
 * @param int $userId User ID
 * @return int Unread count
 */
function error_monitoring_get_unread_count($userId) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('logs');
        
        // Get count of unresolved critical/high errors from last 24 hours
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM {$tableName} 
            WHERE is_resolved = 0 
            AND error_level IN ('critical', 'high')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        if (!$stmt) {
            return 0;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['count'] ?? 0);
        
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to get unread count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Detect component name from file path
 * @param string|null $file File path
 * @return string|null Component name or null
 */
function error_monitoring_detect_component_from_file($file) {
    if (empty($file)) {
        return null;
    }
    
    // Check if file is in a component directory
    if (preg_match('#/admin/components/([^/]+)/#', $file, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Queue error for async processing
 * @param array $errorData Error data
 * @return bool Success
 */
function error_monitoring_queue_error($errorData) {
    // This will be implemented in queue.php
    // For now, just return true
    return true;
}

/**
 * Check notification rules
 * @param array $errorData Error data
 * @return void
 */
function error_monitoring_check_notification_rules($errorData) {
    // This will be implemented in notifications.php
    // For now, do nothing
}

/**
 * Trigger hook
 * @param string $hookName Hook name
 * @param array $data Hook data
 * @return void
 */
function error_monitoring_trigger_hook($hookName, $data = []) {
    // This will be implemented in hooks.php
    // For now, do nothing
}

/**
 * Log history entry
 * @param int $errorId Error ID
 * @param string $action Action performed
 * @param int|null $userId User ID
 * @param array $details Additional details
 * @return bool Success
 */
function error_monitoring_log_history($errorId, $action, $userId = null, $details = []) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('history');
        $oldValue = null;
        $newValue = null;
        $detailsText = !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $conn->prepare("
            INSERT INTO {$tableName} (error_id, action, user_id, old_value, new_value, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $oldValueJson = $oldValue ? json_encode($oldValue) : null;
        $newValueJson = $newValue ? json_encode($newValue) : null;
        
        $stmt->bind_param("isisss", $errorId, $action, $userId, $oldValueJson, $newValueJson, $detailsText);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to log history: " . $e->getMessage());
        return false;
    }
}

