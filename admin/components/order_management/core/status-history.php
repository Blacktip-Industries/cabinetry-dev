<?php
/**
 * Order Management Component - Status History Functions
 * Enhanced status history tracking
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get status history for order
 * @param int $orderId Order ID
 * @param array $filters Filters (limit, offset)
 * @return array Array of status history records
 */
function order_management_get_order_status_history($orderId, $filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('status_history');
    $limit = $filters['limit'] ?? 100;
    $offset = $filters['offset'] ?? 0;
    
    $query = "SELECT sh.*, 
                     w.workflow_name,
                     ws.status_name as step_status_name
              FROM {$tableName} sh
              LEFT JOIN " . order_management_get_table_name('workflows') . " w ON sh.workflow_id = w.id
              LEFT JOIN " . order_management_get_table_name('workflow_steps') . " ws ON sh.workflow_step_id = ws.id
              WHERE sh.order_id = ?
              ORDER BY sh.created_at DESC
              LIMIT ? OFFSET ?";
    
    $history = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("iii", $orderId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
    }
    
    return $history;
}

/**
 * Get status history by ID
 * @param int $historyId History ID
 * @return array|null History record
 */
function order_management_get_status_history($historyId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('status_history');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $historyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = $result->fetch_assoc();
        $stmt->close();
        return $history;
    }
    
    return null;
}

/**
 * Get status change statistics for order
 * @param int $orderId Order ID
 * @return array Statistics
 */
function order_management_get_order_status_stats($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('status_history');
    
    $stats = [
        'total_changes' => 0,
        'manual_changes' => 0,
        'automated_changes' => 0,
        'workflow_changes' => 0,
        'statuses' => [],
        'time_in_status' => []
    ];
    
    // Get total changes
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$tableName} WHERE order_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_changes'] = $row['count'] ?? 0;
        $stmt->close();
    }
    
    // Get changes by type
    $stmt = $conn->prepare("SELECT change_type, COUNT(*) as count FROM {$tableName} WHERE order_id = ? GROUP BY change_type");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            switch ($row['change_type']) {
                case 'manual':
                    $stats['manual_changes'] = $row['count'];
                    break;
                case 'automated':
                    $stats['automated_changes'] = $row['count'];
                    break;
                case 'workflow':
                    $stats['workflow_changes'] = $row['count'];
                    break;
            }
        }
        $stmt->close();
    }
    
    // Get unique statuses
    $stmt = $conn->prepare("SELECT DISTINCT new_status FROM {$tableName} WHERE order_id = ? ORDER BY created_at ASC");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $stats['statuses'][] = $row['new_status'];
        }
        $stmt->close();
    }
    
    // Calculate time in each status
    $history = order_management_get_order_status_history($orderId, ['limit' => 1000]);
    $previousTime = null;
    foreach (array_reverse($history) as $entry) {
        if ($previousTime) {
            $timeDiff = strtotime($previousTime) - strtotime($entry['created_at']);
            $status = $entry['new_status'];
            if (!isset($stats['time_in_status'][$status])) {
                $stats['time_in_status'][$status] = 0;
            }
            $stats['time_in_status'][$status] += $timeDiff;
        }
        $previousTime = $entry['created_at'];
    }
    
    return $stats;
}

/**
 * Get recent status changes across all orders
 * @param int $limit Limit
 * @param array $filters Filters (user_id, change_type, status)
 * @return array Array of recent changes
 */
function order_management_get_recent_status_changes($limit = 50, $filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('status_history');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['user_id'])) {
        $where[] = "changed_by = ?";
        $params[] = $filters['user_id'];
        $types .= 'i';
    }
    
    if (isset($filters['change_type'])) {
        $where[] = "change_type = ?";
        $params[] = $filters['change_type'];
        $types .= 's';
    }
    
    if (isset($filters['status'])) {
        $where[] = "new_status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $params[] = $limit;
    $types .= 'i';
    
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT ?";
    
    $changes = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $changes[] = $row;
        }
        $stmt->close();
    }
    
    return $changes;
}

