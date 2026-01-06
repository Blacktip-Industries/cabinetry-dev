<?php
/**
 * Access Component - Messaging Functions
 * Handles message sending, receiving, attachments, and threading
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/audit.php';

/**
 * Send message to user/account
 * @param array $messageData Message data
 * @return int|false Message ID on success, false on failure
 */
function access_send_message($messageData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $metadata = isset($messageData['metadata']) ? (is_string($messageData['metadata']) ? $messageData['metadata'] : json_encode($messageData['metadata'])) : null;
        
        $stmt = $conn->prepare("INSERT INTO access_messages (from_user_id, to_user_id, account_id, message_type, subject, message, related_entity_type, related_entity_id, priority, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissssiss",
            $messageData['from_user_id'],
            $messageData['to_user_id'] ?? null,
            $messageData['account_id'] ?? null,
            $messageData['message_type'] ?? 'general',
            $messageData['subject'] ?? null,
            $messageData['message'],
            $messageData['related_entity_type'] ?? null,
            $messageData['related_entity_id'] ?? null,
            $messageData['priority'] ?? 'normal',
            $metadata
        );
        
        if ($stmt->execute()) {
            $messageId = $conn->insert_id;
            $stmt->close();
            
            // Create notification
            if (!empty($messageData['to_user_id'])) {
                access_create_notification([
                    'user_id' => $messageData['to_user_id'],
                    'account_id' => $messageData['account_id'] ?? null,
                    'notification_type' => 'message',
                    'title' => 'New Message: ' . ($messageData['subject'] ?? 'No Subject'),
                    'message' => substr($messageData['message'], 0, 200),
                    'action_url' => '/admin/components/access/frontend/messaging/view.php?id=' . $messageId
                ]);
            }
            
            // Log audit
            access_log_audit('message', $messageId, 'create', null, $messageData, $messageData['from_user_id'] ?? null);
            
            return $messageId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error sending message: " . $e->getMessage());
        return false;
    }
}

/**
 * Get messages with filters
 * @param array $filters Filters (to_user_id, from_user_id, account_id, message_type, is_read, is_archived, related_entity_type, related_entity_id, limit, offset)
 * @return array Messages list
 */
function access_get_messages($filters = []) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['to_user_id'])) {
        $where[] = "m.to_user_id = ?";
        $params[] = (int)$filters['to_user_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['from_user_id'])) {
        $where[] = "m.from_user_id = ?";
        $params[] = (int)$filters['from_user_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['account_id'])) {
        $where[] = "m.account_id = ?";
        $params[] = (int)$filters['account_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['message_type'])) {
        $where[] = "m.message_type = ?";
        $params[] = $filters['message_type'];
        $types .= 's';
    }
    
    if (isset($filters['is_read'])) {
        $where[] = "m.is_read = ?";
        $params[] = (int)$filters['is_read'];
        $types .= 'i';
    }
    
    if (isset($filters['is_archived'])) {
        $where[] = "m.is_archived = ?";
        $params[] = (int)$filters['is_archived'];
        $types .= 'i';
    }
    
    if (!empty($filters['related_entity_type'])) {
        $where[] = "m.related_entity_type = ?";
        $params[] = $filters['related_entity_type'];
        $types .= 's';
        
        if (!empty($filters['related_entity_id'])) {
            $where[] = "m.related_entity_id = ?";
            $params[] = (int)$filters['related_entity_id'];
            $types .= 'i';
        }
    }
    
    $sql = "SELECT m.*, 
                   fu.email as from_email, fu.first_name as from_first_name, fu.last_name as from_last_name,
                   tu.email as to_email, tu.first_name as to_first_name, tu.last_name as to_last_name,
                   a.account_name
            FROM access_messages m
            LEFT JOIN access_users fu ON m.from_user_id = fu.id
            LEFT JOIN access_users tu ON m.to_user_id = tu.id
            LEFT JOIN access_accounts a ON m.account_id = a.id";
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY m.created_at DESC";
    
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
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
        return $messages;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting messages: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark message as read
 * @param int $messageId Message ID
 * @param int $userId User ID who read it
 * @return bool Success
 */
function access_mark_message_read($messageId, $userId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE access_messages SET is_read = 1, read_at = NOW() WHERE id = ? AND to_user_id = ?");
        $stmt->bind_param("ii", $messageId, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error marking message as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Archive message
 * @param int $messageId Message ID
 * @return bool Success
 */
function access_archive_message($messageId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE access_messages SET is_archived = 1 WHERE id = ?");
        $stmt->bind_param("i", $messageId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error archiving message: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread message count
 * @param int $userId User ID
 * @param int|null $accountId Account ID (optional)
 * @return int Unread count
 */
function access_get_unread_message_count($userId, $accountId = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        if ($accountId !== null) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM access_messages WHERE to_user_id = ? AND account_id = ? AND is_read = 0 AND is_archived = 0");
            $stmt->bind_param("ii", $userId, $accountId);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM access_messages WHERE to_user_id = ? AND is_read = 0 AND is_archived = 0");
            $stmt->bind_param("i", $userId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0);
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting unread count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Attach file to message
 * @param int $messageId Message ID
 * @param array $fileData File data (file_name, file_path, file_size, mime_type, uploaded_by)
 * @return int|false Attachment ID on success, false on failure
 */
function access_attach_file_to_message($messageId, $fileData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_message_attachments (message_id, file_name, file_path, file_size, mime_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issisi",
            $messageId,
            $fileData['file_name'],
            $fileData['file_path'],
            $fileData['file_size'],
            $fileData['mime_type'] ?? null,
            $fileData['uploaded_by'] ?? null
        );
        
        if ($stmt->execute()) {
            $attachmentId = $conn->insert_id;
            $stmt->close();
            return $attachmentId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error attaching file to message: " . $e->getMessage());
        return false;
    }
}

/**
 * Get message attachments
 * @param int $messageId Message ID
 * @return array Attachments list
 */
function access_get_message_attachments($messageId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_message_attachments WHERE message_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $attachments = [];
        while ($row = $result->fetch_assoc()) {
            $attachments[] = $row;
        }
        $stmt->close();
        return $attachments;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting message attachments: " . $e->getMessage());
        return [];
    }
}

/**
 * Get message by ID
 * @param int $messageId Message ID
 * @return array|null Message data or null
 */
function access_get_message($messageId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT m.*, 
                                       fu.email as from_email, fu.first_name as from_first_name, fu.last_name as from_last_name,
                                       tu.email as to_email, tu.first_name as to_first_name, tu.last_name as to_last_name,
                                       a.account_name
                                FROM access_messages m
                                LEFT JOIN access_users fu ON m.from_user_id = fu.id
                                LEFT JOIN access_users tu ON m.to_user_id = tu.id
                                LEFT JOIN access_accounts a ON m.account_id = a.id
                                WHERE m.id = ?");
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $message = $result->fetch_assoc();
        $stmt->close();
        return $message;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting message: " . $e->getMessage());
        return null;
    }
}

/**
 * Delete message
 * @param int $messageId Message ID
 * @return bool Success
 */
function access_delete_message($messageId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM access_messages WHERE id = ?");
        $stmt->bind_param("i", $messageId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error deleting message: " . $e->getMessage());
        return false;
    }
}

