<?php
/**
 * Inventory Component - Adjustment Management Functions
 * Stock adjustment requests with approval workflow
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/stock.php';

/**
 * Get adjustment by ID
 * @param int $adjustmentId Adjustment ID
 * @return array|null Adjustment data or null
 */
function inventory_get_adjustment($adjustmentId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('adjustments');
    $locationTable = inventory_get_table_name('locations');
    
    $stmt = $conn->prepare("SELECT a.*, l.location_name, l.location_code 
                            FROM {$tableName} a
                            LEFT JOIN {$locationTable} l ON a.location_id = l.id
                            WHERE a.id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $adjustmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $adjustment = $result->fetch_assoc();
        $stmt->close();
        return $adjustment;
    }
    
    return null;
}

/**
 * Get adjustments with filters
 * @param array $filters Filters
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Array of adjustments
 */
function inventory_get_adjustments($filters = [], $limit = 100, $offset = 0) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('adjustments');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['status']) && $filters['status'] !== '') {
        $where[] = 'status = ?';
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (isset($filters['location_id'])) {
        $where[] = 'location_id = ?';
        $params[] = (int)$filters['location_id'];
        $types .= 'i';
    }
    
    if (isset($filters['adjustment_type']) && $filters['adjustment_type'] !== '') {
        $where[] = 'adjustment_type = ?';
        $params[] = $filters['adjustment_type'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $adjustments = [];
        while ($row = $result->fetch_assoc()) {
            $adjustments[] = $row;
        }
        $stmt->close();
        return $adjustments;
    }
    
    return [];
}

/**
 * Create adjustment
 * @param array $adjustmentData Adjustment data
 * @return array Result with success status and adjustment ID
 */
function inventory_create_adjustment($adjustmentData) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('adjustments');
    
    // Validate required fields
    if (empty($adjustmentData['location_id'])) {
        return ['success' => false, 'error' => 'Location is required'];
    }
    
    if (empty($adjustmentData['items']) || !is_array($adjustmentData['items'])) {
        return ['success' => false, 'error' => 'Adjustment items are required'];
    }
    
    $adjustmentNumber = inventory_generate_adjustment_number();
    $locationId = (int)$adjustmentData['location_id'];
    $adjustmentType = $adjustmentData['adjustment_type'] ?? 'count';
    $status = 'pending';
    $requestedBy = inventory_get_current_user_id();
    $reason = $adjustmentData['reason'] ?? null;
    $notes = $adjustmentData['notes'] ?? null;
    
    // Check if approval is required
    if (inventory_requires_adjustment_approval()) {
        $status = 'pending';
    } else {
        $status = 'approved'; // Auto-approve if not required
    }
    
    $conn->begin_transaction();
    
    try {
        // Create adjustment record
        $stmt = $conn->prepare("INSERT INTO {$tableName} (adjustment_number, location_id, status, adjustment_type, requested_by, reason, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sississ", $adjustmentNumber, $locationId, $status, $adjustmentType, $requestedBy, $reason, $notes);
        $stmt->execute();
        $adjustmentId = $conn->insert_id;
        $stmt->close();
        
        // Create adjustment items
        $itemsTable = inventory_get_table_name('adjustment_items');
        foreach ($adjustmentData['items'] as $item) {
            $itemId = (int)$item['item_id'];
            $quantityAfter = (int)$item['quantity_after'];
            
            // Get current stock
            $stock = inventory_get_stock($itemId, $locationId);
            $quantityBefore = $stock ? $stock['quantity_available'] : 0;
            $quantityChange = $quantityAfter - $quantityBefore;
            $unitCost = $item['unit_cost'] ?? null;
            $itemReason = $item['reason'] ?? null;
            
            $stmt = $conn->prepare("INSERT INTO {$itemsTable} (adjustment_id, item_id, quantity_before, quantity_after, quantity_change, unit_cost, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiids", $adjustmentId, $itemId, $quantityBefore, $quantityAfter, $quantityChange, $unitCost, $itemReason);
            $stmt->execute();
            $stmt->close();
        }
        
        // Auto-process if no approval required
        if ($status === 'approved') {
            inventory_process_adjustment($adjustmentId);
        }
        
        $conn->commit();
        return ['success' => true, 'id' => $adjustmentId, 'adjustment_number' => $adjustmentNumber];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Approve adjustment
 * @param int $adjustmentId Adjustment ID
 * @param int|null $approvedBy Approver user ID
 * @return array Result with success status
 */
function inventory_approve_adjustment($adjustmentId, $approvedBy = null) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $adjustment = inventory_get_adjustment($adjustmentId);
    if (!$adjustment) {
        return ['success' => false, 'error' => 'Adjustment not found'];
    }
    
    if ($adjustment['status'] !== 'pending') {
        return ['success' => false, 'error' => 'Adjustment is not pending'];
    }
    
    $tableName = inventory_get_table_name('adjustments');
    $approvedBy = $approvedBy ?? inventory_get_current_user_id();
    $approvedAt = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'approved', approved_by = ?, approved_at = ? WHERE id = ?");
    $stmt->bind_param("isi", $approvedBy, $approvedAt, $adjustmentId);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        // Process the adjustment
        return inventory_process_adjustment($adjustmentId);
    }
    
    return ['success' => $result];
}

/**
 * Reject adjustment
 * @param int $adjustmentId Adjustment ID
 * @return array Result with success status
 */
function inventory_reject_adjustment($adjustmentId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $adjustment = inventory_get_adjustment($adjustmentId);
    if (!$adjustment) {
        return ['success' => false, 'error' => 'Adjustment not found'];
    }
    
    if ($adjustment['status'] !== 'pending') {
        return ['success' => false, 'error' => 'Adjustment is not pending'];
    }
    
    $tableName = inventory_get_table_name('adjustments');
    $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $adjustmentId);
    $result = $stmt->execute();
    $stmt->close();
    
    return ['success' => $result];
}

/**
 * Process adjustment (apply stock changes)
 * @param int $adjustmentId Adjustment ID
 * @return array Result with success status
 */
function inventory_process_adjustment($adjustmentId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $adjustment = inventory_get_adjustment($adjustmentId);
    if (!$adjustment) {
        return ['success' => false, 'error' => 'Adjustment not found'];
    }
    
    if ($adjustment['status'] !== 'approved') {
        return ['success' => false, 'error' => 'Adjustment must be approved before processing'];
    }
    
    $itemsTable = inventory_get_table_name('adjustment_items');
    $conn->begin_transaction();
    
    try {
        // Get adjustment items
        $stmt = $conn->prepare("SELECT * FROM {$itemsTable} WHERE adjustment_id = ?");
        $stmt->bind_param("i", $adjustmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        
        // Apply stock changes
        foreach ($items as $item) {
            $quantityChange = $item['quantity_change'];
            $result = inventory_update_stock(
                $item['item_id'],
                $adjustment['location_id'],
                $quantityChange,
                'adjustment',
                'adjustment',
                $adjustmentId,
                "Adjustment: {$adjustment['adjustment_number']} - {$adjustment['reason']}"
            );
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
        }
        
        // Update adjustment status
        $tableName = inventory_get_table_name('adjustments');
        $processedAt = date('Y-m-d H:i:s');
        $processedBy = inventory_get_current_user_id();
        $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'completed', processed_by = ?, processed_at = ? WHERE id = ?");
        $stmt->bind_param("isi", $processedBy, $processedAt, $adjustmentId);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get adjustment items
 * @param int $adjustmentId Adjustment ID
 * @return array Array of adjustment items
 */
function inventory_get_adjustment_items($adjustmentId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $itemsTable = inventory_get_table_name('adjustment_items');
    $inventoryItemsTable = inventory_get_table_name('items');
    
    $stmt = $conn->prepare("SELECT ai.*, i.item_name, i.item_code, i.sku 
                            FROM {$itemsTable} ai
                            LEFT JOIN {$inventoryItemsTable} i ON ai.item_id = i.id
                            WHERE ai.adjustment_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $adjustmentId);
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

