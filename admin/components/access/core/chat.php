<?php
/**
 * Access Component - Chat Functions
 * Handles chat sessions, real-time messaging, admin availability, and email forwarding
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/audit.php';

/**
 * Create chat session
 * @param array $chatData Chat session data
 * @return int|false Chat session ID on success, false on failure
 */
function access_create_chat_session($chatData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $metadata = isset($chatData['metadata']) ? (is_string($chatData['metadata']) ? $chatData['metadata'] : json_encode($chatData['metadata'])) : null;
        $startedAt = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO access_chat_sessions (user_id, account_id, admin_user_id, status, subject, started_at, last_message_at, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisssss",
            $chatData['user_id'],
            $chatData['account_id'],
            $chatData['admin_user_id'] ?? null,
            $chatData['status'] ?? 'waiting',
            $chatData['subject'] ?? null,
            $startedAt,
            $startedAt,
            $metadata
        );
        
        if ($stmt->execute()) {
            $chatSessionId = $conn->insert_id;
            $stmt->close();
            
            // Create notification for admins
            access_broadcast_notification([
                'notification_type' => 'chat',
                'title' => 'New Chat Session',
                'message' => 'A new chat session has been started',
                'action_url' => '/admin/components/access/admin/chat/session.php?id=' . $chatSessionId
            ], ['user_id' => null]); // Broadcast to all admins
            
            // Log audit
            access_log_audit('chat_session', $chatSessionId, 'create', null, $chatData, $chatData['user_id']);
            
            return $chatSessionId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error creating chat session: " . $e->getMessage());
        return false;
    }
}

/**
 * Send chat message
 * @param int $chatSessionId Chat session ID
 * @param array $messageData Message data
 * @return int|false Message ID on success, false on failure
 */
function access_send_chat_message($chatSessionId, $messageData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $metadata = isset($messageData['metadata']) ? (is_string($messageData['metadata']) ? $messageData['metadata'] : json_encode($messageData['metadata'])) : null;
        
        $stmt = $conn->prepare("INSERT INTO access_chat_messages (chat_session_id, sender_user_id, sender_type, message, metadata) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss",
            $chatSessionId,
            $messageData['sender_user_id'],
            $messageData['sender_type'] ?? 'user',
            $messageData['message'],
            $metadata
        );
        
        if ($stmt->execute()) {
            $messageId = $conn->insert_id;
            $stmt->close();
            
            // Update chat session last_message_at
            $updateStmt = $conn->prepare("UPDATE access_chat_sessions SET last_message_at = NOW(), status = 'active' WHERE id = ?");
            $updateStmt->bind_param("i", $chatSessionId);
            $updateStmt->execute();
            $updateStmt->close();
            
            return $messageId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error sending chat message: " . $e->getMessage());
        return false;
    }
}

/**
 * Get chat messages
 * @param int $chatSessionId Chat session ID
 * @param int|null $sinceId Get messages after this ID (for polling)
 * @return array Messages list
 */
function access_get_chat_messages($chatSessionId, $sinceId = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        if ($sinceId !== null) {
            $stmt = $conn->prepare("SELECT cm.*, u.email as sender_email, u.first_name as sender_first_name, u.last_name as sender_last_name FROM access_chat_messages cm LEFT JOIN access_users u ON cm.sender_user_id = u.id WHERE cm.chat_session_id = ? AND cm.id > ? ORDER BY cm.created_at ASC");
            $stmt->bind_param("ii", $chatSessionId, $sinceId);
        } else {
            $stmt = $conn->prepare("SELECT cm.*, u.email as sender_email, u.first_name as sender_first_name, u.last_name as sender_last_name FROM access_chat_messages cm LEFT JOIN access_users u ON cm.sender_user_id = u.id WHERE cm.chat_session_id = ? ORDER BY cm.created_at ASC");
            $stmt->bind_param("i", $chatSessionId);
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
        error_log("Access: Error getting chat messages: " . $e->getMessage());
        return [];
    }
}

/**
 * Get active chat sessions
 * @param int|null $adminUserId Admin user ID (filter by admin)
 * @param string|null $status Status filter
 * @return array Chat sessions list
 */
function access_get_active_chats($adminUserId = null, $status = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    if ($adminUserId !== null) {
        $where[] = "cs.admin_user_id = ?";
        $params[] = $adminUserId;
        $types .= 'i';
    }
    
    if ($status !== null) {
        $where[] = "cs.status = ?";
        $params[] = $status;
        $types .= 's';
    } else {
        $where[] = "cs.status IN ('waiting', 'active')";
    }
    
    $sql = "SELECT cs.*, 
                   u.email as user_email, u.first_name as user_first_name, u.last_name as user_last_name,
                   a.account_name,
                   admin.email as admin_email, admin.first_name as admin_first_name, admin.last_name as admin_last_name,
                   (SELECT COUNT(*) FROM access_chat_messages WHERE chat_session_id = cs.id AND is_read = 0) as unread_count
            FROM access_chat_sessions cs
            LEFT JOIN access_users u ON cs.user_id = u.id
            LEFT JOIN access_accounts a ON cs.account_id = a.id
            LEFT JOIN access_users admin ON cs.admin_user_id = admin.id";
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY cs.last_message_at DESC";
    
    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $chats = [];
        while ($row = $result->fetch_assoc()) {
            $chats[] = $row;
        }
        $stmt->close();
        return $chats;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting active chats: " . $e->getMessage());
        return [];
    }
}

/**
 * Close chat session
 * @param int $chatSessionId Chat session ID
 * @param int|null $closedBy User ID who closed it
 * @return bool Success
 */
function access_close_chat_session($chatSessionId, $closedBy = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $endedAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE access_chat_sessions SET status = 'closed', ended_at = ? WHERE id = ?");
        $stmt->bind_param("si", $endedAt, $chatSessionId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Check if auto-forward is enabled
            $autoForward = access_get_parameter('Chat', 'auto_forward_chat', 'no');
            if ($autoForward === 'yes') {
                $delay = (int)access_get_parameter('Chat', 'forward_delay_minutes', 0);
                if ($delay > 0) {
                    // Schedule forwarding (could use cron or immediate)
                    // For now, forward immediately
                    access_forward_chat_to_customer($chatSessionId, $closedBy);
                } else {
                    access_forward_chat_to_customer($chatSessionId, $closedBy);
                }
            }
            
            // Log audit
            access_log_audit('chat_session', $chatSessionId, 'close', null, ['closed_by' => $closedBy], $closedBy);
        }
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error closing chat session: " . $e->getMessage());
        return false;
    }
}

/**
 * Set admin availability
 * @param int $userId Admin user ID
 * @param bool $isAvailable Availability status
 * @param string|null $statusMessage Custom status message
 * @return bool Success
 */
function access_set_admin_availability($userId, $isAvailable, $statusMessage = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $lastActiveAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO access_admin_availability (user_id, is_available, status_message, last_active_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_available = VALUES(is_available), status_message = VALUES(status_message), last_active_at = VALUES(last_active_at), updated_at = CURRENT_TIMESTAMP");
        $isAvailableInt = $isAvailable ? 1 : 0;
        $stmt->bind_param("iiss", $userId, $isAvailableInt, $statusMessage, $lastActiveAt);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error setting admin availability: " . $e->getMessage());
        return false;
    }
}

/**
 * Get admin availability status
 * @param int $userId Admin user ID
 * @return array|null Availability data or null
 */
function access_get_admin_availability($userId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_admin_availability WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $availability = $result->fetch_assoc();
        $stmt->close();
        return $availability;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting admin availability: " . $e->getMessage());
        return null;
    }
}

/**
 * Get available admins
 * @return array Available admins list
 */
function access_get_available_admins() {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT aa.*, u.email, u.first_name, u.last_name FROM access_admin_availability aa INNER JOIN access_users u ON aa.user_id = u.id WHERE aa.is_available = 1 AND u.status = 'active' ORDER BY aa.last_active_at DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
        $stmt->close();
        return $admins;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting available admins: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if admin is available
 * @return bool True if at least one admin is available
 */
function access_is_admin_available() {
    $admins = access_get_available_admins();
    return !empty($admins);
}

/**
 * Assign chat to admin
 * @param int $chatSessionId Chat session ID
 * @param int $adminUserId Admin user ID
 * @return bool Success
 */
function access_assign_chat_to_admin($chatSessionId, $adminUserId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE access_chat_sessions SET admin_user_id = ?, status = 'active' WHERE id = ?");
        $stmt->bind_param("ii", $adminUserId, $chatSessionId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Update admin chat count
            $updateStmt = $conn->prepare("UPDATE access_admin_availability SET current_chat_count = current_chat_count + 1 WHERE user_id = ?");
            $updateStmt->bind_param("i", $adminUserId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Create notification for customer
            $chat = access_get_chat_session($chatSessionId);
            if ($chat) {
                access_create_notification([
                    'user_id' => $chat['user_id'],
                    'account_id' => $chat['account_id'],
                    'notification_type' => 'chat',
                    'title' => 'Chat Assigned',
                    'message' => 'An admin has joined your chat',
                    'action_url' => '/admin/components/access/frontend/chat/session.php?id=' . $chatSessionId
                ]);
            }
        }
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error assigning chat to admin: " . $e->getMessage());
        return false;
    }
}

/**
 * Forward chat transcript to customer via email
 * @param int $chatSessionId Chat session ID
 * @param int|null $forwardedBy User ID who forwarded it
 * @return bool Success
 */
function access_forward_chat_to_customer($chatSessionId, $forwardedBy = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $chat = access_get_chat_session($chatSessionId);
    if (!$chat) {
        return false;
    }
    
    // Get chat messages
    $messages = access_get_chat_messages($chatSessionId);
    
    // Get user email
    $user = access_get_user($chat['user_id']);
    if (!$user || empty($user['email'])) {
        return false;
    }
    
    // Build email content
    $subject = 'Chat Transcript - ' . ($chat['subject'] ?? 'Chat Session #' . $chatSessionId);
    $htmlBody = "<h2>Chat Transcript</h2>";
    $htmlBody .= "<p><strong>Chat Session:</strong> " . htmlspecialchars($chat['subject'] ?? 'Chat #' . $chatSessionId) . "</p>";
    $htmlBody .= "<p><strong>Started:</strong> " . access_format_date($chat['started_at']) . "</p>";
    if ($chat['ended_at']) {
        $htmlBody .= "<p><strong>Ended:</strong> " . access_format_date($chat['ended_at']) . "</p>";
    }
    $htmlBody .= "<hr>";
    $htmlBody .= "<div style='font-family: monospace;'>";
    
    foreach ($messages as $msg) {
        $senderName = $msg['sender_first_name'] . ' ' . $msg['sender_last_name'];
        if (empty(trim($senderName))) {
            $senderName = $msg['sender_email'];
        }
        $senderType = ucfirst($msg['sender_type']);
        $time = access_format_date($msg['created_at'], 'Y-m-d H:i:s');
        
        $htmlBody .= "<p><strong>[{$time}] {$senderType} ({$senderName}):</strong><br>";
        $htmlBody .= nl2br(htmlspecialchars($msg['message'])) . "</p>";
    }
    
    $htmlBody .= "</div>";
    
    // Get attachments
    $attachments = access_get_chat_attachments($chatSessionId);
    if (!empty($attachments)) {
        $htmlBody .= "<hr><h3>Attachments</h3><ul>";
        foreach ($attachments as $att) {
            $htmlBody .= "<li>" . htmlspecialchars($att['file_name']) . " (" . number_format($att['file_size'] / 1024, 2) . " KB)</li>";
        }
        $htmlBody .= "</ul>";
    }
    
    $textBody = "Chat Transcript\n";
    $textBody .= "Chat Session: " . ($chat['subject'] ?? 'Chat #' . $chatSessionId) . "\n";
    $textBody .= "Started: " . access_format_date($chat['started_at']) . "\n";
    if ($chat['ended_at']) {
        $textBody .= "Ended: " . access_format_date($chat['ended_at']) . "\n";
    }
    $textBody .= "\n---\n\n";
    
    foreach ($messages as $msg) {
        $senderName = $msg['sender_first_name'] . ' ' . $msg['sender_last_name'];
        if (empty(trim($senderName))) {
            $senderName = $msg['sender_email'];
        }
        $textBody .= "[" . access_format_date($msg['created_at'], 'Y-m-d H:i:s') . "] " . ucfirst($msg['sender_type']) . " ({$senderName}):\n";
        $textBody .= $msg['message'] . "\n\n";
    }
    
    // Send email using email template system
    $emailSent = access_send_email('chat_transcript', $user['email'], [
        'chat_subject' => $chat['subject'] ?? 'Chat Session #' . $chatSessionId,
        'chat_started' => access_format_date($chat['started_at']),
        'chat_ended' => $chat['ended_at'] ? access_format_date($chat['ended_at']) : 'Ongoing',
        'transcript_html' => $htmlBody,
        'transcript_text' => $textBody
    ]);
    
    // If email template doesn't exist, send directly
    if (!$emailSent) {
        $fromEmail = access_get_parameter('Email', 'from_email', 'noreply@example.com');
        $fromName = access_get_parameter('Email', 'from_name', 'System');
        
        $headers = [
            "From: {$fromName} <{$fromEmail}>",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8"
        ];
        
        $emailSent = mail($user['email'], $subject, $htmlBody, implode("\r\n", $headers));
    }
    
    if ($emailSent) {
        // Mark as forwarded
        $stmt = $conn->prepare("UPDATE access_chat_sessions SET is_forwarded_to_customer = 1, forwarded_at = NOW(), forwarded_by = ? WHERE id = ?");
        $stmt->bind_param("ii", $forwardedBy, $chatSessionId);
        $stmt->execute();
        $stmt->close();
    }
    
    return $emailSent;
}

/**
 * Get chat history with filters
 * @param array $filters Filters (user_id, account_id, admin_user_id, status, date_from, date_to, limit, offset)
 * @return array Chat sessions list
 */
function access_get_chat_history($filters = []) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['user_id'])) {
        $where[] = "cs.user_id = ?";
        $params[] = (int)$filters['user_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['account_id'])) {
        $where[] = "cs.account_id = ?";
        $params[] = (int)$filters['account_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['admin_user_id'])) {
        $where[] = "cs.admin_user_id = ?";
        $params[] = (int)$filters['admin_user_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['status'])) {
        $where[] = "cs.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['date_from'])) {
        $where[] = "cs.created_at >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "cs.created_at <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $sql = "SELECT cs.*, 
                   u.email as user_email, u.first_name as user_first_name, u.last_name as user_last_name,
                   a.account_name,
                   admin.email as admin_email, admin.first_name as admin_first_name, admin.last_name as admin_last_name
            FROM access_chat_sessions cs
            LEFT JOIN access_users u ON cs.user_id = u.id
            LEFT JOIN access_accounts a ON cs.account_id = a.id
            LEFT JOIN access_users admin ON cs.admin_user_id = admin.id";
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY cs.created_at DESC";
    
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
        $chats = [];
        while ($row = $result->fetch_assoc()) {
            $chats[] = $row;
        }
        $stmt->close();
        return $chats;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting chat history: " . $e->getMessage());
        return [];
    }
}

/**
 * Get chat session by ID
 * @param int $chatSessionId Chat session ID
 * @return array|null Chat session data or null
 */
function access_get_chat_session($chatSessionId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT cs.*, 
                                       u.email as user_email, u.first_name as user_first_name, u.last_name as user_last_name,
                                       a.account_name,
                                       admin.email as admin_email, admin.first_name as admin_first_name, admin.last_name as admin_last_name
                                FROM access_chat_sessions cs
                                LEFT JOIN access_users u ON cs.user_id = u.id
                                LEFT JOIN access_accounts a ON cs.account_id = a.id
                                LEFT JOIN access_users admin ON cs.admin_user_id = admin.id
                                WHERE cs.id = ?");
        $stmt->bind_param("i", $chatSessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $chat = $result->fetch_assoc();
        $stmt->close();
        return $chat;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting chat session: " . $e->getMessage());
        return null;
    }
}

/**
 * Get chat attachments for a session
 * @param int $chatSessionId Chat session ID
 * @return array Attachments list
 */
function access_get_chat_attachments($chatSessionId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT ca.* FROM access_chat_attachments ca INNER JOIN access_chat_messages cm ON ca.chat_message_id = cm.id WHERE cm.chat_session_id = ? ORDER BY ca.created_at ASC");
        $stmt->bind_param("i", $chatSessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $attachments = [];
        while ($row = $result->fetch_assoc()) {
            $attachments[] = $row;
        }
        $stmt->close();
        return $attachments;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting chat attachments: " . $e->getMessage());
        return [];
    }
}

/**
 * Attach file to chat message
 * @param int $chatMessageId Chat message ID
 * @param array $fileData File data
 * @return int|false Attachment ID on success, false on failure
 */
function access_attach_file_to_chat($chatMessageId, $fileData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_chat_attachments (chat_message_id, file_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis",
            $chatMessageId,
            $fileData['file_name'],
            $fileData['file_path'],
            $fileData['file_size'],
            $fileData['mime_type'] ?? null
        );
        
        if ($stmt->execute()) {
            $attachmentId = $conn->insert_id;
            $stmt->close();
            return $attachmentId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error attaching file to chat: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark chat messages as read
 * @param int $chatSessionId Chat session ID
 * @param int $userId User ID who read them
 * @return bool Success
 */
function access_mark_chat_messages_read($chatSessionId, $userId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        // Mark messages as read where sender is not the current user
        $stmt = $conn->prepare("UPDATE access_chat_messages cm SET cm.is_read = 1, cm.read_at = NOW() WHERE cm.chat_session_id = ? AND cm.sender_user_id != ? AND cm.is_read = 0");
        $stmt->bind_param("ii", $chatSessionId, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error marking chat messages as read: " . $e->getMessage());
        return false;
    }
}

