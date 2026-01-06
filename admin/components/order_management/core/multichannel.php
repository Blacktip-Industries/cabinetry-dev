<?php
/**
 * Order Management Component - Multi-Channel Functions
 * Multi-channel order management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get channel by ID
 * @param int $channelId Channel ID
 * @return array|null Channel data
 */
function order_management_get_channel($channelId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('channels');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $channelId);
        $stmt->execute();
        $result = $stmt->get_result();
        $channel = $result->fetch_assoc();
        $stmt->close();
        return $channel;
    }
    
    return null;
}

/**
 * Get all channels
 * @param bool $activeOnly Only return active channels
 * @return array Array of channels
 */
function order_management_get_channels($activeOnly = false) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('channels');
    $query = "SELECT * FROM {$tableName}";
    if ($activeOnly) {
        $query .= " WHERE is_active = 1";
    }
    $query .= " ORDER BY name ASC";
    
    $result = $conn->query($query);
    $channels = [];
    while ($row = $result->fetch_assoc()) {
        $channels[] = $row;
    }
    
    return $channels;
}

/**
 * Create channel
 * @param array $data Channel data
 * @return array Result
 */
function order_management_create_channel($data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('channels');
    
    $name = $data['name'] ?? '';
    $channelType = $data['channel_type'] ?? 'web';
    $isActive = $data['is_active'] ?? 1;
    $config = isset($data['config']) ? json_encode($data['config']) : '{}';
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (name, channel_type, is_active, config, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ssis", $name, $channelType, $isActive, $config);
        if ($stmt->execute()) {
            $channelId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'channel_id' => $channelId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update channel
 * @param int $channelId Channel ID
 * @param array $data Channel data
 * @return array Result
 */
function order_management_update_channel($channelId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('channels');
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['name'])) {
        $updates[] = "name = ?";
        $params[] = $data['name'];
        $types .= 's';
    }
    
    if (isset($data['channel_type'])) {
        $updates[] = "channel_type = ?";
        $params[] = $data['channel_type'];
        $types .= 's';
    }
    
    if (isset($data['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = $data['is_active'];
        $types .= 'i';
    }
    
    if (isset($data['config'])) {
        $updates[] = "config = ?";
        $params[] = json_encode($data['config']);
        $types .= 's';
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $channelId;
    $types .= 'i';
    
    $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get orders by channel
 * @param int $channelId Channel ID
 * @param array $filters Additional filters
 * @return array Array of orders
 */
function order_management_get_orders_by_channel($channelId, $filters = []) {
    if (!order_management_is_commerce_available()) {
        return [];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('order_channels');
    
    $where = ["channel_id = ?"];
    $params = [$channelId];
    $types = 'i';
    
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
    
    $query = "SELECT o.* FROM commerce_orders o
             INNER JOIN {$tableName} oc ON o.id = oc.order_id
             {$whereClause}
             ORDER BY o.created_at DESC";
    
    $orders = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
    }
    
    return $orders;
}

/**
 * Assign order to channel
 * @param int $orderId Order ID
 * @param int $channelId Channel ID
 * @param string $externalOrderId External order ID (from channel)
 * @return array Result
 */
function order_management_assign_order_to_channel($orderId, $channelId, $externalOrderId = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('order_channels');
    
    // Check if already assigned
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE order_id = ? AND channel_id = ? LIMIT 1");
    $stmt->bind_param("ii", $orderId, $channelId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        // Update existing
        $stmt = $conn->prepare("UPDATE {$tableName} SET external_order_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $externalOrderId, $existing['id']);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    } else {
        // Create new
        $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, channel_id, external_order_id, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $orderId, $channelId, $externalOrderId);
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
 * Get channel statistics
 * @param int $channelId Channel ID
 * @param array $filters Filters
 * @return array Statistics
 */
function order_management_get_channel_statistics($channelId, $filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('order_channels');
    
    $where = ["channel_id = ?"];
    $params = [$channelId];
    $types = 'i';
    
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
    
    if (!order_management_is_commerce_available()) {
        return [];
    }
    
    $query = "SELECT 
        COUNT(DISTINCT oc.order_id) as total_orders,
        SUM(o.total_amount) as total_revenue,
        AVG(o.total_amount) as avg_order_value
    FROM {$tableName} oc
    INNER JOIN commerce_orders o ON oc.order_id = o.id
    {$whereClause}";
    
    $stats = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
    }
    
    return $stats;
}

