<?php
/**
 * Order Management Component - COGS Functions
 * Cost of goods sold tracking
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get COGS for order
 * @param int $orderId Order ID
 * @return array|null COGS data
 */
function order_management_get_order_cogs($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('order_cogs');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE order_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $cogs = $result->fetch_assoc();
        $stmt->close();
        return $cogs;
    }
    
    return null;
}

/**
 * Calculate and set COGS for order
 * @param int $orderId Order ID
 * @return array Result
 */
function order_management_calculate_order_cogs($orderId) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get order items
    $stmt = $conn->prepare("SELECT * FROM commerce_order_items WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orderItems = [];
    while ($row = $result->fetch_assoc()) {
        $orderItems[] = $row;
    }
    $stmt->close();
    
    $totalCogs = 0;
    $itemCogs = [];
    
    foreach ($orderItems as $item) {
        // Get product cost (would integrate with inventory/product system)
        $productCost = 0;
        
        // Try to get from inventory if available
        if (order_management_is_inventory_available() && function_exists('inventory_get_item_cost')) {
            $cost = inventory_get_item_cost($item['product_id'], $item['variant_id'] ?? null);
            $productCost = $cost['cost'] ?? 0;
        }
        
        $itemCogsValue = $productCost * $item['quantity'];
        $totalCogs += $itemCogsValue;
        
        $itemCogs[] = [
            'order_item_id' => $item['id'],
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_cost' => $productCost,
            'total_cost' => $itemCogsValue
        ];
    }
    
    // Get order total
    $stmt = $conn->prepare("SELECT total_amount FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $orderTotal = $order['total_amount'] ?? 0;
    $stmt->close();
    
    $profit = $orderTotal - $totalCogs;
    $profitMargin = $orderTotal > 0 ? ($profit / $orderTotal) * 100 : 0;
    
    // Save COGS
    $tableName = order_management_get_table_name('order_cogs');
    $itemCogsJson = json_encode($itemCogs);
    
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        // Update
        $stmt = $conn->prepare("UPDATE {$tableName} SET total_cogs = ?, profit = ?, profit_margin = ?, item_cogs = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("dddsi", $totalCogs, $profit, $profitMargin, $itemCogsJson, $existing['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, total_cogs, profit, profit_margin, item_cogs, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iddds", $orderId, $totalCogs, $profit, $profitMargin, $itemCogsJson);
        $stmt->execute();
        $stmt->close();
    }
    
    return [
        'success' => true,
        'total_cogs' => $totalCogs,
        'profit' => $profit,
        'profit_margin' => $profitMargin
    ];
}

/**
 * Get profitability report
 * @param array $filters Filters
 * @return array Report data
 */
function order_management_get_profitability_report($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('order_cogs');
    $ordersTable = 'commerce_orders';
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(o.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(o.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $query = "SELECT 
        SUM(c.total_cogs) as total_cogs,
        SUM(c.profit) as total_profit,
        AVG(c.profit_margin) as avg_profit_margin,
        SUM(o.total_amount) as total_revenue,
        COUNT(*) as orders_count
    FROM {$tableName} c
    INNER JOIN {$ordersTable} o ON c.order_id = o.id
    {$whereClause}";
    
    $report = [];
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $report = $result->fetch_assoc();
        $stmt->close();
    } else {
        $result = $conn->query($query);
        $report = $result->fetch_assoc();
    }
    
    return ['success' => true, 'data' => $report];
}

