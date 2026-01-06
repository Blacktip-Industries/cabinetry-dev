<?php
/**
 * Order Management Component - Picking List Functions
 * Picking list generation and management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/fulfillment.php';
require_once __DIR__ . '/functions.php';

/**
 * Get picking list by ID
 * @param int $pickingListId Picking list ID
 * @return array|null Picking list data
 */
function order_management_get_picking_list($pickingListId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('picking_lists');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $pickingListId);
        $stmt->execute();
        $result = $stmt->get_result();
        $list = $result->fetch_assoc();
        $stmt->close();
        return $list;
    }
    
    return null;
}

/**
 * Get picking lists
 * @param array $filters Filters (warehouse_id, status, date)
 * @return array Array of picking lists
 */
function order_management_get_picking_lists($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('picking_lists');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['warehouse_id'])) {
        $where[] = "warehouse_id = ?";
        $params[] = $filters['warehouse_id'];
        $types .= 'i';
    }
    
    if (isset($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (isset($filters['date'])) {
        $where[] = "picking_date = ?";
        $params[] = $filters['date'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY picking_date DESC, created_at DESC";
    
    $lists = [];
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $lists[] = $row;
            }
            $stmt->close();
        }
    } else {
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $lists[] = $row;
            }
        }
    }
    
    return $lists;
}

/**
 * Create picking list
 * @param array $data Picking list data
 * @return array Result with picking list ID
 */
function order_management_create_picking_list($data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('picking_lists');
    
    $warehouseId = $data['warehouse_id'] ?? null;
    $pickingDate = $data['picking_date'] ?? date('Y-m-d');
    $assignedTo = $data['assigned_to'] ?? null;
    $status = $data['status'] ?? 'pending';
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (warehouse_id, picking_date, status, assigned_to) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issi", $warehouseId, $pickingDate, $status, $assignedTo);
        if ($stmt->execute()) {
            $pickingListId = $conn->insert_id;
            $stmt->close();
            
            // Add items if provided
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    order_management_add_picking_item($pickingListId, $item);
                }
            }
            
            return ['success' => true, 'picking_list_id' => $pickingListId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Generate picking list from pending fulfillments
 * @param int $warehouseId Warehouse ID
 * @param string $pickingDate Picking date
 * @param array $options Options (max_orders, optimize_route)
 * @return array Result with picking list ID
 */
function order_management_generate_picking_list_from_fulfillments($warehouseId, $pickingDate = null, $options = []) {
    if ($pickingDate === null) {
        $pickingDate = date('Y-m-d');
    }
    
    // Get pending fulfillments for warehouse
    $fulfillments = order_management_get_fulfillments_by_status('pending', ['warehouse_id' => $warehouseId]);
    
    if (empty($fulfillments)) {
        return ['success' => false, 'error' => 'No pending fulfillments found'];
    }
    
    // Limit orders if specified
    $maxOrders = $options['max_orders'] ?? null;
    if ($maxOrders) {
        $fulfillments = array_slice($fulfillments, 0, $maxOrders);
    }
    
    // Create picking list
    $pickingListData = [
        'warehouse_id' => $warehouseId,
        'picking_date' => $pickingDate,
        'status' => 'pending',
        'items' => []
    ];
    
    $sequenceOrder = 0;
    foreach ($fulfillments as $fulfillment) {
        $items = order_management_get_fulfillment_items($fulfillment['id']);
        
        foreach ($items as $item) {
            // Get product location (would integrate with inventory component)
            $location = $item['location_picked_from'] ?? 'A1-1';
            
            $pickingListData['items'][] = [
                'order_id' => $fulfillment['order_id'],
                'order_item_id' => $item['order_item_id'],
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'location' => $location,
                'quantity' => $item['quantity_fulfilled'],
                'sequence_order' => $sequenceOrder++
            ];
        }
    }
    
    // Optimize route if requested
    if (!empty($options['optimize_route'])) {
        $pickingListData['items'] = order_management_optimize_picking_route($pickingListData['items']);
    }
    
    return order_management_create_picking_list($pickingListData);
}

/**
 * Optimize picking route
 * @param array $items Picking items
 * @return array Optimized items
 */
function order_management_optimize_picking_route($items) {
    // Simple optimization: sort by location
    // In a real system, this would use more sophisticated routing algorithms
    usort($items, function($a, $b) {
        return strcmp($a['location'] ?? '', $b['location'] ?? '');
    });
    
    // Re-sequence
    $sequence = 0;
    foreach ($items as &$item) {
        $item['sequence_order'] = $sequence++;
    }
    
    return $items;
}

/**
 * Get picking list items
 * @param int $pickingListId Picking list ID
 * @return array Array of picking items
 */
function order_management_get_picking_list_items($pickingListId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('picking_items');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE picking_list_id = ? ORDER BY sequence_order ASC");
    if ($stmt) {
        $stmt->bind_param("i", $pickingListId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }
    
    return [];
}

/**
 * Add item to picking list
 * @param int $pickingListId Picking list ID
 * @param array $itemData Item data
 * @return array Result
 */
function order_management_add_picking_item($pickingListId, $itemData) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('picking_items');
    
    $orderId = $itemData['order_id'] ?? 0;
    $orderItemId = $itemData['order_item_id'] ?? null;
    $productId = $itemData['product_id'] ?? null;
    $variantId = $itemData['variant_id'] ?? null;
    $location = $itemData['location'] ?? null;
    $quantity = $itemData['quantity'] ?? 1;
    $sequenceOrder = $itemData['sequence_order'] ?? 0;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (picking_list_id, order_id, order_item_id, product_id, variant_id, location, quantity, sequence_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiiiisii", $pickingListId, $orderId, $orderItemId, $productId, $variantId, $location, $quantity, $sequenceOrder);
        if ($stmt->execute()) {
            $itemId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'item_id' => $itemId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Mark picking item as picked
 * @param int $itemId Picking item ID
 * @param int $pickerId Picker user ID
 * @return array Result
 */
function order_management_mark_item_picked($itemId, $pickerId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('picking_items');
    $stmt = $conn->prepare("UPDATE {$tableName} SET picked_status = 1, picker_id = ?, picked_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $pickerId, $itemId);
        if ($stmt->execute()) {
            $stmt->close();
            
            // Check if all items in picking list are picked
            $pickingListTable = order_management_get_table_name('picking_lists');
            $stmt = $conn->prepare("SELECT pl.id FROM {$pickingListTable} pl 
                                   INNER JOIN {$tableName} pi ON pl.id = pi.picking_list_id 
                                   WHERE pi.id = ?");
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            $result = $stmt->get_result();
            $pickingList = $result->fetch_assoc();
            $stmt->close();
            
            if ($pickingList) {
                // Check if all items are picked
                $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(picked_status) as picked FROM {$tableName} WHERE picking_list_id = ?");
                $stmt->bind_param("i", $pickingList['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $stats = $result->fetch_assoc();
                $stmt->close();
                
                if ($stats['total'] == $stats['picked']) {
                    // All items picked, update picking list status
                    $stmt = $conn->prepare("UPDATE {$pickingListTable} SET status = 'completed', completed_at = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $pickingList['id']);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
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
 * Complete picking list
 * @param int $pickingListId Picking list ID
 * @return array Result
 */
function order_management_complete_picking_list($pickingListId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('picking_lists');
    $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'completed', completed_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $pickingListId);
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

