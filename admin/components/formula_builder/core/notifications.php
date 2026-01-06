<?php
/**
 * Formula Builder Component - Notifications System
 * Notification queue and delivery
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Send notification
 * @param int $userId User ID
 * @param string $notificationType Notification type
 * @param string $channel Channel (email, in_app, sms, push)
 * @param string $message Message
 * @return array Result
 */
function formula_builder_send_notification($userId, $notificationType, $channel, $message) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Check user preferences
    if (!formula_builder_is_notification_enabled($userId, $notificationType, $channel)) {
        return ['success' => false, 'error' => 'Notification disabled by user preference'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('notifications');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (user_id, notification_type, channel, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $notificationType, $channel, $message);
        $stmt->execute();
        $notificationId = $conn->insert_id;
        $stmt->close();
        
        // Send via appropriate channel
        if ($channel === 'email') {
            formula_builder_send_email_notification($userId, $message);
        } elseif ($channel === 'sms') {
            formula_builder_send_sms_notification($userId, $message);
        } elseif ($channel === 'push') {
            formula_builder_send_push_notification($userId, $message);
        }
        // in_app notifications are stored in database and retrieved via get_notifications
        
        return ['success' => true, 'notification_id' => $notificationId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error sending notification: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get notifications
 * @param int $userId User ID
 * @param array $filters Filter options
 * @return array Notifications
 */
function formula_builder_get_notifications($userId, $filters = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('notifications');
        
        $where = ["user_id = ?"];
        $params = [$userId];
        $types = 'i';
        
        if (isset($filters['read']) && $filters['read'] !== null) {
            $where[] = "read = ?";
            $params[] = $filters['read'] ? 1 : 0;
            $types .= 'i';
        }
        
        if (!empty($filters['notification_type'])) {
            $where[] = "notification_type = ?";
            $params[] = $filters['notification_type'];
            $types .= 's';
        }
        
        if (!empty($filters['channel'])) {
            $where[] = "channel = ?";
            $params[] = $filters['channel'];
            $types .= 's';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $orderBy = 'ORDER BY created_at DESC';
        $limit = isset($filters['limit']) ? 'LIMIT ' . (int)$filters['limit'] : 'LIMIT 50';
        
        $query = "SELECT * FROM {$tableName} {$whereClause} {$orderBy} {$limit}";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        $stmt->close();
        return $notifications;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read
 * @param int $notificationId Notification ID
 * @param int $userId User ID
 * @return array Result
 */
function formula_builder_mark_notification_read($notificationId, $userId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('notifications');
        $stmt = $conn->prepare("UPDATE {$tableName} SET read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notificationId, $userId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error marking notification read: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Check if notification is enabled for user
 * @param int $userId User ID
 * @param string $notificationType Notification type
 * @param string $channel Channel
 * @return bool True if enabled
 */
function formula_builder_is_notification_enabled($userId, $notificationType, $channel) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return true; // Default to enabled if can't check
    }
    
    try {
        $tableName = formula_builder_get_table_name('notification_preferences');
        $stmt = $conn->prepare("SELECT enabled FROM {$tableName} WHERE user_id = ? AND notification_type = ? AND channel = ? LIMIT 1");
        $stmt->bind_param("iss", $userId, $notificationType, $channel);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? (bool)$row['enabled'] : true; // Default to enabled
    } catch (Exception $e) {
        return true; // Default to enabled on error
    }
}

/**
 * Set notification preference
 * @param int $userId User ID
 * @param string $notificationType Notification type
 * @param string $channel Channel
 * @param bool $enabled Enabled status
 * @param string $frequency Frequency (immediate, digest_daily, digest_weekly)
 * @return array Result
 */
function formula_builder_set_notification_preference($userId, $notificationType, $channel, $enabled, $frequency = 'immediate') {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('notification_preferences');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (user_id, notification_type, channel, enabled, frequency) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE enabled = ?, frequency = ?, updated_at = NOW()");
        $enabledInt = $enabled ? 1 : 0;
        $stmt->bind_param("isssisss", $userId, $notificationType, $channel, $enabledInt, $frequency, $enabledInt, $frequency);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error setting notification preference: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send email notification (placeholder - integrate with email system)
 * @param int $userId User ID
 * @param string $message Message
 * @return bool Success
 */
function formula_builder_send_email_notification($userId, $message) {
    // TODO: Integrate with email system
    error_log("Formula Builder: Email notification to user {$userId}: {$message}");
    return true;
}

/**
 * Send SMS notification (placeholder - integrate with SMS service)
 * @param int $userId User ID
 * @param string $message Message
 * @return bool Success
 */
function formula_builder_send_sms_notification($userId, $message) {
    // TODO: Integrate with SMS service
    error_log("Formula Builder: SMS notification to user {$userId}: {$message}");
    return true;
}

/**
 * Send push notification (placeholder - integrate with push service)
 * @param int $userId User ID
 * @param string $message Message
 * @return bool Success
 */
function formula_builder_send_push_notification($userId, $message) {
    // TODO: Integrate with push notification service
    error_log("Formula Builder: Push notification to user {$userId}: {$message}");
    return true;
}

