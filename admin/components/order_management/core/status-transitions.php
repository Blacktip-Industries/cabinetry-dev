<?php
/**
 * Order Management Component - Status Transition Engine
 * Handles order status transitions based on workflows
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/workflows.php';
require_once __DIR__ . '/functions.php';

/**
 * Get current workflow for order
 * @param int $orderId Order ID
 * @return array|null Workflow data
 */
function order_management_get_order_workflow($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    // Get workflow ID from order metadata
    $metadataTable = order_management_get_table_name('order_metadata');
    $customFieldsTable = order_management_get_table_name('custom_fields');
    
    $stmt = $conn->prepare("SELECT om.field_value, cf.field_name 
                            FROM {$metadataTable} om 
                            INNER JOIN {$customFieldsTable} cf ON om.field_id = cf.id 
                            WHERE om.order_id = ? AND cf.field_name = 'workflow_id' 
                            LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row && !empty($row['field_value'])) {
            return order_management_get_workflow((int)$row['field_value']);
        }
    }
    
    // Fall back to default workflow
    return order_management_get_default_workflow();
}

/**
 * Get current status step for order
 * @param int $orderId Order ID
 * @return array|null Current step data
 */
function order_management_get_order_current_step($orderId) {
    $workflow = order_management_get_order_workflow($orderId);
    if (!$workflow) {
        return null;
    }
    
    // Get current order status from commerce_orders
    if (!order_management_is_commerce_available()) {
        return null;
    }
    
    $conn = order_management_get_db_connection();
    $stmt = $conn->prepare("SELECT order_status FROM commerce_orders WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if ($order) {
            $currentStatus = $order['order_status'];
            
            // Find step with matching status
            $steps = order_management_get_workflow_steps($workflow['id']);
            foreach ($steps as $step) {
                if ($step['status_name'] === $currentStatus) {
                    return $step;
                }
            }
        }
    }
    
    return null;
}

/**
 * Get next available status transitions for order
 * @param int $orderId Order ID
 * @return array Array of available next statuses
 */
function order_management_get_available_transitions($orderId) {
    $currentStep = order_management_get_order_current_step($orderId);
    if (!$currentStep) {
        return [];
    }
    
    $workflow = order_management_get_order_workflow($orderId);
    if (!$workflow) {
        return [];
    }
    
    $steps = order_management_get_workflow_steps($workflow['id']);
    $available = [];
    
    // Get next step in sequence
    foreach ($steps as $step) {
        if ($step['step_order'] > $currentStep['step_order']) {
            // Check conditions
            if (empty($step['conditions']) || order_management_check_step_conditions($orderId, $step['conditions'])) {
                $available[] = $step;
            }
        }
    }
    
    return $available;
}

/**
 * Check if step conditions are met
 * @param int $orderId Order ID
 * @param array $conditions Conditions array
 * @return bool True if conditions are met
 */
function order_management_check_step_conditions($orderId, $conditions) {
    if (empty($conditions)) {
        return true;
    }
    
    // Get order data
    if (!order_management_is_commerce_available()) {
        return false;
    }
    
    $conn = order_management_get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if (!$order) {
            return false;
        }
        
        // Evaluate conditions
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? '';
            
            $orderValue = $order[$field] ?? null;
            
            switch ($operator) {
                case '=':
                    if ($orderValue != $value) return false;
                    break;
                case '!=':
                    if ($orderValue == $value) return false;
                    break;
                case '>':
                    if ($orderValue <= $value) return false;
                    break;
                case '<':
                    if ($orderValue >= $value) return false;
                    break;
                case '>=':
                    if ($orderValue < $value) return false;
                    break;
                case '<=':
                    if ($orderValue > $value) return false;
                    break;
                case 'in':
                    if (!in_array($orderValue, explode(',', $value))) return false;
                    break;
                case 'not_in':
                    if (in_array($orderValue, explode(',', $value))) return false;
                    break;
            }
        }
        
        return true;
    }
    
    return false;
}

/**
 * Transition order to new status
 * @param int $orderId Order ID
 * @param string $newStatus New status name
 * @param int $userId User ID making the change
 * @param string $notes Optional notes
 * @param bool $skipApproval Skip approval check (for automated transitions)
 * @return array Result
 */
function order_management_transition_order_status($orderId, $newStatus, $userId = null, $notes = null, $skipApproval = false) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    // Get current status
    $stmt = $conn->prepare("SELECT order_status FROM commerce_orders WHERE id = ? LIMIT 1");
    $oldStatus = null;
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        if ($order) {
            $oldStatus = $order['order_status'];
        }
    }
    
    // Check if transition is valid
    $availableTransitions = order_management_get_available_transitions($orderId);
    $isValidTransition = false;
    $targetStep = null;
    
    foreach ($availableTransitions as $step) {
        if ($step['status_name'] === $newStatus) {
            $isValidTransition = true;
            $targetStep = $step;
            break;
        }
    }
    
    // Allow direct status changes if no workflow is assigned
    $workflow = order_management_get_order_workflow($orderId);
    if (!$workflow) {
        $isValidTransition = true;
    }
    
    if (!$isValidTransition) {
        return ['success' => false, 'error' => 'Invalid status transition'];
    }
    
    // Check if approval is required
    if (!$skipApproval && $targetStep && $targetStep['requires_approval']) {
        // Check if approval exists
        $approvalsTable = order_management_get_table_name('approvals');
        $stmt = $conn->prepare("SELECT id FROM {$approvalsTable} WHERE order_id = ? AND workflow_step_id = ? AND status = 'approved' LIMIT 1");
        $hasApproval = false;
        if ($stmt) {
            $stmt->bind_param("ii", $orderId, $targetStep['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $hasApproval = true;
            }
            $stmt->close();
        }
        
        if (!$hasApproval) {
            return ['success' => false, 'error' => 'Approval required for this status transition', 'requires_approval' => true];
        }
    }
    
    // Update order status in commerce_orders
    $stmt = $conn->prepare("UPDATE commerce_orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $newStatus, $orderId);
        if ($stmt->execute()) {
            $stmt->close();
            
            // Record status history
            $historyTable = order_management_get_table_name('status_history');
            $workflowId = $workflow ? $workflow['id'] : null;
            $stepId = $targetStep ? $targetStep['id'] : null;
            $changeType = $skipApproval ? 'automated' : 'manual';
            
            $stmt = $conn->prepare("INSERT INTO {$historyTable} (order_id, workflow_id, workflow_step_id, old_status, new_status, changed_by, change_type, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("iiisssss", $orderId, $workflowId, $stepId, $oldStatus, $newStatus, $userId, $changeType, $notes);
                $stmt->execute();
                $stmt->close();
            }
            
            // Execute step actions
            if ($targetStep && !empty($targetStep['actions'])) {
                order_management_execute_step_actions($orderId, $targetStep['actions']);
            }
            
            // Send notifications
            if ($targetStep && !empty($targetStep['notifications'])) {
                order_management_send_step_notifications($orderId, $targetStep['notifications']);
            }
            
            // Log audit
            order_management_log_audit($orderId, 'status_change', $userId, ['old_status' => $oldStatus], ['new_status' => $newStatus]);
            
            return ['success' => true, 'old_status' => $oldStatus, 'new_status' => $newStatus];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Execute step actions
 * @param int $orderId Order ID
 * @param array $actions Actions to execute
 * @return void
 */
function order_management_execute_step_actions($orderId, $actions) {
    // This will be expanded in the automation engine
    // For now, basic action execution
    foreach ($actions as $action) {
        $actionType = $action['type'] ?? '';
        
        switch ($actionType) {
            case 'update_field':
                // Update order field
                break;
            case 'send_notification':
                // Send notification
                break;
            case 'allocate_inventory':
                // Allocate inventory
                break;
            case 'create_fulfillment':
                // Create fulfillment
                break;
        }
    }
}

/**
 * Send step notifications
 * @param int $orderId Order ID
 * @param array $notifications Notifications to send
 * @return void
 */
function order_management_send_step_notifications($orderId, $notifications) {
    // This will integrate with the notification system
    // For now, placeholder
}

/**
 * Log audit entry
 * @param int $orderId Order ID
 * @param string $actionType Action type
 * @param int $userId User ID
 * @param array $beforeValues Before values
 * @param array $afterValues After values
 * @return void
 */
function order_management_log_audit($orderId, $actionType, $userId, $beforeValues = [], $afterValues = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return;
    }
    
    $tableName = order_management_get_table_name('audit_log');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, action_type, user_id, before_values, after_values, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $beforeJson = json_encode($beforeValues);
        $afterJson = json_encode($afterValues);
        $stmt->bind_param("isissss", $orderId, $actionType, $userId, $beforeJson, $afterJson, $ipAddress, $userAgent);
        $stmt->execute();
        $stmt->close();
    }
}

