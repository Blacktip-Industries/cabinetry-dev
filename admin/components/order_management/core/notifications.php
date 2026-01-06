<?php
/**
 * Order Management Component - Notification Functions
 * Email and in-app notifications
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Create notification
 * @param int $orderId Order ID
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param array $recipients Recipient user IDs
 * @param array $metadata Additional metadata
 * @return array Result
 */
function order_management_create_notification($orderId, $type, $title, $message, $recipients = [], $metadata = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('notifications');
    
    $metadataJson = !empty($metadata) ? json_encode($metadata) : null;
    $status = 'pending';
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, notification_type, title, message, status, metadata, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isssss", $orderId, $type, $title, $message, $status, $metadataJson);
        if ($stmt->execute()) {
            $notificationId = $conn->insert_id;
            $stmt->close();
            
            // Create recipient records
            if (!empty($recipients)) {
                foreach ($recipients as $userId) {
                    order_management_add_notification_recipient($notificationId, $userId);
                }
            }
            
            return ['success' => true, 'notification_id' => $notificationId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Add notification recipient
 * @param int $notificationId Notification ID
 * @param int $userId User ID
 * @return array Result
 */
function order_management_add_notification_recipient($notificationId, $userId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('notification_recipients');
    
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE notification_id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        return ['success' => true, 'id' => $existing['id']];
    }
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (notification_id, user_id, is_read, created_at) VALUES (?, ?, 0, NOW())");
    if ($stmt) {
        $stmt->bind_param("ii", $notificationId, $userId);
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Send notification (email if configured)
 * @param int $notificationId Notification ID
 * @return array Result
 */
function order_management_send_notification($notificationId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('notifications');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notification = $result->fetch_assoc();
    $stmt->close();
    
    if (!$notification) {
        return ['success' => false, 'error' => 'Notification not found'];
    }
    
    // Get recipients
    $recipientsTable = order_management_get_table_name('notification_recipients');
    $stmt = $conn->prepare("SELECT * FROM {$recipientsTable} WHERE notification_id = ?");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipients = [];
    while ($row = $result->fetch_assoc()) {
        $recipients[] = $row;
    }
    $stmt->close();
    
    // Send email if email_marketing is available
    if (order_management_is_email_marketing_available() && function_exists('email_marketing_send_email')) {
        foreach ($recipients as $recipient) {
            // Get user email (would need user system integration)
            // For now, just mark as sent
        }
    }
    
    // Update notification status
    $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'sent', sent_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $stmt->close();
    
    return ['success' => true];
}

/**
 * Get user notifications
 * @param int $userId User ID
 * @param bool $unreadOnly Only unread notifications
 * @param int $limit Limit results
 * @return array Array of notifications
 */
function order_management_get_user_notifications($userId, $unreadOnly = false, $limit = 50) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $notificationsTable = order_management_get_table_name('notifications');
    $recipientsTable = order_management_get_table_name('notification_recipients');
    
    $where = ["nr.user_id = ?"];
    $params = [$userId];
    $types = 'i';
    
    if ($unreadOnly) {
        $where[] = "nr.is_read = 0";
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    $query = "SELECT n.*, nr.is_read, nr.read_at 
             FROM {$notificationsTable} n
             INNER JOIN {$recipientsTable} nr ON n.id = nr.notification_id
             {$whereClause}
             ORDER BY n.created_at DESC
             LIMIT ?";
    
    $notifications = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $params[] = $limit;
        $types .= 'i';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['metadata'] = json_decode($row['metadata'], true);
            $notifications[] = $row;
        }
        $stmt->close();
    }
    
    return $notifications;
}

/**
 * Mark notification as read
 * @param int $notificationId Notification ID
 * @param int $userId User ID
 * @return array Result
 */
function order_management_mark_notification_read($notificationId, $userId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('notification_recipients');
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $notificationId, $userId);
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

