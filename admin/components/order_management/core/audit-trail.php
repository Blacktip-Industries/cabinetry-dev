<?php
/**
 * Order Management Component - Audit Trail Functions
 * Complete audit trail and history tracking
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Record audit log entry
 * @param int $orderId Order ID
 * @param string $action Action performed
 * @param string $entityType Entity type (order, fulfillment, return, etc.)
 * @param int $entityId Entity ID
 * @param array $changes Changes made
 * @param int $userId User ID
 * @param string $ipAddress IP address
 * @return array Result
 */
function order_management_record_audit_log($orderId, $action, $entityType, $entityId, $changes = [], $userId = null, $ipAddress = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('audit_logs');
    
    $userId = $userId ?? ($_SESSION['user_id'] ?? null);
    $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? null);
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $changesJson = !empty($changes) ? json_encode($changes) : null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, action, entity_type, entity_id, changes, user_id, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("issisiss", $orderId, $action, $entityType, $entityId, $changesJson, $userId, $ipAddress, $userAgent);
        if ($stmt->execute()) {
            $logId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'log_id' => $logId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get audit log for order
 * @param int $orderId Order ID
 * @param array $filters Filters
 * @return array Array of audit log entries
 */
function order_management_get_order_audit_log($orderId, $filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('audit_logs');
    
    $where = ["order_id = ?"];
    $params = [$orderId];
    $types = 'i';
    
    if (!empty($filters['action'])) {
        $where[] = "action = ?";
        $params[] = $filters['action'];
        $types .= 's';
    }
    
    if (!empty($filters['entity_type'])) {
        $where[] = "entity_type = ?";
        $params[] = $filters['entity_type'];
        $types .= 's';
    }
    
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC";
    
    $logs = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['changes'] = json_decode($row['changes'], true);
            $logs[] = $row;
        }
        $stmt->close();
    }
    
    return $logs;
}

/**
 * Get audit log for entity
 * @param string $entityType Entity type
 * @param int $entityId Entity ID
 * @return array Array of audit log entries
 */
function order_management_get_entity_audit_log($entityType, $entityId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('audit_logs');
    
    $query = "SELECT * FROM {$tableName} WHERE entity_type = ? AND entity_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("si", $entityType, $entityId);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $row['changes'] = json_decode($row['changes'], true);
            $logs[] = $row;
        }
        $stmt->close();
        return $logs;
    }
    
    return [];
}

/**
 * Get system activity log
 * @param array $filters Filters
 * @param int $limit Limit results
 * @return array Array of activity log entries
 */
function order_management_get_activity_log($filters = [], $limit = 100) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('audit_logs');
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['action'])) {
        $where[] = "action = ?";
        $params[] = $filters['action'];
        $types .= 's';
    }
    
    if (!empty($filters['user_id'])) {
        $where[] = "user_id = ?";
        $params[] = $filters['user_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT ?";
    
    $logs = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        if (!empty($params)) {
            $types .= 'i';
            $params[] = $limit;
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param("i", $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['changes'] = json_decode($row['changes'], true);
            $logs[] = $row;
        }
        $stmt->close();
    }
    
    return $logs;
}

