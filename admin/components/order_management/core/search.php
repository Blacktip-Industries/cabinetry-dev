<?php
/**
 * Order Management Component - Search Functions
 * Advanced search with full-text indexing
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Search orders
 * @param string $query Search query
 * @param array $filters Additional filters
 * @param int $limit Limit results
 * @param int $offset Offset
 * @return array Search results
 */
function order_management_search_orders($query, $filters = [], $limit = 50, $offset = 0) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    // Full-text search
    if (!empty($query)) {
        $where[] = "(order_number LIKE ? OR customer_email LIKE ? OR shipping_address LIKE ?)";
        $searchTerm = '%' . $query . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    // Additional filters
    if (!empty($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
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
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT * FROM commerce_orders {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $orders = [];
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM commerce_orders {$whereClause}";
    $total = 0;
    if (!empty($params)) {
        // Remove limit/offset params
        $countParams = array_slice($params, 0, -2);
        $countTypes = substr($types, 0, -2);
        if (!empty($countParams)) {
            $stmt = $conn->prepare($countSql);
            $stmt->bind_param($countTypes, ...$countParams);
            $stmt->execute();
            $result = $stmt->get_result();
            $total = $result->fetch_assoc()['total'] ?? 0;
            $stmt->close();
        } else {
            $result = $conn->query($countSql);
            $total = $result->fetch_assoc()['total'] ?? 0;
        }
    } else {
        $result = $conn->query($countSql);
        $total = $result->fetch_assoc()['total'] ?? 0;
    }
    
    return [
        'success' => true,
        'data' => $orders,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ];
}

/**
 * Save search
 * @param string $name Search name
 * @param string $query Search query
 * @param array $filters Filters
 * @param int $userId User ID
 * @return array Result
 */
function order_management_save_search($name, $query, $filters, $userId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('saved_searches');
    $filtersJson = json_encode($filters);
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (name, search_query, filters, user_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("sssi", $name, $query, $filtersJson, $userId);
        if ($stmt->execute()) {
            $searchId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'search_id' => $searchId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

