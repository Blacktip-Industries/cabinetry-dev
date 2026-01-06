<?php
/**
 * Order Management Component - Packing Functions
 * Packing interface and operations
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/fulfillment.php';
require_once __DIR__ . '/functions.php';

/**
 * Get packing queue (fulfillments ready for packing)
 * @param int $warehouseId Warehouse ID (optional)
 * @return array Array of fulfillments ready for packing
 */
function order_management_get_packing_queue($warehouseId = null) {
    $filters = ['warehouse_id' => $warehouseId];
    return order_management_get_fulfillments_by_status('packing', $filters);
}

/**
 * Start packing fulfillment
 * @param int $fulfillmentId Fulfillment ID
 * @return array Result
 */
function order_management_start_packing($fulfillmentId) {
    return order_management_update_fulfillment($fulfillmentId, [
        'fulfillment_status' => 'packing'
    ]);
}

/**
 * Complete packing for fulfillment
 * @param int $fulfillmentId Fulfillment ID
 * @param array $data Packing data (weight, dimensions, package_type)
 * @return array Result
 */
function order_management_complete_packing($fulfillmentId, $data = []) {
    // Update fulfillment status to ready for shipping
    $result = order_management_update_fulfillment($fulfillmentId, [
        'fulfillment_status' => 'packing'
    ]);
    
    // Store packing data in fulfillment notes or metadata
    if ($result['success'] && !empty($data)) {
        $fulfillment = order_management_get_fulfillment($fulfillmentId);
        $notes = $fulfillment['fulfillment_notes'] ?? '';
        $packingData = [
            'weight' => $data['weight'] ?? null,
            'dimensions' => [
                'length' => $data['length'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null
            ],
            'package_type' => $data['package_type'] ?? 'standard'
        ];
        
        $packingJson = json_encode($packingData);
        $newNotes = $notes . "\n[Packing Data: " . $packingJson . "]";
        
        order_management_update_fulfillment($fulfillmentId, [
            'fulfillment_notes' => $newNotes
        ]);
    }
    
    return $result;
}

/**
 * Verify all items are packed
 * @param int $fulfillmentId Fulfillment ID
 * @return array Result with verification status
 */
function order_management_verify_packing($fulfillmentId) {
    $items = order_management_get_fulfillment_items($fulfillmentId);
    
    if (empty($items)) {
        return ['success' => false, 'error' => 'No items in fulfillment'];
    }
    
    $allPacked = true;
    $missingItems = [];
    
    foreach ($items as $item) {
        // Check if item has been scanned/packed
        if (empty($item['barcode_data']) || empty($item['scanned_at'])) {
            $allPacked = false;
            $missingItems[] = [
                'item_id' => $item['id'],
                'product_id' => $item['product_id']
            ];
        }
    }
    
    return [
        'success' => true,
        'all_packed' => $allPacked,
        'missing_items' => $missingItems,
        'total_items' => count($items),
        'packed_items' => count($items) - count($missingItems)
    ];
}

/**
 * Get packing statistics
 * @param array $filters Filters (date, warehouse_id)
 * @return array Statistics
 */
function order_management_get_packing_stats($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    $where = ["fulfillment_status = 'packing'"];
    $params = [];
    $types = '';
    
    if (isset($filters['warehouse_id'])) {
        $where[] = "warehouse_id = ?";
        $params[] = $filters['warehouse_id'];
        $types .= 'i';
    }
    
    if (isset($filters['date'])) {
        $where[] = "DATE(packing_date) = ?";
        $params[] = $filters['date'];
        $types .= 's';
    }
    
    $whereClause = implode(' AND ', $where);
    
    $stats = [
        'total_packing' => 0,
        'completed_today' => 0,
        'avg_packing_time' => 0
    ];
    
    // Total in packing
    $query = "SELECT COUNT(*) as count FROM {$tableName} WHERE {$whereClause}";
    $stmt = $conn->prepare($query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_packing'] = $row['count'] ?? 0;
        $stmt->close();
    } elseif (empty($params)) {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_packing'] = $row['count'] ?? 0;
        }
    }
    
    // Completed today
    $whereCompleted = array_merge($where, ["DATE(packing_date) = CURDATE()", "fulfillment_status = 'shipped'"]);
    $query = "SELECT COUNT(*) as count FROM {$tableName} WHERE " . implode(' AND ', $whereCompleted);
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['completed_today'] = $row['count'] ?? 0;
    }
    
    return $stats;
}

