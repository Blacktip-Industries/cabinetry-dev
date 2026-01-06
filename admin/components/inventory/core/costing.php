<?php
/**
 * Inventory Component - Costing Functions
 * FIFO, LIFO, and Average Cost calculation
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Record cost for item
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @param float $unitCost Unit cost
 * @param int $quantity Quantity
 * @param string|null $referenceType Reference type
 * @param int|null $referenceId Reference ID
 * @param string|null $purchaseDate Purchase date
 * @param string|null $expiryDate Expiry date
 * @return array Result with success status
 */
function inventory_record_cost($itemId, $locationId, $unitCost, $quantity, $referenceType = null, $referenceId = null, $purchaseDate = null, $expiryDate = null) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $costMethod = inventory_get_costing_method();
    $tableName = inventory_get_table_name('costs');
    $totalCost = $unitCost * $quantity;
    
    $purchaseDate = $purchaseDate ?? date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (item_id, location_id, cost_method, unit_cost, quantity, total_cost, purchase_date, expiry_date, reference_type, reference_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisddissis", $itemId, $locationId, $costMethod, $unitCost, $quantity, $totalCost, $purchaseDate, $expiryDate, $referenceType, $referenceId);
    $result = $stmt->execute();
    $costId = $result ? $conn->insert_id : null;
    $stmt->close();
    
    return ['success' => $result, 'cost_id' => $costId];
}

/**
 * Calculate average cost for item at location
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @return float Average cost
 */
function inventory_calculate_average_cost($itemId, $locationId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return 0.0;
    }
    
    $tableName = inventory_get_table_name('costs');
    $stmt = $conn->prepare("SELECT SUM(total_cost) as total_cost, SUM(quantity) as total_quantity FROM {$tableName} WHERE item_id = ? AND location_id = ? AND quantity > 0");
    $stmt->bind_param("ii", $itemId, $locationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row && $row['total_quantity'] > 0) {
        return (float)($row['total_cost'] / $row['total_quantity']);
    }
    
    return 0.0;
}

/**
 * Get FIFO cost layers for item at location
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @param int $quantity Quantity needed
 * @return array Array of cost layers (oldest first)
 */
function inventory_get_fifo_layers($itemId, $locationId, $quantity) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('costs');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE item_id = ? AND location_id = ? AND quantity > 0 ORDER BY purchase_date ASC, created_at ASC");
    $stmt->bind_param("ii", $itemId, $locationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $layers = [];
    $remaining = $quantity;
    
    while ($row = $result->fetch_assoc() && $remaining > 0) {
        $layerQuantity = min($remaining, $row['quantity']);
        $layers[] = [
            'cost_id' => $row['id'],
            'unit_cost' => (float)$row['unit_cost'],
            'quantity' => $layerQuantity,
            'total_cost' => (float)$row['unit_cost'] * $layerQuantity,
            'purchase_date' => $row['purchase_date']
        ];
        $remaining -= $layerQuantity;
    }
    
    $stmt->close();
    return $layers;
}

/**
 * Get LIFO cost layers for item at location
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @param int $quantity Quantity needed
 * @return array Array of cost layers (newest first)
 */
function inventory_get_lifo_layers($itemId, $locationId, $quantity) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('costs');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE item_id = ? AND location_id = ? AND quantity > 0 ORDER BY purchase_date DESC, created_at DESC");
    $stmt->bind_param("ii", $itemId, $locationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $layers = [];
    $remaining = $quantity;
    
    while ($row = $result->fetch_assoc() && $remaining > 0) {
        $layerQuantity = min($remaining, $row['quantity']);
        $layers[] = [
            'cost_id' => $row['id'],
            'unit_cost' => (float)$row['unit_cost'],
            'quantity' => $layerQuantity,
            'total_cost' => (float)$row['unit_cost'] * $layerQuantity,
            'purchase_date' => $row['purchase_date']
        ];
        $remaining -= $layerQuantity;
    }
    
    $stmt->close();
    return $layers;
}

/**
 * Calculate cost for quantity using current costing method
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @param int $quantity Quantity
 * @return float Total cost
 */
function inventory_calculate_cost($itemId, $locationId, $quantity) {
    $costMethod = inventory_get_costing_method();
    
    switch ($costMethod) {
        case 'FIFO':
            $layers = inventory_get_fifo_layers($itemId, $locationId, $quantity);
            $totalCost = 0.0;
            foreach ($layers as $layer) {
                $totalCost += $layer['total_cost'];
            }
            return $totalCost;
            
        case 'LIFO':
            $layers = inventory_get_lifo_layers($itemId, $locationId, $quantity);
            $totalCost = 0.0;
            foreach ($layers as $layer) {
                $totalCost += $layer['total_cost'];
            }
            return $totalCost;
            
        case 'Average':
        default:
            $avgCost = inventory_calculate_average_cost($itemId, $locationId);
            return $avgCost * $quantity;
    }
}

/**
 * Update cost quantity (when stock is used)
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @param int $quantity Quantity to deduct
 * @return array Result with success status
 */
function inventory_update_cost_quantity($itemId, $locationId, $quantity) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $costMethod = inventory_get_costing_method();
    $tableName = inventory_get_table_name('costs');
    $remaining = $quantity;
    
    $conn->begin_transaction();
    
    try {
        if ($costMethod === 'FIFO') {
            $orderBy = "ORDER BY purchase_date ASC, created_at ASC";
        } else { // LIFO
            $orderBy = "ORDER BY purchase_date DESC, created_at DESC";
        }
        
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE item_id = ? AND location_id = ? AND quantity > 0 {$orderBy}");
        $stmt->bind_param("ii", $itemId, $locationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc() && $remaining > 0) {
            $deductQuantity = min($remaining, $row['quantity']);
            $newQuantity = $row['quantity'] - $deductQuantity;
            
            if ($newQuantity > 0) {
                $updateStmt = $conn->prepare("UPDATE {$tableName} SET quantity = ?, total_cost = unit_cost * quantity WHERE id = ?");
                $updateStmt->bind_param("di", $newQuantity, $row['id']);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                $deleteStmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
                $deleteStmt->bind_param("i", $row['id']);
                $deleteStmt->execute();
                $deleteStmt->close();
            }
            
            $remaining -= $deductQuantity;
        }
        
        $stmt->close();
        $conn->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Calculate inventory valuation
 * @param int|null $locationId Location ID (null for all locations)
 * @return float Total valuation
 */
function inventory_calculate_valuation($locationId = null) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return 0.0;
    }
    
    $stockTable = inventory_get_table_name('stock');
    $where = $locationId ? "WHERE s.location_id = {$locationId}" : "";
    
    $query = "SELECT s.item_id, s.location_id, s.quantity_available 
              FROM {$stockTable} s {$where}";
    
    $result = $conn->query($query);
    $totalValuation = 0.0;
    
    while ($row = $result->fetch_assoc()) {
        if ($row['quantity_available'] > 0) {
            $cost = inventory_calculate_cost($row['item_id'], $row['location_id'], $row['quantity_available']);
            $totalValuation += $cost;
        }
    }
    
    return $totalValuation;
}

