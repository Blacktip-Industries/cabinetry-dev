<?php
/**
 * Access Component - Notifications Functions
 * Handles system notifications
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Create notification
 * @param array $notificationData Notification data
 * @return int|false Notification ID on success, false on failure
 */
function access_create_notification($notificationData) {
    // Check if notifications are enabled
    if (access_get_parameter('Notifications', 'enable_notifications', 'yes') !== 'yes') {
        return false;
    }
    
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $expiresAt = !empty($notificationData['expires_at']) ? $notificationData['expires_at'] : null;
        
        $stmt = $conn->prepare("INSERT INTO access_notifications (user_id, account_id, notification_type, title, message, action_url, priority, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssss",
            $notificationData['user_id'] ?? null,
            $notificationData['account_id'] ?? null,
            $notificationData['notification_type'] ?? 'system',
            $notificationData['title'],
            $notificationData['message'],
            $notificationData['action_url'] ?? null,
            $notificationData['priority'] ?? 'normal',
            $expiresAt
        );
        
        if ($stmt->execute()) {
            $notificationId = $conn->insert_id;
            $stmt->close();
            return $notificationId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get notifications
 * @param array $filters Filters (user_id, account_id, notification_type, is_read, priority, limit, offset)
 * @return array Notifications list
 */
function access_get_notifications($filters = []) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['user_id'])) {
        $where[] = "(n.user_id = ? OR n.user_id IS NULL)";
        $params[] = (int)$filters['user_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['account_id'])) {
        $where[] = "(n.account_id = ? OR n.account_id IS NULL)";
        $params[] = (int)$filters['account_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['notification_type'])) {
        $where[] = "n.notification_type = ?";
        $params[] = $filters['notification_type'];
        $types .= 's';
    }
    
    if (isset($filters['is_read'])) {
        $where[] = "n.is_read = ?";
        $params[] = (int)$filters['is_read'];
        $types .= 'i';
    }
    
    if (!empty($filters['priority'])) {
        $where[] = "n.priority = ?";
        $params[] = $filters['priority'];
        $types .= 's';
    }
    
    // Exclude expired notifications
    $where[] = "(n.expires_at IS NULL OR n.expires_at > NOW())";
    
    $sql = "SELECT * FROM access_notifications n";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY n.created_at DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = (int)$filters['limit'];
        $types .= 'i';
        
        if (!empty($filters['offset'])) {
            $sql .= " OFFSET ?";
            $params[] = (int)$filters['offset'];
            $types .= 'i';
        }
    }
    
    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
        return $notifications;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read
 * @param int $notificationId Notification ID
 * @return bool Success
 */
function access_mark_notification_read($notificationId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE access_notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for user
 * @param int $userId User ID
 * @param int|null $accountId Account ID (optional)
 * @return bool Success
 */
function access_mark_all_notifications_read($userId, $accountId = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        if ($accountId !== null) {
            $stmt = $conn->prepare("UPDATE access_notifications SET is_read = 1, read_at = NOW() WHERE (user_id = ? OR user_id IS NULL) AND (account_id = ? OR account_id IS NULL) AND is_read = 0");
            $stmt->bind_param("ii", $userId, $accountId);
        } else {
            $stmt = $conn->prepare("UPDATE access_notifications SET is_read = 1, read_at = NOW() WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
            $stmt->bind_param("i", $userId);
        }
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count
 * @param int $userId User ID
 * @param int|null $accountId Account ID (optional)
 * @return int Unread count
 */
function access_get_unread_notification_count($userId, $accountId = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        if ($accountId !== null) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM access_notifications WHERE (user_id = ? OR user_id IS NULL) AND (account_id = ? OR account_id IS NULL) AND is_read = 0 AND (expires_at IS NULL OR expires_at > NOW())");
            $stmt->bind_param("ii", $userId, $accountId);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM access_notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0 AND (expires_at IS NULL OR expires_at > NOW())");
            $stmt->bind_param("i", $userId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0);
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting unread notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Broadcast notification to all users/accounts
 * @param array $notificationData Notification data
 * @param array $options Options (user_id filter, account_id filter)
 * @return int Number of notifications created
 */
function access_broadcast_notification($notificationData, $options = []) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    $count = 0;
    
    try {
        // If user_id is specified in options, send to that user only
        if (!empty($options['user_id'])) {
            $notificationData['user_id'] = $options['user_id'];
            if (access_create_notification($notificationData)) {
                $count = 1;
            }
        } else {
            // Broadcast to all active users
            $users = access_list_users(['status' => 'active']);
            foreach ($users as $user) {
                $notificationData['user_id'] = $user['id'];
                if (access_create_notification($notificationData)) {
                    $count++;
                }
            }
        }
    } catch (Exception $e) {
        error_log("Access: Error broadcasting notification: " . $e->getMessage());
    }
    
    return $count;
}

/**
 * Delete notification
 * @param int $notificationId Notification ID
 * @return bool Success
 */
function access_delete_notification($notificationId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM access_notifications WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error deleting notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean expired notifications
 * @return int Number of notifications deleted
 */
function access_clean_expired_notifications() {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM access_notifications WHERE expires_at IS NOT NULL AND expires_at < NOW()");
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        return $deleted;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error cleaning expired notifications: " . $e->getMessage());
        return 0;
    }
}

