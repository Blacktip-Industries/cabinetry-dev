<?php
/**
 * Inventory Component - Movement Tracking Functions
 * Stock movement history and tracking
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Record inventory movement
 * @param int $itemId Item ID
 * @param int $locationId Location ID
 * @param string $movementType Movement type (in/out/adjustment/transfer/reservation/release/count)
 * @param int $quantity Quantity
 * @param string|null $referenceType Reference type
 * @param int|null $referenceId Reference ID
 * @param string|null $notes Notes
 * @param float|null $unitCost Unit cost
 * @return array Result with success status
 */
function inventory_record_movement($itemId, $locationId, $movementType, $quantity, $referenceType = null, $referenceId = null, $notes = null, $unitCost = null) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('movements');
    $userId = inventory_get_current_user_id();
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (item_id, location_id, movement_type, quantity, unit_cost, reference_type, reference_id, created_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisidisis", $itemId, $locationId, $movementType, $quantity, $unitCost, $referenceType, $referenceId, $userId, $notes);
        $result = $stmt->execute();
        $movementId = $result ? $conn->insert_id : null;
        $stmt->close();
        return ['success' => $result, 'movement_id' => $movementId];
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get movements with filters
 * @param array $filters Filters (item_id, location_id, movement_type, date_from, date_to, etc.)
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Array of movements
 */
function inventory_get_movements($filters = [], $limit = 100, $offset = 0) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('movements');
    $itemsTable = inventory_get_table_name('items');
    $locationsTable = inventory_get_table_name('locations');
    
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['item_id'])) {
        $where[] = 'm.item_id = ?';
        $params[] = (int)$filters['item_id'];
        $types .= 'i';
    }
    
    if (isset($filters['location_id'])) {
        $where[] = 'm.location_id = ?';
        $params[] = (int)$filters['location_id'];
        $types .= 'i';
    }
    
    if (isset($filters['movement_type']) && $filters['movement_type'] !== '') {
        $where[] = 'm.movement_type = ?';
        $params[] = $filters['movement_type'];
        $types .= 's';
    }
    
    if (isset($filters['date_from'])) {
        $where[] = 'DATE(m.created_at) >= ?';
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (isset($filters['date_to'])) {
        $where[] = 'DATE(m.created_at) <= ?';
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT m.*, i.item_name, i.item_code, l.location_name, l.location_code 
              FROM {$tableName} m 
              LEFT JOIN {$itemsTable} i ON m.item_id = i.id 
              LEFT JOIN {$locationsTable} l ON m.location_id = l.id 
              {$whereClause} 
              ORDER BY m.created_at DESC 
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $movements = [];
        while ($row = $result->fetch_assoc()) {
            $movements[] = $row;
        }
        $stmt->close();
        return $movements;
    }
    
    return [];
}

/**
 * Get movement by ID
 * @param int $movementId Movement ID
 * @return array|null Movement data or null
 */
function inventory_get_movement($movementId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('movements');
    $itemsTable = inventory_get_table_name('items');
    $locationsTable = inventory_get_table_name('locations');
    
    $stmt = $conn->prepare("SELECT m.*, i.item_name, i.item_code, l.location_name, l.location_code 
                            FROM {$tableName} m 
                            LEFT JOIN {$itemsTable} i ON m.item_id = i.id 
                            LEFT JOIN {$locationsTable} l ON m.location_id = l.id 
                            WHERE m.id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $movementId);
        $stmt->execute();
        $result = $stmt->get_result();
        $movement = $result->fetch_assoc();
        $stmt->close();
        return $movement;
    }
    
    return null;
}

