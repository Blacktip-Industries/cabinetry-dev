<?php
/**
 * Layout Component - Collaboration Functions
 * Real-time editing, comments, and approvals
 */

require_once __DIR__ . '/database.php';

/**
 * Create collaboration session
 * @param string $resourceType Resource type
 * @param int $resourceId Resource ID
 * @param int $userId User ID
 * @return array Result
 */
function layout_collaboration_create_session($resourceType, $resourceId, $userId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('collaboration_sessions');
        $sessionData = json_encode(['user_id' => $userId, 'joined_at' => date('Y-m-d H:i:s')]);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (resource_type, resource_id, user_id, session_data) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $resourceType, $resourceId, $userId, $sessionData);
        
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Collaboration: Error creating session: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Add collaboration comment
 * @param int $sessionId Session ID
 * @param int $userId User ID
 * @param string $comment Comment text
 * @param int|null $parentCommentId Parent comment ID
 * @return array Result
 */
function layout_collaboration_add_comment($sessionId, $userId, $comment, $parentCommentId = null) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('collaboration_comments');
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (session_id, user_id, comment, parent_comment_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $sessionId, $userId, $comment, $parentCommentId);
        
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Collaboration: Error adding comment: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get comments for session
 * @param int $sessionId Session ID
 * @return array Comments
 */
function layout_collaboration_get_comments($sessionId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('collaboration_comments');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE session_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        
        $stmt->close();
        return $comments;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Collaboration: Error getting comments: " . $e->getMessage());
        return [];
    }
}

