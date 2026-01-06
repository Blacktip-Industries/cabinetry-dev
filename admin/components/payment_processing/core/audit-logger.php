<?php
/**
 * Payment Processing Component - Audit Logger
 * Handles audit logging for compliance and security
 */

require_once __DIR__ . '/database.php';

/**
 * Log audit event
 * @param string $actionType Action type
 * @param string $entityType Entity type
 * @param int $entityId Entity ID
 * @param int $userId User ID (optional)
 * @param array $details Additional details
 * @param array $changes Changes made (optional)
 * @return bool Success
 */
function payment_processing_log_audit($actionType, $entityType, $entityId = null, $userId = null, $details = [], $changes = []) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = payment_processing_get_table_name('audit_log');
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $detailsJson = !empty($details) ? json_encode($details) : null;
        $changesJson = !empty($changes) ? json_encode($changes) : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (action_type, entity_type, entity_id, user_id, ip_address, user_agent, details, changes_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiissss", $actionType, $entityType, $entityId, $userId, $ipAddress, $userAgent, $detailsJson, $changesJson);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error logging audit: " . $e->getMessage());
        return false;
    }
}

/**
 * Get audit logs
 * @param array $filters Filters (action_type, entity_type, entity_id, user_id, date_from, date_to)
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Audit logs
 */
function payment_processing_get_audit_logs($filters = [], $limit = 100, $offset = 0) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = payment_processing_get_table_name('audit_log');
        
        $where = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['action_type'])) {
            $where[] = "action_type = ?";
            $params[] = $filters['action_type'];
            $types .= 's';
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = "entity_type = ?";
            $params[] = $filters['entity_type'];
            $types .= 's';
        }
        
        if (!empty($filters['entity_id'])) {
            $where[] = "entity_id = ?";
            $params[] = $filters['entity_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
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
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $conn->prepare($sql);
        if ($stmt && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        } elseif ($stmt) {
            $stmt->bind_param('ii', $limit, $offset);
        }
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        
        while ($row = $result->fetch_assoc()) {
            if ($row['details']) {
                $row['details'] = json_decode($row['details'], true);
            }
            if ($row['changes_json']) {
                $row['changes'] = json_decode($row['changes_json'], true);
            }
            $logs[] = $row;
        }
        
        $stmt->close();
        return $logs;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting audit logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Clean old audit logs
 * @param int $daysOld Days old to delete
 * @return int Number of records deleted
 */
function payment_processing_clean_audit_logs($daysOld = 365) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = payment_processing_get_table_name('audit_log');
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE created_at < ?");
        $stmt->bind_param("s", $cutoffDate);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        
        return $deleted;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error cleaning audit logs: " . $e->getMessage());
        return 0;
    }
}

