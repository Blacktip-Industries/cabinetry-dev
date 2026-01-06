<?php
/**
 * Inventory Component - Stock Management Functions
 * Stock level management and operations
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/movements.php';

/**
 * Get stock for item at location
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @return array|null Stock data or null
 */
function inventory_get_stock($itemId, $locationId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('stock');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE item_id = ? AND location_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $itemId, $locationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock = $result->fetch_assoc();
        $stmt->close();
        return $stock;
    }
    
    return null;
}

/**
 * Get all stock for an item
 * @param int $itemId Item ID
 * @return array Array of stock records
 */
function inventory_get_item_stock($itemId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('stock');
    $stmt = $conn->prepare("SELECT s.*, l.location_name, l.location_code FROM {$tableName} s INNER JOIN " . inventory_get_table_name('locations') . " l ON s.location_id = l.id WHERE s.item_id = ? ORDER BY l.location_name ASC");
    if ($stmt) {
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock = [];
        while ($row = $result->fetch_assoc()) {
            $stock[] = $row;
        }
        $stmt->close();
        return $stock;
    }
    
    return [];
}

/**
 * Get stock at location
 * @param int $locationId Location ID
 * @return array Array of stock records
 */
function inventory_get_location_stock($locationId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('stock');
    $stmt = $conn->prepare("SELECT s.*, i.item_name, i.item_code FROM {$tableName} s INNER JOIN " . inventory_get_table_name('items') . " i ON s.item_id = i.id WHERE s.location_id = ? ORDER BY i.item_name ASC");
    if ($stmt) {
        $stmt->bind_param("i", $locationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock = [];
        while ($row = $result->fetch_assoc()) {
            $stock[] = $row;
        }
        $stmt->close();
        return $stock;
    }
    
    return [];
}

/**
 * Update stock quantity
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @param int $quantityChange Quantity change (positive for increase, negative for decrease)
 * @param string $movementType Movement type
 * @param string|null $referenceType Reference type
 * @param int|null $referenceId Reference ID
 * @param string|null $notes Notes
 * @return array Result with success status
 */
function inventory_update_stock($itemId, $locationId, $quantityChange, $movementType = 'adjustment', $referenceType = null, $referenceId = null, $notes = null) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('stock');
    
    // Get or create stock record
    $stock = inventory_get_stock($itemId, $locationId);
    
    if ($stock) {
        $newQuantity = $stock['quantity_available'] + $quantityChange;
        if ($newQuantity < 0) {
            return ['success' => false, 'error' => 'Insufficient stock'];
        }
        
        $stmt = $conn->prepare("UPDATE {$tableName} SET quantity_available = ?, last_movement_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ii", $newQuantity, $stock['id']);
        $stmt->execute();
        $stmt->close();
        $stockId = $stock['id'];
    } else {
        $newQuantity = max(0, $quantityChange);
        $stmt = $conn->prepare("INSERT INTO {$tableName} (item_id, location_id, quantity_available, last_movement_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->bind_param("iii", $itemId, $locationId, $newQuantity);
        $stmt->execute();
        $stockId = $conn->insert_id;
        $stmt->close();
    }
    
    // Record movement
    inventory_record_movement($itemId, $locationId, $movementType, $quantityChange, $referenceType, $referenceId, $notes);
    
    return ['success' => true, 'stock_id' => $stockId, 'quantity' => $newQuantity];
}

/**
 * Reserve stock
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @param int $quantity Quantity to reserve
 * @return array Result with success status
 */
function inventory_reserve_stock($itemId, $locationId, $quantity) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $stock = inventory_get_stock($itemId, $locationId);
    if (!$stock || $stock['quantity_available'] < $quantity) {
        return ['success' => false, 'error' => 'Insufficient stock available'];
    }
    
    $tableName = inventory_get_table_name('stock');
    $newReserved = $stock['quantity_reserved'] + $quantity;
    $newAvailable = $stock['quantity_available'] - $quantity;
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET quantity_reserved = ?, quantity_available = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("iii", $newReserved, $newAvailable, $stock['id']);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        inventory_record_movement($itemId, $locationId, 'reservation', -$quantity, null, null, 'Stock reserved');
    }
    
    return ['success' => $result];
}

/**
 * Release reserved stock
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @param int $quantity Quantity to release
 * @return array Result with success status
 */
function inventory_release_stock($itemId, $locationId, $quantity) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $stock = inventory_get_stock($itemId, $locationId);
    if (!$stock || $stock['quantity_reserved'] < $quantity) {
        return ['success' => false, 'error' => 'Insufficient reserved stock'];
    }
    
    $tableName = inventory_get_table_name('stock');
    $newReserved = $stock['quantity_reserved'] - $quantity;
    $newAvailable = $stock['quantity_available'] + $quantity;
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET quantity_reserved = ?, quantity_available = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("iii", $newReserved, $newAvailable, $stock['id']);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        inventory_record_movement($itemId, $locationId, 'release', $quantity, null, null, 'Stock released from reservation');
    }
    
    return ['success' => $result];
}

