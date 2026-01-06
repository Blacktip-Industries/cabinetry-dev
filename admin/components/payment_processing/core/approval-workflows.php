<?php
/**
 * Payment Processing Component - Approval Workflows
 * Handles approval workflows for transactions and refunds
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/payment-method-rules.php'; // For evaluate_rule_conditions
require_once __DIR__ . '/audit-logger.php';

/**
 * Check if transaction requires approval
 * @param array $transactionData Transaction data
 * @return array|null Workflow data if approval required, null otherwise
 */
function payment_processing_check_approval_required($transactionData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('approval_workflows');
        $result = $conn->query("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY id");
        
        while ($row = $result->fetch_assoc()) {
            $conditions = json_decode($row['conditions'], true);
            
            // Check if conditions match
            if (payment_processing_evaluate_rule_conditions($conditions, $transactionData)) {
                return [
                    'workflow_id' => $row['id'],
                    'workflow_name' => $row['workflow_name'],
                    'approval_levels' => json_decode($row['approval_levels'], true)
                ];
            }
        }
        
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error checking approval required: " . $e->getMessage());
        return null;
    }
}

/**
 * Create approval record
 * @param int $workflowId Workflow ID
 * @param int|null $transactionId Transaction ID
 * @param int|null $refundId Refund ID
 * @return array Result with approval ID
 */
function payment_processing_create_approval($workflowId, $transactionId = null, $refundId = null) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get workflow
    $workflowTable = payment_processing_get_table_name('approval_workflows');
    $stmt = $conn->prepare("SELECT approval_levels FROM {$workflowTable} WHERE id = ?");
    $stmt->bind_param("i", $workflowId);
    $stmt->execute();
    $result = $stmt->get_result();
    $workflow = $result->fetch_assoc();
    $stmt->close();
    
    if (!$workflow) {
        return ['success' => false, 'error' => 'Workflow not found'];
    }
    
    $approvalLevels = json_decode($workflow['approval_levels'], true);
    $firstLevel = $approvalLevels[0] ?? null;
    
    if (!$firstLevel) {
        return ['success' => false, 'error' => 'No approval levels defined'];
    }
    
    // Create approval record for first level
    $approvalTable = payment_processing_get_table_name('approvals');
    $stmt = $conn->prepare("INSERT INTO {$approvalTable} (transaction_id, refund_id, workflow_id, approval_level, status) VALUES (?, ?, ?, ?, 'pending')");
    $approvalLevel = 1;
    $stmt->bind_param("iiii", $transactionId, $refundId, $workflowId, $approvalLevel);
    $stmt->execute();
    $approvalId = $conn->insert_id;
    $stmt->close();
    
    // Log audit
    payment_processing_log_audit(
        'approval_required',
        $transactionId ? 'transaction' : 'refund',
        $transactionId ?? $refundId,
        null,
        [
            'workflow_id' => $workflowId,
            'approval_level' => $approvalLevel
        ]
    );
    
    return [
        'success' => true,
        'approval_id' => $approvalId,
        'approval_level' => $approvalLevel,
        'approvers' => $firstLevel['approvers'] ?? []
    ];
}

/**
 * Approve or reject approval
 * @param int $approvalId Approval ID
 * @param int $approverId Approver user ID
 * @param string $action 'approved' or 'rejected'
 * @param string|null $comments Comments
 * @return array Result
 */
function payment_processing_process_approval($approvalId, $approverId, $action, $comments = null) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('approvals');
        
        // Get approval
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $approvalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $approval = $result->fetch_assoc();
        $stmt->close();
        
        if (!$approval || $approval['status'] !== 'pending') {
            return ['success' => false, 'error' => 'Approval not found or already processed'];
        }
        
        // Update approval
        $updateStmt = $conn->prepare("UPDATE {$tableName} SET status = ?, approver_id = ?, comments = ?, approved_at = ?, rejected_at = ? WHERE id = ?");
        
        $approvedAt = $action === 'approved' ? date('Y-m-d H:i:s') : null;
        $rejectedAt = $action === 'rejected' ? date('Y-m-d H:i:s') : null;
        
        $updateStmt->bind_param("sisssi", $action, $approverId, $comments, $approvedAt, $rejectedAt, $approvalId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // If approved, check for next level
        if ($action === 'approved') {
            // Get workflow to check next level
            $workflowTable = payment_processing_get_table_name('approval_workflows');
            $workflowStmt = $conn->prepare("SELECT approval_levels FROM {$workflowTable} WHERE id = ?");
            $workflowStmt->bind_param("i", $approval['workflow_id']);
            $workflowStmt->execute();
            $workflowResult = $workflowStmt->get_result();
            $workflow = $workflowResult->fetch_assoc();
            $workflowStmt->close();
            
            $approvalLevels = json_decode($workflow['approval_levels'], true);
            $nextLevel = $approval['approval_level'] + 1;
            
            if (isset($approvalLevels[$nextLevel - 1])) {
                // Create next level approval
                $nextStmt = $conn->prepare("INSERT INTO {$tableName} (transaction_id, refund_id, workflow_id, approval_level, status) VALUES (?, ?, ?, ?, 'pending')");
                $nextStmt->bind_param("iiii", $approval['transaction_id'], $approval['refund_id'], $approval['workflow_id'], $nextLevel);
                $nextStmt->execute();
                $nextStmt->close();
            } else {
                // All levels approved, proceed with transaction/refund
                if ($approval['transaction_id']) {
                    // Process transaction
                    // This would trigger the actual payment processing
                } elseif ($approval['refund_id']) {
                    // Process refund
                    // This would trigger the actual refund processing
                }
            }
        }
        
        // Log audit
        payment_processing_log_audit(
            'approval_' . $action,
            $approval['transaction_id'] ? 'transaction' : 'refund',
            $approval['transaction_id'] ?? $approval['refund_id'],
            $approverId,
            [
                'approval_id' => $approvalId,
                'approval_level' => $approval['approval_level'],
                'comments' => $comments
            ]
        );
        
        return ['success' => true, 'action' => $action];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

