<?php
/**
 * Order Management Component - Returns Functions
 * Returns and refunds management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/approvals.php';

/**
 * Generate unique return number
 * @return string Return number
 */
function order_management_generate_return_number() {
    $prefix = order_management_get_parameter('return_number_prefix', 'RET');
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    return $prefix . '-' . $date . '-' . $random;
}

/**
 * Get return by ID
 * @param int $returnId Return ID
 * @return array|null Return data
 */
function order_management_get_return($returnId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('returns');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $returnId);
        $stmt->execute();
        $result = $stmt->get_result();
        $return = $result->fetch_assoc();
        $stmt->close();
        return $return;
    }
    
    return null;
}

/**
 * Get return by return number
 * @param string $returnNumber Return number
 * @return array|null Return data
 */
function order_management_get_return_by_number($returnNumber) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('returns');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE return_number = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $returnNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $return = $result->fetch_assoc();
        $stmt->close();
        return $return;
    }
    
    return null;
}

/**
 * Get returns for order
 * @param int $orderId Order ID
 * @return array Array of returns
 */
function order_management_get_order_returns($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('returns');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE order_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $returns = [];
        while ($row = $result->fetch_assoc()) {
            $returns[] = $row;
        }
        $stmt->close();
        return $returns;
    }
    
    return [];
}

/**
 * Create return request
 * @param int $orderId Order ID
 * @param array $data Return data
 * @return array Result with return ID
 */
function order_management_create_return($orderId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('returns');
    
    $returnNumber = order_management_generate_return_number();
    $returnType = $data['return_type'] ?? 'refund';
    $reason = $data['reason'] ?? null;
    $requestedBy = $data['requested_by'] ?? null;
    $status = 'pending';
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, return_number, return_type, reason, status, requested_by) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issssi", $orderId, $returnNumber, $returnType, $reason, $status, $requestedBy);
        if ($stmt->execute()) {
            $returnId = $conn->insert_id;
            $stmt->close();
            
            // Add return items if provided
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    order_management_add_return_item($returnId, $item);
                }
            }
            
            return ['success' => true, 'return_id' => $returnId, 'return_number' => $returnNumber];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update return
 * @param int $returnId Return ID
 * @param array $data Return data
 * @return array Result
 */
function order_management_update_return($returnId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('returns');
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['return_type'])) {
        $updates[] = "return_type = ?";
        $params[] = $data['return_type'];
        $types .= 's';
    }
    
    if (isset($data['reason'])) {
        $updates[] = "reason = ?";
        $params[] = $data['reason'];
        $types .= 's';
    }
    
    if (isset($data['status'])) {
        $updates[] = "status = ?";
        $params[] = $data['status'];
        $types .= 's';
        
        // Set approval date if approved
        if ($data['status'] === 'approved') {
            $updates[] = "approved_at = NOW()";
        }
    }
    
    if (isset($data['approved_by'])) {
        $updates[] = "approved_by = ?";
        $params[] = $data['approved_by'];
        $types .= 'i';
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $returnId;
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
 * Get return items
 * @param int $returnId Return ID
 * @return array Array of return items
 */
function order_management_get_return_items($returnId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('return_items');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE return_id = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $returnId);
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
 * Add item to return
 * @param int $returnId Return ID
 * @param array $itemData Item data
 * @return array Result
 */
function order_management_add_return_item($returnId, $itemData) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('return_items');
    
    $orderItemId = $itemData['order_item_id'] ?? null;
    $productId = $itemData['product_id'] ?? null;
    $variantId = $itemData['variant_id'] ?? null;
    $condition = $itemData['condition'] ?? 'new';
    $quantity = $itemData['quantity'] ?? 1;
    $disposition = $itemData['disposition'] ?? 'restock';
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (return_id, order_item_id, product_id, variant_id, condition, quantity, disposition) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiiisis", $returnId, $orderItemId, $productId, $variantId, $condition, $quantity, $disposition);
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
 * Approve return
 * @param int $returnId Return ID
 * @param int $userId User ID approving
 * @return array Result
 */
function order_management_approve_return($returnId, $userId) {
    $return = order_management_get_return($returnId);
    if (!$return) {
        return ['success' => false, 'error' => 'Return not found'];
    }
    
    // Update return status
    $result = order_management_update_return($returnId, [
        'status' => 'approved',
        'approved_by' => $userId
    ]);
    
    if (!$result['success']) {
        return $result;
    }
    
    // Process return based on type
    if ($return['return_type'] === 'refund') {
        // Create refund
        order_management_create_refund_from_return($returnId);
    } elseif ($return['return_type'] === 'exchange') {
        // Handle exchange (would create new order)
        // Placeholder for exchange logic
    }
    
    // Restock inventory
    order_management_restock_return_items($returnId);
    
    return ['success' => true];
}

/**
 * Reject return
 * @param int $returnId Return ID
 * @param int $userId User ID rejecting
 * @param string $reason Rejection reason
 * @return array Result
 */
function order_management_reject_return($returnId, $userId, $reason = null) {
    $return = order_management_get_return($returnId);
    $updateData = [
        'status' => 'rejected',
        'approved_by' => $userId
    ];
    
    if ($reason && $return) {
        $updateData['reason'] = ($return['reason'] ?? '') . ' [REJECTED: ' . $reason . ']';
    }
    
    return order_management_update_return($returnId, $updateData);
}

/**
 * Process return (after approval)
 * @param int $returnId Return ID
 * @return array Result
 */
function order_management_process_return($returnId) {
    $return = order_management_get_return($returnId);
    if (!$return) {
        return ['success' => false, 'error' => 'Return not found'];
    }
    
    if ($return['status'] !== 'approved') {
        return ['success' => false, 'error' => 'Return must be approved before processing'];
    }
    
    // Update status to processing
    order_management_update_return($returnId, ['status' => 'processing']);
    
    // Process based on return type
    if ($return['return_type'] === 'refund') {
        // Refund should already be created, just mark as completed
        order_management_update_return($returnId, ['status' => 'completed']);
    }
    
    return ['success' => true];
}

/**
 * Get returns by status
 * @param string $status Return status
 * @param array $filters Additional filters
 * @return array Array of returns
 */
function order_management_get_returns_by_status($status, $filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('returns');
    $where = ["status = ?"];
    $params = [$status];
    $types = 's';
    
    if (isset($filters['return_type'])) {
        $where[] = "return_type = ?";
        $params[] = $filters['return_type'];
        $types .= 's';
    }
    
    $whereClause = implode(' AND ', $where);
    $query = "SELECT * FROM {$tableName} WHERE {$whereClause} ORDER BY created_at DESC";
    
    $returns = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $returns[] = $row;
        }
        $stmt->close();
    }
    
    return $returns;
}

