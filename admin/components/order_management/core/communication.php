<?php
/**
 * Order Management Component - Communication Functions
 * Internal and external communication tracking
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Create communication entry
 * @param int $orderId Order ID
 * @param string $type Communication type (email, phone, note, etc.)
 * @param string $direction Direction (inbound, outbound)
 * @param string $subject Subject
 * @param string $content Content
 * @param int $userId User ID
 * @return array Result
 */
function order_management_create_communication($orderId, $type, $direction, $subject, $content, $userId = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('communications');
    
    $userId = $userId ?? ($_SESSION['user_id'] ?? null);
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, communication_type, direction, subject, content, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("issssi", $orderId, $type, $direction, $subject, $content, $userId);
        if ($stmt->execute()) {
            $communicationId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'communication_id' => $communicationId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get communications for order
 * @param int $orderId Order ID
 * @return array Array of communications
 */
function order_management_get_order_communications($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('communications');
    $query = "SELECT * FROM {$tableName} WHERE order_id = ? ORDER BY created_at DESC";
    
    $communications = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $communications[] = $row;
        }
        $stmt->close();
    }
    
    return $communications;
}

