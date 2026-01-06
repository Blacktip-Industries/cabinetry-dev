<?php
/**
 * Order Management Component - Migration Functions
 * Hybrid migration system for commerce orders
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if order is migrated
 * @param int $orderId Order ID
 * @return bool True if migrated
 */
function order_management_is_order_migrated($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('migration_status');
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE order_id = ? AND migration_status = 'completed' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    return false;
}

/**
 * Migrate order (lazy migration on first access)
 * @param int $orderId Order ID
 * @return array Result
 */
function order_management_migrate_order($orderId) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Check if already migrated
    if (order_management_is_order_migrated($orderId)) {
        return ['success' => true, 'message' => 'Order already migrated'];
    }
    
    // Get order from commerce
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found'];
    }
    
    // Create status history entry
    $statusHistoryTable = order_management_get_table_name('status_history');
    $stmt = $conn->prepare("INSERT INTO {$statusHistoryTable} (order_id, from_status, to_status, changed_by, created_at) VALUES (?, 'new', ?, NULL, ?)");
    $toStatus = $order['status'] ?? 'pending';
    $createdAt = $order['created_at'] ?? date('Y-m-d H:i:s');
    $stmt->bind_param("iss", $orderId, $toStatus, $createdAt);
    $stmt->execute();
    $stmt->close();
    
    // Mark as migrated
    $tableName = order_management_get_table_name('migration_status');
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, migration_status, migrated_at) VALUES (?, 'completed', NOW())");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $stmt->close();
    
    return ['success' => true, 'message' => 'Order migrated successfully'];
}

/**
 * Batch migrate orders
 * @param int $limit Number of orders to migrate
 * @param int $offset Offset
 * @return array Result
 */
function order_management_batch_migrate_orders($limit = 100, $offset = 0) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get orders not yet migrated
    $tableName = order_management_get_table_name('migration_status');
    $query = "SELECT o.id FROM commerce_orders o
             LEFT JOIN {$tableName} m ON o.id = m.order_id AND m.migration_status = 'completed'
             WHERE m.id IS NULL
             ORDER BY o.id ASC
             LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $orderIds = [];
    while ($row = $result->fetch_assoc()) {
        $orderIds[] = $row['id'];
    }
    $stmt->close();
    
    $migrated = 0;
    $errors = [];
    
    foreach ($orderIds as $orderId) {
        $result = order_management_migrate_order($orderId);
        if ($result['success']) {
            $migrated++;
        } else {
            $errors[] = "Order {$orderId}: " . ($result['error'] ?? 'Unknown error');
        }
    }
    
    return [
        'success' => true,
        'migrated' => $migrated,
        'total' => count($orderIds),
        'errors' => $errors
    ];
}

/**
 * Get migration status
 * @return array Migration status
 */
function order_management_get_migration_status() {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Total orders
    $result = $conn->query("SELECT COUNT(*) as total FROM commerce_orders");
    $totalOrders = $result->fetch_assoc()['total'] ?? 0;
    
    // Migrated orders
    $tableName = order_management_get_table_name('migration_status');
    $result = $conn->query("SELECT COUNT(*) as migrated FROM {$tableName} WHERE migration_status = 'completed'");
    $migratedOrders = $result->fetch_assoc()['migrated'] ?? 0;
    
    $percentage = $totalOrders > 0 ? ($migratedOrders / $totalOrders) * 100 : 0;
    
    return [
        'success' => true,
        'total_orders' => $totalOrders,
        'migrated_orders' => $migratedOrders,
        'pending_orders' => $totalOrders - $migratedOrders,
        'migration_percentage' => round($percentage, 2)
    ];
}

