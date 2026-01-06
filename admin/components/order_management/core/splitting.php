<?php
/**
 * Order Management Component - Splitting & Merging Functions
 * Order splitting and merging functionality
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Split order into multiple orders
 * @param int $orderId Order ID
 * @param array $splitConfig Split configuration (items per new order)
 * @return array Result
 */
function order_management_split_order($orderId, $splitConfig) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get original order
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $originalOrder = $result->fetch_assoc();
    $stmt->close();
    
    if (!$originalOrder) {
        return ['success' => false, 'error' => 'Order not found'];
    }
    
    // Get order items
    $stmt = $conn->prepare("SELECT * FROM commerce_order_items WHERE order_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orderItems = [];
    while ($row = $result->fetch_assoc()) {
        $orderItems[] = $row;
    }
    $stmt->close();
    
    $newOrderIds = [];
    $itemIndex = 0;
    
    foreach ($splitConfig as $split) {
        // Create new order
        $newOrderData = [
            'customer_id' => $originalOrder['customer_id'],
            'status' => $originalOrder['status'],
            'total_amount' => 0,
            'shipping_address' => $originalOrder['shipping_address'],
            'billing_address' => $originalOrder['billing_address']
        ];
        
        // This would use commerce_create_order if available
        // For now, placeholder
        $newOrderId = null; // Would be created via commerce component
        
        if ($newOrderId) {
            // Add items to new order
            $itemsToAdd = $split['items'] ?? [];
            foreach ($itemsToAdd as $itemConfig) {
                // Add item to new order
            }
            
            $newOrderIds[] = $newOrderId;
        }
    }
    
    // Record split in database
    $tableName = order_management_get_table_name('order_splits');
    $splitData = json_encode(['original_order_id' => $orderId, 'new_order_ids' => $newOrderIds]);
    $stmt = $conn->prepare("INSERT INTO {$tableName} (original_order_id, split_data, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $orderId, $splitData);
    $stmt->execute();
    $stmt->close();
    
    return ['success' => true, 'new_order_ids' => $newOrderIds];
}

/**
 * Merge multiple orders into one
 * @param array $orderIds Order IDs to merge
 * @return array Result
 */
function order_management_merge_orders($orderIds) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if (count($orderIds) < 2) {
        return ['success' => false, 'error' => 'Need at least 2 orders to merge'];
    }
    
    // Get first order as base
    $baseOrderId = $orderIds[0];
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $baseOrderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $baseOrder = $result->fetch_assoc();
    $stmt->close();
    
    // Collect all items from all orders
    $allItems = [];
    $totalAmount = $baseOrder['total_amount'];
    
    for ($i = 1; $i < count($orderIds); $i++) {
        $orderId = $orderIds[$i];
        $stmt = $conn->prepare("SELECT * FROM commerce_order_items WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $allItems[] = $row;
        }
        $stmt->close();
        
        // Get order total
        $stmt = $conn->prepare("SELECT total_amount FROM commerce_orders WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $totalAmount += $order['total_amount'];
        $stmt->close();
    }
    
    // Update base order with merged data
    $stmt = $conn->prepare("UPDATE commerce_orders SET total_amount = ? WHERE id = ?");
    $stmt->bind_param("di", $totalAmount, $baseOrderId);
    $stmt->execute();
    $stmt->close();
    
    // Move items to base order
    foreach ($allItems as $item) {
        $stmt = $conn->prepare("UPDATE commerce_order_items SET order_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $baseOrderId, $item['id']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Record merge
    $tableName = order_management_get_table_name('order_merges');
    $mergeData = json_encode(['merged_order_ids' => $orderIds, 'result_order_id' => $baseOrderId]);
    $stmt = $conn->prepare("INSERT INTO {$tableName} (result_order_id, merge_data, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $baseOrderId, $mergeData);
    $stmt->execute();
    $stmt->close();
    
    return ['success' => true, 'merged_order_id' => $baseOrderId];
}

