<?php
/**
 * Access Component - Audit Logging
 * Comprehensive audit trail for all system actions
 */

require_once __DIR__ . '/database.php';

/**
 * Log audit event
 * @param string $entityType Entity type (user, account, role, permission, etc.)
 * @param int $entityId Entity ID
 * @param string $action Action (create, update, delete, login, logout, etc.)
 * @param array|null $oldValue Old value (for updates/deletes)
 * @param array|null $newValue New value (for creates/updates)
 * @param int|null $performedBy User ID who performed the action
 * @param array|null $metadata Additional metadata
 * @return bool Success
 */
function access_log_audit($entityType, $entityId, $action, $oldValue = null, $newValue = null, $performedBy = null, $metadata = null) {
    // Check if audit logging is enabled
    if (access_get_parameter('Audit', 'audit_log_enabled', 'yes') !== 'yes') {
        return true;
    }
    
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $oldValueJson = $oldValue ? json_encode($oldValue) : null;
        $newValueJson = $newValue ? json_encode($newValue) : null;
        $metadataJson = $metadata ? json_encode($metadata) : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO access_audit_log (entity_type, entity_id, action, old_value, new_value, performed_by, ip_address, user_agent, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssisss",
            $entityType,
            $entityId,
            $action,
            $oldValueJson,
            $newValueJson,
            $performedBy,
            $ipAddress,
            $userAgent,
            $metadataJson
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error logging audit: " . $e->getMessage());
        return false;
    }
}

/**
 * Get audit log entries
 * @param array $filters Filters (entity_type, entity_id, action, performed_by, date_from, date_to, limit, offset)
 * @return array Audit log entries
 */
function access_get_audit_log($filters = []) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['entity_type'])) {
        $where[] = "entity_type = ?";
        $params[] = $filters['entity_type'];
        $types .= 's';
    }
    
    if (!empty($filters['entity_id'])) {
        $where[] = "entity_id = ?";
        $params[] = (int)$filters['entity_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['action'])) {
        $where[] = "action = ?";
        $params[] = $filters['action'];
        $types .= 's';
    }
    
    if (!empty($filters['performed_by'])) {
        $where[] = "performed_by = ?";
        $params[] = (int)$filters['performed_by'];
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
    
    $sql = "SELECT * FROM access_audit_log";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY created_at DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = (int)$filters['limit'];
        $types .= 'i';
        
        if (!empty($filters['offset'])) {
            $sql .= " OFFSET ?";
            $params[] = (int)$filters['offset'];
            $types .= 'i';
        }
    }
    
    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $entries = [];
        while ($row = $result->fetch_assoc()) {
            $entries[] = $row;
        }
        $stmt->close();
        return $entries;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting audit log: " . $e->getMessage());
        return [];
    }
}

/**
 * Clean old audit log entries
 * @return int Number of entries deleted
 */
function access_clean_audit_log() {
    $retentionDays = (int)access_get_parameter('Audit', 'audit_log_retention_days', 365);
    $conn = access_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $cutoffDate = date('Y-m-d H:i:s', time() - ($retentionDays * 24 * 60 * 60));
        $stmt = $conn->prepare("DELETE FROM access_audit_log WHERE created_at < ?");
        $stmt->bind_param("s", $cutoffDate);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        return $deleted;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error cleaning audit log: " . $e->getMessage());
        return 0;
    }
}

