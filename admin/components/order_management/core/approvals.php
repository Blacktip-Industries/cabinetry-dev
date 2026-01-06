<?php
/**
 * Order Management Component - Approval System
 * Handles approval workflows for order status changes
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Create approval request
 * @param int $orderId Order ID
 * @param int $workflowStepId Workflow step ID
 * @param int $approverId Approver user ID
 * @param string $approvalType Approval type
 * @return array Result with approval ID
 */
function order_management_create_approval($orderId, $workflowStepId, $approverId, $approvalType = 'status_change') {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('approvals');
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, workflow_step_id, approval_type, approver_id, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
    if ($stmt) {
        $stmt->bind_param("iisi", $orderId, $workflowStepId, $approvalType, $approverId);
        if ($stmt->execute()) {
            $approvalId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'approval_id' => $approvalId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get approval by ID
 * @param int $approvalId Approval ID
 * @return array|null Approval data
 */
function order_management_get_approval($approvalId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('approvals');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $approvalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $approval = $result->fetch_assoc();
        $stmt->close();
        return $approval;
    }
    
    return null;
}

/**
 * Get approvals for order
 * @param int $orderId Order ID
 * @param array $filters Filters (status, workflow_step_id)
 * @return array Array of approvals
 */
function order_management_get_order_approvals($orderId, $filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('approvals');
    $where = ["order_id = ?"];
    $params = [$orderId];
    $types = 'i';
    
    if (isset($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (isset($filters['workflow_step_id'])) {
        $where[] = "workflow_step_id = ?";
        $params[] = $filters['workflow_step_id'];
        $types .= 'i';
    }
    
    $whereClause = implode(' AND ', $where);
    $query = "SELECT * FROM {$tableName} WHERE {$whereClause} ORDER BY created_at DESC";
    
    $approvals = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $approvals[] = $row;
        }
        $stmt->close();
    }
    
    return $approvals;
}

/**
 * Approve approval request
 * @param int $approvalId Approval ID
 * @param int $userId User ID approving
 * @param string $comments Optional comments
 * @return array Result
 */
function order_management_approve($approvalId, $userId, $comments = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('approvals');
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'approved', approver_id = ?, comments = ?, updated_at = NOW() WHERE id = ? AND status = 'pending'");
    if ($stmt) {
        $stmt->bind_param("isi", $userId, $comments, $approvalId);
        if ($stmt->execute()) {
            $stmt->close();
            
            // Get approval details
            $approval = order_management_get_approval($approvalId);
            if ($approval) {
                // Check if we can now transition the order
                require_once __DIR__ . '/status-transitions.php';
                $step = null;
                if ($approval['workflow_step_id']) {
                    require_once __DIR__ . '/workflows.php';
                    $steps = order_management_get_workflow_steps($approval['workflow_step_id']);
                    // Get workflow ID from step
                    $workflowStepsTable = order_management_get_table_name('workflow_steps');
                    $stmt = $conn->prepare("SELECT workflow_id FROM {$workflowStepsTable} WHERE id = ? LIMIT 1");
                    $stmt->bind_param("i", $approval['workflow_step_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stepRow = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($stepRow) {
                        $steps = order_management_get_workflow_steps($stepRow['workflow_id']);
                        foreach ($steps as $s) {
                            if ($s['id'] == $approval['workflow_step_id']) {
                                $step = $s;
                                break;
                            }
                        }
                    }
                }
                
                // Auto-transition if all approvals are in place
                if ($step) {
                    $pendingApprovals = order_management_get_order_approvals($approval['order_id'], [
                        'workflow_step_id' => $approval['workflow_step_id'],
                        'status' => 'pending'
                    ]);
                    
                    if (empty($pendingApprovals)) {
                        // All approvals complete, transition order
                        order_management_transition_order_status(
                            $approval['order_id'],
                            $step['status_name'],
                            $userId,
                            'Auto-transitioned after approval',
                            true
                        );
                    }
                }
            }
            
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Reject approval request
 * @param int $approvalId Approval ID
 * @param int $userId User ID rejecting
 * @param string $comments Rejection comments
 * @return array Result
 */
function order_management_reject_approval($approvalId, $userId, $comments = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('approvals');
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'rejected', approver_id = ?, comments = ?, updated_at = NOW() WHERE id = ? AND status = 'pending'");
    if ($stmt) {
        $stmt->bind_param("isi", $userId, $comments, $approvalId);
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

/**
 * Get pending approvals for user
 * @param int $userId User ID
 * @param string $role User role (optional)
 * @return array Array of pending approvals
 */
function order_management_get_pending_approvals_for_user($userId, $role = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('approvals');
    $workflowStepsTable = order_management_get_table_name('workflow_steps');
    
    // Get approvals where user is the approver or matches the required role
    $query = "SELECT a.* FROM {$tableName} a 
              LEFT JOIN {$workflowStepsTable} ws ON a.workflow_step_id = ws.id 
              WHERE a.status = 'pending' 
              AND (a.approver_id = ? OR (? IS NOT NULL AND ws.approval_role = ?))
              ORDER BY a.created_at ASC";
    
    $approvals = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("iss", $userId, $role, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $approvals[] = $row;
        }
        $stmt->close();
    }
    
    return $approvals;
}

