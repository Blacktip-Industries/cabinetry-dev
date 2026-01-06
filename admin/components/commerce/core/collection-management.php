<?php
/**
 * Commerce Component - Collection Management Functions
 * Customer-facing collection management functions
 */

require_once __DIR__ . '/database.php';

/**
 * Customer confirms collection window
 * @param int $orderId Order ID
 * @param int $customerId Customer ID
 * @return array Result
 */
function commerce_confirm_collection($orderId, $customerId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('orders');
    $confirmedAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE {$tableName} SET collection_confirmed_at = ?, collection_confirmed_by = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sii", $confirmedAt, $customerId, $orderId);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        }
        $stmt->close();
    }
    
    return ['success' => false, 'error' => 'Failed to confirm collection'];
}

/**
 * Request collection reschedule
 * @param int $orderId Order ID
 * @param string $newStart New start date/time
 * @param string $newEnd New end date/time
 * @param string|null $reason Reschedule reason
 * @param int $customerId Customer ID
 * @return array Result
 */
function commerce_request_collection_reschedule($orderId, $newStart, $newEnd, $reason, $customerId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('orders');
    
    // Check reschedule limit
    $stmt = $conn->prepare("SELECT collection_reschedule_count, collection_reschedule_limit FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if ($order) {
            $rescheduleCount = (int)$order['collection_reschedule_count'];
            $rescheduleLimit = (int)$order['collection_reschedule_limit'];
            
            if ($rescheduleCount >= $rescheduleLimit) {
                return ['success' => false, 'error' => 'Reschedule limit reached'];
            }
            
            // Update order
            $requestedAt = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("UPDATE {$tableName} SET collection_reschedule_requested_at = ?, collection_reschedule_request = ?, collection_reschedule_request_end = ?, collection_reschedule_reason = ?, collection_reschedule_status = 'pending', collection_reschedule_count = collection_reschedule_count + 1 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssssi", $requestedAt, $newStart, $newEnd, $reason, $orderId);
                if ($stmt->execute()) {
                    $stmt->close();
                    return ['success' => true];
                }
                $stmt->close();
            }
        }
    }
    
    return ['success' => false, 'error' => 'Failed to process reschedule request'];
}

/**
 * Submit collection feedback
 * @param int $orderId Order ID
 * @param int $rating Rating (1-5)
 * @param string|null $comment Comment
 * @param int $customerId Customer ID
 * @return array Result
 */
function commerce_submit_collection_feedback($orderId, $rating, $comment, $customerId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('orders');
    $stmt = $conn->prepare("UPDATE {$tableName} SET collection_feedback_rating = ?, collection_feedback_comment = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("isi", $rating, $comment, $orderId);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        }
        $stmt->close();
    }
    
    return ['success' => false, 'error' => 'Failed to submit feedback'];
}

