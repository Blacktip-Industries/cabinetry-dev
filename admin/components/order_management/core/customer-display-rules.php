<?php
/**
 * Order Management Component - Customer Display Rules
 * Functions for managing what information customers can see
 */

require_once __DIR__ . '/database.php';

/**
 * Get customer queue items
 * @param int $accountId Account ID
 * @return array Queue items
 */
function order_management_get_customer_queue_items($accountId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('production_queue');
    $ordersTable = 'commerce_orders'; // Assuming commerce component
    
    $sql = "SELECT q.*, o.order_number, o.account_id, o.need_by_date, o.is_rush_order 
            FROM {$tableName} q 
            LEFT JOIN {$ordersTable} o ON q.order_id = o.id 
            WHERE o.account_id = ? AND q.is_active = 1 
            ORDER BY q.queue_position ASC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $accountId);
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
 * Get queue item by order ID
 * @param int $orderId Order ID
 * @return array|null Queue item or null
 */
function order_management_get_queue_item_by_order($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('production_queue');
    $ordersTable = 'commerce_orders';
    
    $sql = "SELECT q.*, o.order_number, o.account_id, o.need_by_date, o.is_rush_order 
            FROM {$tableName} q 
            LEFT JOIN {$ordersTable} o ON q.order_id = o.id 
            WHERE q.order_id = ? AND q.is_active = 1 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item;
    }
    
    return null;
}

/**
 * Get customer display configuration for an order
 * @param int $orderId Order ID
 * @param int $accountId Account ID
 * @return array Display configuration
 */
function order_management_get_customer_display_config($orderId, $accountId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        // Default: show everything
        return [
            'show_queue_position' => true,
            'show_estimated_completion' => true,
            'show_need_by_date' => true,
            'show_rush_order' => true,
            'show_status' => true,
            'show_delays' => true
        ];
    }
    
    $tableName = order_management_get_table_name('customer_display_rules');
    
    // Get all active rules
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY priority ASC");
    if (!$stmt) {
        return [
            'show_queue_position' => true,
            'show_estimated_completion' => true,
            'show_need_by_date' => true,
            'show_rush_order' => true,
            'show_status' => true,
            'show_delays' => true
        ];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $rules = [];
    while ($row = $result->fetch_assoc()) {
        $rules[] = $row;
    }
    $stmt->close();
    
    // Get order data for condition evaluation
    $order = null;
    if (function_exists('commerce_get_order')) {
        $order = commerce_get_order($orderId);
    }
    
    // Default configuration
    $config = [
        'show_queue_position' => true,
        'show_estimated_completion' => true,
        'show_need_by_date' => true,
        'show_rush_order' => true,
        'show_status' => true,
        'show_delays' => true
    ];
    
    // Apply rules
    foreach ($rules as $rule) {
        $fieldName = $rule['field_name'];
        $displayType = $rule['display_type'];
        $conditions = json_decode($rule['conditions_json'] ?? '{}', true);
        
        // Check if conditions are met
        $conditionsMet = true;
        if (!empty($conditions) && $order) {
            $conditionsMet = order_management_evaluate_display_conditions($conditions, $order, $accountId);
        }
        
        // Apply rule
        if ($displayType === 'always') {
            $config["show_{$fieldName}"] = true;
        } elseif ($displayType === 'never') {
            $config["show_{$fieldName}"] = false;
        } elseif ($displayType === 'conditional' && $conditionsMet) {
            $config["show_{$fieldName}"] = true;
        } elseif ($displayType === 'conditional' && !$conditionsMet) {
            $config["show_{$fieldName}"] = false;
        }
    }
    
    return $config;
}

/**
 * Evaluate display rule conditions
 * @param array $conditions Conditions
 * @param array $order Order data
 * @param int $accountId Account ID
 * @return bool True if conditions met
 */
function order_management_evaluate_display_conditions($conditions, $order, $accountId) {
    // Check order status
    if (isset($conditions['order_status'])) {
        if ($order['status'] !== $conditions['order_status']) {
            return false;
        }
    }
    
    // Check payment status
    if (isset($conditions['payment_status'])) {
        if ($order['payment_status'] !== $conditions['payment_status']) {
            return false;
        }
    }
    
    // Check rush order
    if (isset($conditions['is_rush_order'])) {
        if ((bool)$order['is_rush_order'] !== (bool)$conditions['is_rush_order']) {
            return false;
        }
    }
    
    // Add more condition checks as needed
    
    return true;
}

/**
 * Get queue delays
 * @param int $queueId Queue ID
 * @return array Delays
 */
function order_management_get_queue_delays($queueId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('queue_delays');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE queue_id = ? ORDER BY delay_start_date DESC");
    if ($stmt) {
        $stmt->bind_param("i", $queueId);
        $stmt->execute();
        $result = $stmt->get_result();
        $delays = [];
        while ($row = $result->fetch_assoc()) {
            $delays[] = $row;
        }
        $stmt->close();
        return $delays;
    }
    
    return [];
}

