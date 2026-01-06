<?php
/**
 * Formula Builder Component - Collaboration System
 * Workspaces, comments, and collaboration features
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Create workspace
 * @param string $workspaceName Workspace name
 * @param string $description Description
 * @param int $createdBy User ID
 * @return array Result with workspace ID
 */
function formula_builder_create_workspace($workspaceName, $description = '', $createdBy = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if ($createdBy === null) {
        $createdBy = $_SESSION['user_id'] ?? 0;
    }
    
    try {
        $tableName = formula_builder_get_table_name('workspaces');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (workspace_name, description, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $workspaceName, $description, $createdBy);
        $stmt->execute();
        $workspaceId = $conn->insert_id;
        $stmt->close();
        
        // Add creator as admin member
        formula_builder_add_workspace_member($workspaceId, $createdBy, 'admin');
        
        return ['success' => true, 'workspace_id' => $workspaceId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error creating workspace: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Add workspace member
 * @param int $workspaceId Workspace ID
 * @param int $userId User ID
 * @param string $role Role (admin, editor, viewer)
 * @param array $permissions Permissions array
 * @return array Result
 */
function formula_builder_add_workspace_member($workspaceId, $userId, $role = 'viewer', $permissions = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('workspace_members');
        $permissionsJson = json_encode($permissions);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (workspace_id, user_id, role, permissions) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE role = ?, permissions = ?");
        $stmt->bind_param("iissss", $workspaceId, $userId, $role, $permissionsJson, $role, $permissionsJson);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error adding workspace member: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Add comment to formula
 * @param int $formulaId Formula ID
 * @param int $userId User ID
 * @param string $commentText Comment text
 * @param int|null $lineNumber Line number (optional)
 * @return array Result with comment ID
 */
function formula_builder_add_comment($formulaId, $userId, $commentText, $lineNumber = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('comments');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, user_id, line_number, comment_text) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $formulaId, $userId, $lineNumber, $commentText);
        $stmt->execute();
        $commentId = $conn->insert_id;
        $stmt->close();
        
        // Record collaboration activity
        formula_builder_record_collaboration($formulaId, $userId, 'comment_added', ['comment_id' => $commentId]);
        
        return ['success' => true, 'comment_id' => $commentId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error adding comment: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get comments for formula
 * @param int $formulaId Formula ID
 * @return array Comments
 */
function formula_builder_get_comments($formulaId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('comments');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE formula_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $formulaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        
        $stmt->close();
        return $comments;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting comments: " . $e->getMessage());
        return [];
    }
}

/**
 * Record collaboration activity
 * @param int $formulaId Formula ID
 * @param int $userId User ID
 * @param string $actionType Action type
 * @param array $changes Changes data
 * @param string $comment Comment
 * @return array Result
 */
function formula_builder_record_collaboration($formulaId, $userId, $actionType, $changes = [], $comment = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('collaborations');
        $changesJson = json_encode($changes);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, user_id, action_type, changes, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $formulaId, $userId, $actionType, $changesJson, $comment);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error recording collaboration: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get collaboration activity
 * @param int $formulaId Formula ID
 * @param int $limit Limit
 * @return array Activity
 */
function formula_builder_get_collaboration_activity($formulaId, $limit = 50) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('collaborations');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE formula_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $formulaId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $row['changes'] = json_decode($row['changes'], true) ?: [];
            $activities[] = $row;
        }
        
        $stmt->close();
        return $activities;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting collaboration activity: " . $e->getMessage());
        return [];
    }
}

