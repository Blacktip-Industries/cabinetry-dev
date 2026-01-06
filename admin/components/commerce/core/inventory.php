<?php
/**
 * Commerce Component - Inventory Functions
 * Inventory management and stock tracking
 */

require_once __DIR__ . '/database.php';

/**
 * Get inventory for product/variant
 * @param int $productId Product ID
 * @param int|null $variantId Variant ID
 * @param int|null $warehouseId Warehouse ID
 * @return array Inventory data
 */
function commerce_get_inventory($productId, $variantId = null, $warehouseId = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('inventory');
    $where = ['product_id = ?'];
    $params = [$productId];
    $types = 'i';
    
    if ($variantId) {
        $where[] = 'variant_id = ?';
        $params[] = $variantId;
        $types .= 'i';
    } else {
        $where[] = 'variant_id IS NULL';
    }
    
    if ($warehouseId) {
        $where[] = 'warehouse_id = ?';
        $params[] = $warehouseId;
        $types .= 'i';
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    $stmt = $conn->prepare("SELECT * FROM {$tableName} {$whereClause}");
    
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $inventory = [];
        while ($row = $result->fetch_assoc()) {
            $inventory[] = $row;
        }
        $stmt->close();
        return $inventory;
    }
    
    return [];
}

/**
 * Update inventory quantity
 * @param int $productId Product ID
 * @param int|null $variantId Variant ID
 * @param int $warehouseId Warehouse ID
 * @param int $quantityChange Quantity change (positive for increase, negative for decrease)
 * @param string $movementType Movement type
 * @param string|null $referenceType Reference type
 * @param int|null $referenceId Reference ID
 * @param string|null $notes Notes
 * @return array Result
 */
function commerce_update_inventory($productId, $variantId, $warehouseId, $quantityChange, $movementType = 'adjustment', $referenceType = null, $referenceId = null, $notes = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('inventory');
    
    // Get or create inventory record
    $stmt = $conn->prepare("SELECT id, quantity_available FROM {$tableName} WHERE product_id = ? AND variant_id <=> ? AND warehouse_id = ?");
    $stmt->bind_param("iii", $productId, $variantId, $warehouseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $inventory = $result->fetch_assoc();
    $stmt->close();
    
    if ($inventory) {
        $newQuantity = $inventory['quantity_available'] + $quantityChange;
        if ($newQuantity < 0) {
            return ['success' => false, 'error' => 'Insufficient stock'];
        }
        
        $stmt = $conn->prepare("UPDATE {$tableName} SET quantity_available = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ii", $newQuantity, $inventory['id']);
        $stmt->execute();
        $stmt->close();
        $inventoryId = $inventory['id'];
    } else {
        $newQuantity = max(0, $quantityChange);
        $stmt = $conn->prepare("INSERT INTO {$tableName} (product_id, variant_id, warehouse_id, quantity_available) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $productId, $variantId, $warehouseId, $newQuantity);
        $stmt->execute();
        $inventoryId = $conn->insert_id;
        $stmt->close();
    }
    
    // Record movement
    commerce_record_inventory_movement($productId, $variantId, $warehouseId, $movementType, $quantityChange, $referenceType, $referenceId, $notes);
    
    return ['success' => true, 'inventory_id' => $inventoryId, 'quantity' => $newQuantity];
}

/**
 * Record inventory movement
 * @param int $productId Product ID
 * @param int|null $variantId Variant ID
 * @param int $warehouseId Warehouse ID
 * @param string $movementType Movement type
 * @param int $quantity Quantity
 * @param string|null $referenceType Reference type
 * @param int|null $referenceId Reference ID
 * @param string|null $notes Notes
 * @return array Result
 */
function commerce_record_inventory_movement($productId, $variantId, $warehouseId, $movementType, $quantity, $referenceType = null, $referenceId = null, $notes = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false];
    }
    
    $tableName = commerce_get_table_name('inventory_movements');
    $stmt = $conn->prepare("INSERT INTO {$tableName} (product_id, variant_id, warehouse_id, movement_type, quantity, reference_type, reference_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("iiisisis", $productId, $variantId, $warehouseId, $movementType, $quantity, $referenceType, $referenceId, $notes);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    }
    
    return ['success' => false];
}

/**
 * Reserve inventory for order
 * @param int $orderId Order ID
 * @return array Result
 */
function commerce_reserve_inventory_for_order($orderId) {
    require_once __DIR__ . '/orders.php';
    
    $order = commerce_get_order($orderId);
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found'];
    }
    
    $items = commerce_get_order_items($orderId);
    $defaultWarehouseId = commerce_get_default_warehouse_id();
    
    foreach ($items as $item) {
        $warehouseId = $defaultWarehouseId;
        $quantity = (int)$item['quantity'];
        
        // Reserve inventory
        $result = commerce_update_inventory(
            $item['product_id'],
            $item['variant_id'],
            $warehouseId,
            -$quantity,
            'reservation',
            'order',
            $orderId,
            'Reserved for order ' . $order['order_number']
        );
        
        if (!$result['success']) {
            return $result;
        }
    }
    
    return ['success' => true];
}

/**
 * Get default warehouse ID
 * @return int|null Warehouse ID
 */
function commerce_get_default_warehouse_id() {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('warehouses');
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE is_default = 1 AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $warehouse = $result->fetch_assoc();
        $stmt->close();
        return $warehouse ? $warehouse['id'] : null;
    }
    
    return null;
}

/**
 * Check low stock alerts
 * @return array Low stock items
 */
function commerce_check_low_stock() {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $inventoryTable = commerce_get_table_name('inventory');
    $alertsTable = commerce_get_table_name('low_stock_alerts');
    
    $sql = "SELECT i.*, a.alert_email, a.threshold_quantity 
            FROM {$inventoryTable} i
            INNER JOIN {$alertsTable} a ON (
                (a.product_id = i.product_id OR a.product_id IS NULL) AND
                (a.variant_id = i.variant_id OR a.variant_id IS NULL) AND
                (a.warehouse_id = i.warehouse_id OR a.warehouse_id IS NULL)
            )
            WHERE a.is_active = 1 
            AND i.quantity_available <= a.threshold_quantity
            AND (a.last_alert_sent_at IS NULL OR a.last_alert_sent_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))";
    
    $result = $conn->query($sql);
    $lowStockItems = [];
    while ($row = $result->fetch_assoc()) {
        $lowStockItems[] = $row;
    }
    
    return $lowStockItems;
}

