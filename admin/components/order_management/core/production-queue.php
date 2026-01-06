<?php
/**
 * Order Management Component - Production Queue Management
 * Queue entry, ordering engine, lock/pin system, delay management
 */

require_once __DIR__ . '/database.php';

/**
 * Add order to queue when payment is received
 * @param int $orderId Order ID
 * @param string $queueType Queue type ('rush' or 'normal')
 * @return array Result
 */
function order_management_add_order_to_queue($orderId, $queueType) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get order to check payment status and rush order flag
    // Note: This assumes commerce_get_order is accessible
    if (function_exists('commerce_get_order')) {
        $order = commerce_get_order($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }
        
        // Determine queue type from order if not provided
        if ($queueType === null) {
            $queueType = (!empty($order['is_rush_order']) && $order['is_rush_order'] == 1) ? 'rush' : 'normal';
        }
        
        // Get payment date (use updated_at if payment_status is 'paid')
        $paidAt = null;
        if ($order['payment_status'] === 'paid') {
            // Try to get actual payment date from order_payments table
            $paidAt = date('Y-m-d H:i:s');
        } else {
            return ['success' => false, 'error' => 'Order is not paid'];
        }
    } else {
        // Fallback: assume order is paid if function doesn't exist
        $paidAt = date('Y-m-d H:i:s');
    }
    
    $tableName = order_management_get_table_name('production_queue');
    
    // Get the next payment_order_position for this queue type
    $stmt = $conn->prepare("SELECT MAX(payment_order_position) as max_position FROM {$tableName} WHERE queue_type = ? AND is_active = 1");
    if ($stmt) {
        $stmt->bind_param("s", $queueType);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        $nextPaymentPosition = ($row['max_position'] ?? 0) + 1;
    } else {
        $nextPaymentPosition = 1;
    }
    
    // Get the next queue position (after highest locked position)
    $lockedPositions = order_management_get_locked_positions($queueType);
    $highestLocked = !empty($lockedPositions) ? max($lockedPositions) : 0;
    $nextQueuePosition = $highestLocked + 1;
    
    // Check if order already in queue
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE order_id = ? AND queue_type = ? AND is_active = 1");
    if ($stmt) {
        $stmt->bind_param("is", $orderId, $queueType);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            return ['success' => false, 'error' => 'Order already in queue'];
        }
        $stmt->close();
    }
    
    // Insert into queue
    $enteredAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, queue_type, queue_position, paid_at, entered_queue_at, payment_order_position, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    if ($stmt) {
        $stmt->bind_param("isissi", $orderId, $queueType, $nextQueuePosition, $paidAt, $enteredAt, $nextPaymentPosition);
        if ($stmt->execute()) {
            $queueId = $conn->insert_id;
            $stmt->close();
            
            // Apply ordering rules if auto-recalculate is enabled
            if (order_management_should_auto_recalculate('new_order_added')) {
                order_management_apply_ordering_rules($queueType);
            }
            
            return ['success' => true, 'queue_id' => $queueId, 'queue_position' => $nextQueuePosition, 'payment_order_position' => $nextPaymentPosition];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get locked positions for a queue type
 * @param string $queueType Queue type
 * @return array Array of locked position numbers
 */
function order_management_get_locked_positions($queueType) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('production_queue');
    $stmt = $conn->prepare("SELECT locked_position FROM {$tableName} WHERE queue_type = ? AND is_locked = 1 AND is_active = 1 AND locked_position IS NOT NULL");
    if ($stmt) {
        $stmt->bind_param("s", $queueType);
        $stmt->execute();
        $result = $stmt->get_result();
        $positions = [];
        while ($row = $result->fetch_assoc()) {
            $positions[] = (int)$row['locked_position'];
        }
        $stmt->close();
        return $positions;
    }
    
    return [];
}

/**
 * Check if auto-recalculate is enabled for a trigger
 * @param string $trigger Trigger name
 * @return bool True if enabled
 */
function order_management_should_auto_recalculate($trigger) {
    // TODO: Check configuration from order_management_parameters
    // For now, return true for new_order_added
    return ($trigger === 'new_order_added');
}

/**
 * Apply all active ordering rules to recalculate queue positions
 * @param string $queueType Queue type
 * @param bool $skipLocked Skip locked orders
 * @return bool Success
 */
function order_management_apply_ordering_rules($queueType, $skipLocked = true) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    // Get all locked positions
    $lockedPositions = $skipLocked ? order_management_get_locked_positions($queueType) : [];
    $highestLocked = !empty($lockedPositions) ? max($lockedPositions) : 0;
    
    // Get all unlocked orders
    $tableName = order_management_get_table_name('production_queue');
    $sql = "SELECT * FROM {$tableName} WHERE queue_type = ? AND is_active = 1";
    if ($skipLocked) {
        $sql .= " AND (is_locked = 0 OR is_locked IS NULL)";
    }
    $sql .= " ORDER BY payment_order_position ASC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("s", $queueType);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
    
    // Get active ordering rules
    $rulesTable = order_management_get_table_name('queue_ordering_rules');
    $stmt = $conn->prepare("SELECT * FROM {$rulesTable} WHERE is_active = 1 ORDER BY priority ASC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $rules = [];
        while ($row = $result->fetch_assoc()) {
            $rules[] = $row;
        }
        $stmt->close();
    }
    
    // Apply rules to calculate new positions
    // For now, keep payment order as base (positions start after highest locked)
    $newPosition = $highestLocked + 1;
    foreach ($orders as $order) {
        // TODO: Apply rule-based adjustments here
        // For now, just assign sequential positions
        $updateStmt = $conn->prepare("UPDATE {$tableName} SET queue_position = ? WHERE id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("ii", $newPosition, $order['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        $newPosition++;
    }
    
    return true;
}

/**
 * Remove order from queue
 * @param int $orderId Order ID
 * @return bool Success
 */
function order_management_remove_order_from_queue($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('production_queue');
    $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = 0 WHERE order_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Get production queue
 * @param string $queueType Queue type
 * @param array $filters Filters
 * @return array Queue data
 */
function order_management_get_production_queue($queueType, $filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('production_queue');
    $sql = "SELECT * FROM {$tableName} WHERE queue_type = ? AND is_active = 1 ORDER BY queue_position ASC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("s", $queueType);
    $stmt->execute();
    $result = $stmt->get_result();
    $queue = [];
    while ($row = $result->fetch_assoc()) {
        $queue[] = $row;
    }
    $stmt->close();
    
    return $queue;
}

