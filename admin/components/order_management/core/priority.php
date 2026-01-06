<?php
/**
 * Order Management Component - Priority Functions
 * Order priority management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get priority level
 * @param int $priorityId Priority ID
 * @return array|null Priority data
 */
function order_management_get_priority($priorityId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('priority_levels');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $priorityId);
        $stmt->execute();
        $result = $stmt->get_result();
        $priority = $result->fetch_assoc();
        $stmt->close();
        return $priority;
    }
    
    return null;
}

/**
 * Get all priority levels
 * @return array Array of priorities
 */
function order_management_get_priority_levels() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('priority_levels');
    $result = $conn->query("SELECT * FROM {$tableName} ORDER BY priority_value DESC");
    $priorities = [];
    while ($row = $result->fetch_assoc()) {
        $priorities[] = $row;
    }
    
    return $priorities;
}

/**
 * Set order priority
 * @param int $orderId Order ID
 * @param int $priorityId Priority ID
 * @return array Result
 */
function order_management_set_order_priority($orderId, $priorityId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('order_priorities');
    
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        // Update
        $stmt = $conn->prepare("UPDATE {$tableName} SET priority_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $priorityId, $existing['id']);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, priority_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $orderId, $priorityId);
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
}

/**
 * Get order priority
 * @param int $orderId Order ID
 * @return array|null Priority data
 */
function order_management_get_order_priority($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('order_priorities');
    $prioritiesTable = order_management_get_table_name('priority_levels');
    
    $query = "SELECT p.* FROM {$prioritiesTable} p
             INNER JOIN {$tableName} op ON p.id = op.priority_id
             WHERE op.order_id = ?
             LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $priority = $result->fetch_assoc();
        $stmt->close();
        return $priority;
    }
    
    return null;
}

