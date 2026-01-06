<?php
/**
 * Order Management Component - Refund Functions
 * Refund processing and integration
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/returns.php';
require_once __DIR__ . '/functions.php';

/**
 * Get refund by ID
 * @param int $refundId Refund ID
 * @return array|null Refund data
 */
function order_management_get_refund($refundId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('refunds');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $refundId);
        $stmt->execute();
        $result = $stmt->get_result();
        $refund = $result->fetch_assoc();
        $stmt->close();
        return $refund;
    }
    
    return null;
}

/**
 * Get refunds for order
 * @param int $orderId Order ID
 * @return array Array of refunds
 */
function order_management_get_order_refunds($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('refunds');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE order_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $refunds = [];
        while ($row = $result->fetch_assoc()) {
            $refunds[] = $row;
        }
        $stmt->close();
        return $refunds;
    }
    
    return [];
}

/**
 * Create refund from return
 * @param int $returnId Return ID
 * @return array Result with refund ID
 */
function order_management_create_refund_from_return($returnId) {
    $return = order_management_get_return($returnId);
    if (!$return) {
        return ['success' => false, 'error' => 'Return not found'];
    }
    
    // Calculate refund amount from return items
    $returnItems = order_management_get_return_items($returnId);
    $refundAmount = 0;
    
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    
    foreach ($returnItems as $item) {
        // Get order item price
        if ($item['order_item_id']) {
            $stmt = $conn->prepare("SELECT total_price, quantity FROM commerce_order_items WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $item['order_item_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $orderItem = $result->fetch_assoc();
            $stmt->close();
            
            if ($orderItem) {
                $unitPrice = $orderItem['total_price'] / $orderItem['quantity'];
                $refundAmount += $unitPrice * $item['quantity'];
            }
        }
    }
    
    // Create refund record
    $refundData = [
        'return_id' => $returnId,
        'order_id' => $return['order_id'],
        'refund_amount' => $refundAmount,
        'refund_method' => 'original_payment_method',
        'status' => 'pending'
    ];
    
    $result = order_management_create_refund($refundData);
    
    // Process refund if payment_processing is available
    if ($result['success'] && order_management_is_payment_processing_available()) {
        order_management_process_refund_payment($result['refund_id']);
    }
    
    return $result;
}

/**
 * Create refund
 * @param array $data Refund data
 * @return array Result with refund ID
 */
function order_management_create_refund($data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('refunds');
    
    $returnId = $data['return_id'] ?? null;
    $orderId = $data['order_id'] ?? 0;
    $refundAmount = $data['refund_amount'] ?? 0;
    $refundMethod = $data['refund_method'] ?? 'original_payment_method';
    $status = $data['status'] ?? 'pending';
    $approvedBy = $data['approved_by'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (return_id, order_id, refund_amount, refund_method, status, approved_by) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iidssi", $returnId, $orderId, $refundAmount, $refundMethod, $status, $approvedBy);
        if ($stmt->execute()) {
            $refundId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'refund_id' => $refundId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Process refund payment (integrate with payment_processing)
 * @param int $refundId Refund ID
 * @return array Result
 */
function order_management_process_refund_payment($refundId) {
    $refund = order_management_get_refund($refundId);
    if (!$refund) {
        return ['success' => false, 'error' => 'Refund not found'];
    }
    
    if (!order_management_is_payment_processing_available()) {
        return ['success' => false, 'error' => 'Payment processing component not available'];
    }
    
    // Get original transaction from order
    $conn = order_management_get_db_connection();
    
    // Check if payment_processing_transactions table exists
    $result = $conn->query("SHOW TABLES LIKE 'payment_processing_transactions'");
    if (!$result || $result->num_rows === 0) {
        return ['success' => false, 'error' => 'Payment processing tables not found'];
    }
    
    // Find original transaction
    $stmt = $conn->prepare("SELECT * FROM payment_processing_transactions WHERE order_id = ? AND status = 'completed' ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $refund['order_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();
    
    if (!$transaction) {
        return ['success' => false, 'error' => 'Original transaction not found'];
    }
    
    // Create refund transaction via payment_processing component
    if (function_exists('payment_processing_process_refund')) {
        $refundResult = payment_processing_process_refund([
            'transaction_id' => $transaction['id'],
            'amount' => $refund['refund_amount'],
            'reason' => 'Return refund',
            'metadata' => ['return_id' => $refund['return_id']]
        ]);
        
        if ($refundResult['success']) {
            // Update refund with transaction ID
            $tableName = order_management_get_table_name('refunds');
            $stmt = $conn->prepare("UPDATE {$tableName} SET transaction_id = ?, status = 'processing', processed_at = NOW() WHERE id = ?");
            $transactionId = $refundResult['transaction_id'] ?? null;
            $stmt->bind_param("ii", $transactionId, $refundId);
            $stmt->execute();
            $stmt->close();
            
            return ['success' => true, 'transaction_id' => $transactionId];
        } else {
            return ['success' => false, 'error' => $refundResult['error'] ?? 'Refund processing failed'];
        }
    } else {
        // Manual refund processing
        $tableName = order_management_get_table_name('refunds');
        $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'completed', processed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $refundId);
        $stmt->execute();
        $stmt->close();
        return ['success' => true, 'message' => 'Refund processed manually'];
    }
}

/**
 * Update refund status
 * @param int $refundId Refund ID
 * @param string $status New status
 * @return array Result
 */
function order_management_update_refund_status($refundId, $status) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('refunds');
    
    $updates = ["status = ?"];
    $params = [$status];
    $types = 's';
    
    if ($status === 'completed' || $status === 'processing') {
        $updates[] = "processed_at = NOW()";
    }
    
    $params[] = $refundId;
    $types .= 'i';
    
    $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
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
 * Restock return items (integrate with inventory)
 * @param int $returnId Return ID
 * @return array Result
 */
function order_management_restock_return_items($returnId) {
    $returnItems = order_management_get_return_items($returnId);
    
    if (empty($returnItems)) {
        return ['success' => false, 'error' => 'No items in return'];
    }
    
    if (!order_management_is_inventory_available()) {
        return ['success' => false, 'error' => 'Inventory component not available'];
    }
    
    $restocked = 0;
    $errors = [];
    
    foreach ($returnItems as $item) {
        if ($item['disposition'] !== 'restock') {
            continue;
        }
        
        // Restock via inventory component
        if (function_exists('inventory_update_stock')) {
            // Get default location
            $locationId = order_management_get_parameter('default_restock_location', null);
            
            if ($locationId && $item['product_id']) {
                $result = inventory_update_stock(
                    $item['product_id'],
                    $locationId,
                    $item['quantity'],
                    'in',
                    'return_restock',
                    'order_management',
                    $returnId,
                    'Restocked from return'
                );
                
                if ($result['success']) {
                    $restocked++;
                } else {
                    $errors[] = "Failed to restock product {$item['product_id']}: " . ($result['error'] ?? 'Unknown error');
                }
            }
        } else {
            // Fallback: update commerce inventory if available
            if (order_management_is_commerce_available() && function_exists('commerce_update_inventory')) {
                $result = commerce_update_inventory(
                    $item['product_id'],
                    $item['variant_id'],
                    null, // warehouse_id
                    $item['quantity'],
                    'in',
                    'return_restock',
                    $returnId,
                    'Restocked from return'
                );
                
                if ($result['success'] ?? false) {
                    $restocked++;
                }
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'restocked' => $restocked,
        'errors' => $errors
    ];
}

/**
 * Get return statistics
 * @param array $filters Filters (date, return_type, status)
 * @return array Statistics
 */
function order_management_get_return_stats($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('returns');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['return_type'])) {
        $where[] = "return_type = ?";
        $params[] = $filters['return_type'];
        $types .= 's';
    }
    
    if (isset($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (isset($filters['date'])) {
        $where[] = "DATE(created_at) = ?";
        $params[] = $filters['date'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stats = [
        'total_returns' => 0,
        'pending_returns' => 0,
        'approved_returns' => 0,
        'total_refund_amount' => 0
    ];
    
    // Total returns
    $query = "SELECT COUNT(*) as count FROM {$tableName} {$whereClause}";
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_returns'] = $row['count'] ?? 0;
        $stmt->close();
    } else {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_returns'] = $row['count'] ?? 0;
        }
    }
    
    // Pending returns
    $wherePending = array_merge($where, ["status = 'pending'"]);
    $whereClausePending = 'WHERE ' . implode(' AND ', $wherePending);
    $query = "SELECT COUNT(*) as count FROM {$tableName} {$whereClausePending}";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_returns'] = $row['count'] ?? 0;
    }
    
    // Total refund amount
    $refundsTable = order_management_get_table_name('refunds');
    $query = "SELECT SUM(refund_amount) as total FROM {$refundsTable} WHERE status = 'completed'";
    if (!empty($where)) {
        // Join with returns table
        $query = "SELECT SUM(rf.refund_amount) as total FROM {$refundsTable} rf 
                 INNER JOIN {$tableName} r ON rf.return_id = r.id 
                 {$whereClause} AND rf.status = 'completed'";
    }
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_refund_amount'] = $row['total'] ?? 0;
    }
    
    return $stats;
}

