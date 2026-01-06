<?php
/**
 * Order Management Component - Fulfillment Functions
 * Fulfillment management and operations
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get fulfillment by ID
 * @param int $fulfillmentId Fulfillment ID
 * @return array|null Fulfillment data
 */
function order_management_get_fulfillment($fulfillmentId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $fulfillmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $fulfillment = $result->fetch_assoc();
        $stmt->close();
        return $fulfillment;
    }
    
    return null;
}

/**
 * Get fulfillments for order
 * @param int $orderId Order ID
 * @return array Array of fulfillments
 */
function order_management_get_order_fulfillments($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE order_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $fulfillments = [];
        while ($row = $result->fetch_assoc()) {
            $fulfillments[] = $row;
        }
        $stmt->close();
        return $fulfillments;
    }
    
    return [];
}

/**
 * Create fulfillment for order
 * @param int $orderId Order ID
 * @param array $data Fulfillment data
 * @return array Result with fulfillment ID
 */
function order_management_create_fulfillment($orderId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    
    $warehouseId = $data['warehouse_id'] ?? null;
    $shippingMethod = $data['shipping_method'] ?? null;
    $trackingNumber = $data['tracking_number'] ?? null;
    $fulfillmentNotes = $data['fulfillment_notes'] ?? null;
    $status = $data['fulfillment_status'] ?? 'pending';
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, fulfillment_status, warehouse_id, shipping_method, tracking_number, fulfillment_notes) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isiss", $orderId, $status, $warehouseId, $shippingMethod, $trackingNumber, $fulfillmentNotes);
        if ($stmt->execute()) {
            $fulfillmentId = $conn->insert_id;
            $stmt->close();
            
            // Create fulfillment items if provided
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    order_management_add_fulfillment_item($fulfillmentId, $item);
                }
            }
            
            return ['success' => true, 'fulfillment_id' => $fulfillmentId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update fulfillment
 * @param int $fulfillmentId Fulfillment ID
 * @param array $data Fulfillment data
 * @return array Result
 */
function order_management_update_fulfillment($fulfillmentId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['fulfillment_status'])) {
        $updates[] = "fulfillment_status = ?";
        $params[] = $data['fulfillment_status'];
        $types .= 's';
        
        // Set date based on status
        if ($data['fulfillment_status'] === 'picking') {
            $updates[] = "picking_date = NOW()";
        } elseif ($data['fulfillment_status'] === 'packing') {
            $updates[] = "packing_date = NOW()";
        } elseif ($data['fulfillment_status'] === 'shipped') {
            $updates[] = "shipping_date = NOW()";
        } elseif ($data['fulfillment_status'] === 'delivered') {
            $updates[] = "delivered_date = NOW()";
        }
    }
    
    if (isset($data['warehouse_id'])) {
        $updates[] = "warehouse_id = ?";
        $params[] = $data['warehouse_id'];
        $types .= 'i';
    }
    
    if (isset($data['shipping_method'])) {
        $updates[] = "shipping_method = ?";
        $params[] = $data['shipping_method'];
        $types .= 's';
    }
    
    if (isset($data['tracking_number'])) {
        $updates[] = "tracking_number = ?";
        $params[] = $data['tracking_number'];
        $types .= 's';
    }
    
    if (isset($data['fulfillment_notes'])) {
        $updates[] = "fulfillment_notes = ?";
        $params[] = $data['fulfillment_notes'];
        $types .= 's';
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $fulfillmentId;
    $types .= 'i';
    
    $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get fulfillment items
 * @param int $fulfillmentId Fulfillment ID
 * @return array Array of fulfillment items
 */
function order_management_get_fulfillment_items($fulfillmentId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('fulfillment_items');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE fulfillment_id = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $fulfillmentId);
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
 * Add item to fulfillment
 * @param int $fulfillmentId Fulfillment ID
 * @param array $itemData Item data
 * @return array Result
 */
function order_management_add_fulfillment_item($fulfillmentId, $itemData) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('fulfillment_items');
    
    $orderItemId = $itemData['order_item_id'] ?? null;
    $productId = $itemData['product_id'] ?? null;
    $variantId = $itemData['variant_id'] ?? null;
    $quantityFulfilled = $itemData['quantity_fulfilled'] ?? 1;
    $locationPickedFrom = $itemData['location_picked_from'] ?? null;
    $barcodeData = $itemData['barcode_data'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (fulfillment_id, order_item_id, product_id, variant_id, quantity_fulfilled, location_picked_from, barcode_data) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiiiiss", $fulfillmentId, $orderItemId, $productId, $variantId, $quantityFulfilled, $locationPickedFrom, $barcodeData);
        if ($stmt->execute()) {
            $itemId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'item_id' => $itemId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update fulfillment item
 * @param int $itemId Item ID
 * @param array $data Item data
 * @return array Result
 */
function order_management_update_fulfillment_item($itemId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('fulfillment_items');
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['quantity_fulfilled'])) {
        $updates[] = "quantity_fulfilled = ?";
        $params[] = (int)$data['quantity_fulfilled'];
        $types .= 'i';
    }
    
    if (isset($data['location_picked_from'])) {
        $updates[] = "location_picked_from = ?";
        $params[] = $data['location_picked_from'];
        $types .= 's';
    }
    
    if (isset($data['barcode_data'])) {
        $updates[] = "barcode_data = ?";
        $params[] = $data['barcode_data'];
        $types .= 's';
    }
    
    if (isset($data['scanned_at'])) {
        $updates[] = "scanned_at = NOW()";
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $params[] = $itemId;
    $types .= 'i';
    
    $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Scan barcode for fulfillment item
 * @param int $fulfillmentId Fulfillment ID
 * @param string $barcode Barcode data
 * @return array Result
 */
function order_management_scan_fulfillment_barcode($fulfillmentId, $barcode) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Find item by barcode
    $itemsTable = order_management_get_table_name('fulfillment_items');
    $stmt = $conn->prepare("SELECT * FROM {$itemsTable} WHERE fulfillment_id = ? AND (barcode_data = ? OR barcode_data IS NULL) LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("is", $fulfillmentId, $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        
        if ($item) {
            // Update item with barcode and scan time
            return order_management_update_fulfillment_item($item['id'], [
                'barcode_data' => $barcode,
                'scanned_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            return ['success' => false, 'error' => 'Item not found for barcode'];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get fulfillments by status
 * @param string $status Fulfillment status
 * @param array $filters Additional filters
 * @return array Array of fulfillments
 */
function order_management_get_fulfillments_by_status($status, $filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    $where = ["fulfillment_status = ?"];
    $params = [$status];
    $types = 's';
    
    if (isset($filters['warehouse_id'])) {
        $where[] = "warehouse_id = ?";
        $params[] = $filters['warehouse_id'];
        $types .= 'i';
    }
    
    $whereClause = implode(' AND ', $where);
    $query = "SELECT * FROM {$tableName} WHERE {$whereClause} ORDER BY created_at ASC";
    
    $fulfillments = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $fulfillments[] = $row;
        }
        $stmt->close();
    }
    
    return $fulfillments;
}

