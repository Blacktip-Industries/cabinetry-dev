<?php
/**
 * Order Management Component - Shipping Functions
 * Shipping integration and tracking
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/fulfillment.php';
require_once __DIR__ . '/functions.php';

/**
 * Ship fulfillment
 * @param int $fulfillmentId Fulfillment ID
 * @param array $data Shipping data (tracking_number, shipping_method, carrier)
 * @return array Result
 */
function order_management_ship_fulfillment($fulfillmentId, $data) {
    $updateData = [
        'fulfillment_status' => 'shipped',
        'tracking_number' => $data['tracking_number'] ?? null,
        'shipping_method' => $data['shipping_method'] ?? null
    ];
    
    $result = order_management_update_fulfillment($fulfillmentId, $updateData);
    
    if ($result['success'] && !empty($data['tracking_number'])) {
        // Create tracking record if commerce component is available
        if (order_management_is_commerce_available()) {
            $conn = order_management_get_db_connection();
            $fulfillment = order_management_get_fulfillment($fulfillmentId);
            
            // Check if commerce_shipments table exists
            $result = $conn->query("SHOW TABLES LIKE 'commerce_shipments'");
            if ($result && $result->num_rows > 0) {
                // Create or update shipment record
                $stmt = $conn->prepare("INSERT INTO commerce_shipments (order_id, tracking_number, shipping_method, status, shipped_at) VALUES (?, ?, ?, 'shipped', NOW()) ON DUPLICATE KEY UPDATE tracking_number = ?, shipping_method = ?, shipped_at = NOW()");
                $shippingMethod = $updateData['shipping_method'] ?? 'standard';
                $stmt->bind_param("issss", $fulfillment['order_id'], $updateData['tracking_number'], $shippingMethod, $updateData['tracking_number'], $shippingMethod);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    return $result;
}

/**
 * Mark fulfillment as delivered
 * @param int $fulfillmentId Fulfillment ID
 * @return array Result
 */
function order_management_mark_delivered($fulfillmentId) {
    return order_management_update_fulfillment($fulfillmentId, [
        'fulfillment_status' => 'delivered'
    ]);
}

/**
 * Get shipping tracking information
 * @param string $trackingNumber Tracking number
 * @return array Tracking information
 */
function order_management_get_tracking_info($trackingNumber) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE tracking_number = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $trackingNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $fulfillment = $result->fetch_assoc();
        $stmt->close();
        
        if ($fulfillment) {
            return [
                'tracking_number' => $fulfillment['tracking_number'],
                'status' => $fulfillment['fulfillment_status'],
                'shipping_method' => $fulfillment['shipping_method'],
                'shipped_date' => $fulfillment['shipping_date'],
                'delivered_date' => $fulfillment['delivered_date'],
                'order_id' => $fulfillment['order_id']
            ];
        }
    }
    
    return null;
}

/**
 * Get shipping statistics
 * @param array $filters Filters (date, warehouse_id, shipping_method)
 * @return array Statistics
 */
function order_management_get_shipping_stats($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['warehouse_id'])) {
        $where[] = "warehouse_id = ?";
        $params[] = $filters['warehouse_id'];
        $types .= 'i';
    }
    
    if (isset($filters['shipping_method'])) {
        $where[] = "shipping_method = ?";
        $params[] = $filters['shipping_method'];
        $types .= 's';
    }
    
    if (isset($filters['date'])) {
        $where[] = "DATE(shipping_date) = ?";
        $params[] = $filters['date'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stats = [
        'total_shipped' => 0,
        'total_delivered' => 0,
        'pending_delivery' => 0,
        'shipped_today' => 0
    ];
    
    // Total shipped
    $query = "SELECT COUNT(*) as count FROM {$tableName} {$whereClause} AND fulfillment_status = 'shipped'";
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_shipped'] = $row['count'] ?? 0;
        $stmt->close();
    } else {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_shipped'] = $row['count'] ?? 0;
        }
    }
    
    // Total delivered
    $query = "SELECT COUNT(*) as count FROM {$tableName} {$whereClause} AND fulfillment_status = 'delivered'";
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_delivered'] = $row['count'] ?? 0;
        $stmt->close();
    } else {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_delivered'] = $row['count'] ?? 0;
        }
    }
    
    // Pending delivery (shipped but not delivered)
    $stats['pending_delivery'] = $stats['total_shipped'] - $stats['total_delivered'];
    
    // Shipped today
    $whereToday = array_merge($where, ["DATE(shipping_date) = CURDATE()", "fulfillment_status = 'shipped'"]);
    $whereClauseToday = 'WHERE ' . implode(' AND ', $whereToday);
    $query = "SELECT COUNT(*) as count FROM {$tableName} {$whereClauseToday}";
    if (!empty($params)) {
        $paramsToday = array_merge($params, [date('Y-m-d')]);
        $typesToday = $types . 's';
        $stmt = $conn->prepare($query);
        $stmt->bind_param($typesToday, ...$paramsToday);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['shipped_today'] = $row['count'] ?? 0;
        $stmt->close();
    } else {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['shipped_today'] = $row['count'] ?? 0;
        }
    }
    
    return $stats;
}

/**
 * Generate shipping label (placeholder - would integrate with carrier APIs)
 * @param int $fulfillmentId Fulfillment ID
 * @param array $options Label options
 * @return array Result with label data
 */
function order_management_generate_shipping_label($fulfillmentId, $options = []) {
    $fulfillment = order_management_get_fulfillment($fulfillmentId);
    if (!$fulfillment) {
        return ['success' => false, 'error' => 'Fulfillment not found'];
    }
    
    // This would integrate with carrier APIs (USPS, FedEx, UPS, etc.)
    // For now, return placeholder
    return [
        'success' => true,
        'label_url' => '/admin/components/order_management/admin/fulfillment/print-label.php?id=' . $fulfillmentId,
        'tracking_number' => $fulfillment['tracking_number'] ?? null,
        'message' => 'Label generation would integrate with carrier API'
    ];
}

