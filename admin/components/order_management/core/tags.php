<?php
/**
 * Order Management Component - Tags Functions
 * Order tags management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get tag by ID
 * @param int $tagId Tag ID
 * @return array|null Tag data
 */
function order_management_get_tag($tagId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('tags');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $tagId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tag = $result->fetch_assoc();
        $stmt->close();
        return $tag;
    }
    
    return null;
}

/**
 * Get all tags
 * @return array Array of tags
 */
function order_management_get_tags() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('tags');
    $result = $conn->query("SELECT * FROM {$tableName} ORDER BY name ASC");
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    
    return $tags;
}

/**
 * Create tag
 * @param string $name Tag name
 * @param string $color Tag color (hex)
 * @return array Result
 */
function order_management_create_tag($name, $color = '#007bff') {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('tags');
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (name, color, created_at) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ss", $name, $color);
        if ($stmt->execute()) {
            $tagId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'tag_id' => $tagId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get tags for order
 * @param int $orderId Order ID
 * @return array Array of tags
 */
function order_management_get_order_tags($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('order_tags');
    $tagsTable = order_management_get_table_name('tags');
    
    $query = "SELECT t.* FROM {$tagsTable} t
             INNER JOIN {$tableName} ot ON t.id = ot.tag_id
             WHERE ot.order_id = ?
             ORDER BY t.name ASC";
    
    $tags = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        $stmt->close();
    }
    
    return $tags;
}

/**
 * Add tag to order
 * @param int $orderId Order ID
 * @param int $tagId Tag ID
 * @return array Result
 */
function order_management_add_order_tag($orderId, $tagId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('order_tags');
    
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE order_id = ? AND tag_id = ? LIMIT 1");
    $stmt->bind_param("ii", $orderId, $tagId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        return ['success' => true, 'id' => $existing['id']];
    }
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, tag_id, created_at) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ii", $orderId, $tagId);
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
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Remove tag from order
 * @param int $orderId Order ID
 * @param int $tagId Tag ID
 * @return array Result
 */
function order_management_remove_order_tag($orderId, $tagId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('order_tags');
    
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE order_id = ? AND tag_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $orderId, $tagId);
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
 * Get orders by tag
 * @param int $tagId Tag ID
 * @return array Array of order IDs
 */
function order_management_get_orders_by_tag($tagId) {
    if (!order_management_is_commerce_available()) {
        return [];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('order_tags');
    
    $query = "SELECT order_id FROM {$tableName} WHERE tag_id = ?";
    $orderIds = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $tagId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orderIds[] = $row['order_id'];
        }
        $stmt->close();
    }
    
    return $orderIds;
}

