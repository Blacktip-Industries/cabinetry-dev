<?php
/**
 * Inventory Component - Transfer Management Functions
 * Stock transfer management with approval workflow
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/stock.php';

/**
 * Get transfer by ID
 * @param int $transferId Transfer ID
 * @return array|null Transfer data or null
 */
function inventory_get_transfer($transferId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('transfers');
    $fromLocationTable = inventory_get_table_name('locations');
    $toLocationTable = inventory_get_table_name('locations');
    
    $stmt = $conn->prepare("SELECT t.*, 
                            fl.location_name as from_location_name, fl.location_code as from_location_code,
                            tl.location_name as to_location_name, tl.location_code as to_location_code
                            FROM {$tableName} t
                            LEFT JOIN {$fromLocationTable} fl ON t.from_location_id = fl.id
                            LEFT JOIN {$toLocationTable} tl ON t.to_location_id = tl.id
                            WHERE t.id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $transferId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transfer = $result->fetch_assoc();
        $stmt->close();
        return $transfer;
    }
    
    return null;
}

/**
 * Get transfers with filters
 * @param array $filters Filters
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Array of transfers
 */
function inventory_get_transfers($filters = [], $limit = 100, $offset = 0) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('transfers');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['status']) && $filters['status'] !== '') {
        $where[] = 'status = ?';
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (isset($filters['from_location_id'])) {
        $where[] = 'from_location_id = ?';
        $params[] = (int)$filters['from_location_id'];
        $types .= 'i';
    }
    
    if (isset($filters['to_location_id'])) {
        $where[] = 'to_location_id = ?';
        $params[] = (int)$filters['to_location_id'];
        $types .= 'i';
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
        $transfers = [];
        while ($row = $result->fetch_assoc()) {
            $transfers[] = $row;
        }
        $stmt->close();
        return $transfers;
    }
    
    return [];
}

/**
 * Create transfer
 * @param array $transferData Transfer data
 * @return array Result with success status and transfer ID
 */
function inventory_create_transfer($transferData) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('transfers');
    
    // Validate required fields
    if (empty($transferData['from_location_id']) || empty($transferData['to_location_id'])) {
        return ['success' => false, 'error' => 'From and to locations are required'];
    }
    
    if ($transferData['from_location_id'] == $transferData['to_location_id']) {
        return ['success' => false, 'error' => 'From and to locations must be different'];
    }
    
    if (empty($transferData['items']) || !is_array($transferData['items'])) {
        return ['success' => false, 'error' => 'Transfer items are required'];
    }
    
    $transferNumber = inventory_generate_transfer_number();
    $fromLocationId = (int)$transferData['from_location_id'];
    $toLocationId = (int)$transferData['to_location_id'];
    $status = 'pending';
    $requestedBy = inventory_get_current_user_id();
    $notes = $transferData['notes'] ?? null;
    
    // Check if approval is required
    if (inventory_requires_transfer_approval()) {
        $status = 'pending';
    }
    
    $conn->begin_transaction();
    
    try {
        // Create transfer record
        $stmt = $conn->prepare("INSERT INTO {$tableName} (transfer_number, from_location_id, to_location_id, status, requested_by, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisis", $transferNumber, $fromLocationId, $toLocationId, $status, $requestedBy, $notes);
        $stmt->execute();
        $transferId = $conn->insert_id;
        $stmt->close();
        
        // Create transfer items
        $itemsTable = inventory_get_table_name('transfer_items');
        foreach ($transferData['items'] as $item) {
            $itemId = (int)$item['item_id'];
            $quantity = (int)$item['quantity'];
            
            // Check stock availability
            $stock = inventory_get_stock($itemId, $fromLocationId);
            if (!$stock || $stock['quantity_available'] < $quantity) {
                throw new Exception("Insufficient stock for item ID {$itemId} at source location");
            }
            
            $stmt = $conn->prepare("INSERT INTO {$itemsTable} (transfer_id, item_id, quantity_requested, notes) VALUES (?, ?, ?, ?)");
            $itemNotes = $item['notes'] ?? null;
            $stmt->bind_param("iiis", $transferId, $itemId, $quantity, $itemNotes);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        return ['success' => true, 'id' => $transferId, 'transfer_number' => $transferNumber];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Approve transfer
 * @param int $transferId Transfer ID
 * @param int|null $approvedBy Approver user ID
 * @return array Result with success status
 */
function inventory_approve_transfer($transferId, $approvedBy = null) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $transfer = inventory_get_transfer($transferId);
    if (!$transfer) {
        return ['success' => false, 'error' => 'Transfer not found'];
    }
    
    if ($transfer['status'] !== 'pending') {
        return ['success' => false, 'error' => 'Transfer is not pending'];
    }
    
    $tableName = inventory_get_table_name('transfers');
    $approvedBy = $approvedBy ?? inventory_get_current_user_id();
    $approvedAt = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'approved', approved_by = ?, approved_at = ? WHERE id = ?");
    $stmt->bind_param("isi", $approvedBy, $approvedAt, $transferId);
    $result = $stmt->execute();
    $stmt->close();
    
    return ['success' => $result];
}

/**
 * Process transfer (ship items)
 * @param int $transferId Transfer ID
 * @param array $shippedItems Array of [item_id => quantity_shipped]
 * @return array Result with success status
 */
function inventory_process_transfer_ship($transferId, $shippedItems = []) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $transfer = inventory_get_transfer($transferId);
    if (!$transfer) {
        return ['success' => false, 'error' => 'Transfer not found'];
    }
    
    if ($transfer['status'] !== 'approved' && $transfer['status'] !== 'in_transit') {
        return ['success' => false, 'error' => 'Transfer must be approved before shipping'];
    }
    
    $itemsTable = inventory_get_table_name('transfer_items');
    $conn->begin_transaction();
    
    try {
        // Update shipped quantities
        foreach ($shippedItems as $itemId => $quantityShipped) {
            $stmt = $conn->prepare("UPDATE {$itemsTable} SET quantity_shipped = ? WHERE transfer_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $quantityShipped, $transferId, $itemId);
            $stmt->execute();
            $stmt->close();
            
            // Deduct stock from source location
            $result = inventory_update_stock($itemId, $transfer['from_location_id'], -$quantityShipped, 'transfer', 'transfer', $transferId, "Shipped for transfer {$transfer['transfer_number']}");
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
        }
        
        // Update transfer status
        $tableName = inventory_get_table_name('transfers');
        $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'in_transit', processed_by = ? WHERE id = ?");
        $processedBy = inventory_get_current_user_id();
        $stmt->bind_param("ii", $processedBy, $transferId);
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
 * Complete transfer (receive items)
 * @param int $transferId Transfer ID
 * @param array $receivedItems Array of [item_id => quantity_received]
 * @return array Result with success status
 */
function inventory_complete_transfer($transferId, $receivedItems = []) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $transfer = inventory_get_transfer($transferId);
    if (!$transfer) {
        return ['success' => false, 'error' => 'Transfer not found'];
    }
    
    if ($transfer['status'] !== 'in_transit') {
        return ['success' => false, 'error' => 'Transfer must be in transit before completing'];
    }
    
    $itemsTable = inventory_get_table_name('transfer_items');
    $conn->begin_transaction();
    
    try {
        // Update received quantities and add stock to destination
        foreach ($receivedItems as $itemId => $quantityReceived) {
            $stmt = $conn->prepare("UPDATE {$itemsTable} SET quantity_received = ? WHERE transfer_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $quantityReceived, $transferId, $itemId);
            $stmt->execute();
            $stmt->close();
            
            // Add stock to destination location
            $result = inventory_update_stock($itemId, $transfer['to_location_id'], $quantityReceived, 'transfer', 'transfer', $transferId, "Received from transfer {$transfer['transfer_number']}");
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
        }
        
        // Update transfer status
        $tableName = inventory_get_table_name('transfers');
        $processedAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'completed', processed_at = ? WHERE id = ?");
        $stmt->bind_param("si", $processedAt, $transferId);
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
 * Get transfer items
 * @param int $transferId Transfer ID
 * @return array Array of transfer items
 */
function inventory_get_transfer_items($transferId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $itemsTable = inventory_get_table_name('transfer_items');
    $inventoryItemsTable = inventory_get_table_name('items');
    
    $stmt = $conn->prepare("SELECT ti.*, i.item_name, i.item_code, i.sku 
                            FROM {$itemsTable} ti
                            LEFT JOIN {$inventoryItemsTable} i ON ti.item_id = i.id
                            WHERE ti.transfer_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $transferId);
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

