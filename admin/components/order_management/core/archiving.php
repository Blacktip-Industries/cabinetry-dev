<?php
/**
 * Order Management Component - Archiving Functions
 * Order archiving management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Archive order
 * @param int $orderId Order ID
 * @param string $reason Archive reason
 * @return array Result
 */
function order_management_archive_order($orderId, $reason = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('archived_orders');
    
    // Check if already archived
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        return ['success' => false, 'error' => 'Order already archived'];
    }
    
    // Get order data
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found'];
    }
    
    // Archive order data
    $orderData = json_encode($order);
    $userId = $_SESSION['user_id'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, order_data, archive_reason, archived_by, archived_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("issi", $orderId, $orderData, $reason, $userId);
        if ($stmt->execute()) {
            $archiveId = $conn->insert_id;
            $stmt->close();
            
            // Optionally delete from main orders table (if configured)
            $deleteOnArchive = order_management_get_parameter('delete_orders_on_archive', false);
            if ($deleteOnArchive) {
                // Delete order (would need to handle foreign keys)
                // For now, just mark as archived
            }
            
            return ['success' => true, 'archive_id' => $archiveId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Restore archived order
 * @param int $archiveId Archive ID
 * @return array Result
 */
function order_management_restore_archived_order($archiveId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('archived_orders');
    
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $archiveId);
    $stmt->execute();
    $result = $stmt->get_result();
    $archive = $result->fetch_assoc();
    $stmt->close();
    
    if (!$archive) {
        return ['success' => false, 'error' => 'Archive not found'];
    }
    
    $orderData = json_decode($archive['order_data'], true);
    $orderId = $archive['order_id'];
    
    // Check if order still exists
    if (order_management_is_commerce_available()) {
        $stmt = $conn->prepare("SELECT id FROM commerce_orders WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            return ['success' => false, 'error' => 'Order already exists'];
        }
        
        // Restore order (would need to recreate in commerce_orders)
        // This is a placeholder - actual implementation would restore all order data
    }
    
    // Delete archive record
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
    $stmt->bind_param("i", $archiveId);
    $stmt->execute();
    $stmt->close();
    
    return ['success' => true];
}

/**
 * Get archived orders
 * @param array $filters Filters
 * @return array Array of archived orders
 */
function order_management_get_archived_orders($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('archived_orders');
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(archived_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(archived_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY archived_at DESC";
    
    $archives = [];
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['order_data'] = json_decode($row['order_data'], true);
            $archives[] = $row;
        }
        $stmt->close();
    } else {
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $row['order_data'] = json_decode($row['order_data'], true);
            $archives[] = $row;
        }
    }
    
    return $archives;
}

