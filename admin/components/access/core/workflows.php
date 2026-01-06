<?php
/**
 * Access Component - Workflow Engine
 * Handles custom workflow execution
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/hooks.php';

/**
 * Execute workflow
 * @param int $workflowId Workflow ID
 * @param array $context Context data
 * @return array ['success' => bool, 'results' => array, 'errors' => array]
 */
function access_execute_workflow($workflowId, $context = []) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'errors' => ['Database connection failed']];
    }
    
    $workflow = access_get_workflow($workflowId);
    if (!$workflow || !$workflow['is_active']) {
        return ['success' => false, 'errors' => ['Workflow not found or inactive']];
    }
    
    $steps = is_string($workflow['steps']) ? json_decode($workflow['steps'], true) : $workflow['steps'];
    $conditions = is_string($workflow['conditions']) ? json_decode($workflow['conditions'], true) : $workflow['conditions'];
    $actions = is_string($workflow['actions']) ? json_decode($workflow['actions'], true) : $workflow['actions'];
    
    $results = [];
    $errors = [];
    
    // Evaluate conditions
    if (!empty($conditions) && !access_evaluate_workflow_conditions($conditions, $context)) {
        return ['success' => false, 'errors' => ['Workflow conditions not met']];
    }
    
    // Execute steps
    foreach ($steps as $step) {
        $stepResult = access_execute_workflow_step($step, $context);
        $results[] = $stepResult;
        
        if (!$stepResult['success']) {
            $errors = array_merge($errors, $stepResult['errors'] ?? []);
        }
    }
    
    // Execute actions
    if (!empty($actions)) {
        foreach ($actions as $action) {
            $actionResult = access_execute_workflow_action($action, $context);
            $results[] = $actionResult;
            
            if (!$actionResult['success']) {
                $errors = array_merge($errors, $actionResult['errors'] ?? []);
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'results' => $results,
        'errors' => $errors
    ];
}

/**
 * Get workflow by ID
 * @param int $workflowId Workflow ID
 * @return array|null Workflow data or null
 */
function access_get_workflow($workflowId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_workflows WHERE id = ?");
        $stmt->bind_param("i", $workflowId);
        $stmt->execute();
        $result = $stmt->get_result();
        $workflow = $result->fetch_assoc();
        $stmt->close();
        return $workflow;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting workflow: " . $e->getMessage());
        return null;
    }
}

/**
 * Evaluate workflow conditions
 * @param array $conditions Conditions array
 * @param array $context Context data
 * @return bool True if conditions are met
 */
function access_evaluate_workflow_conditions($conditions, $context) {
    // Simple condition evaluation
    // Can be extended for complex logic
    foreach ($conditions as $condition) {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;
        
        if ($field && isset($context[$field])) {
            $contextValue = $context[$field];
            
            switch ($operator) {
                case 'equals':
                    if ($contextValue != $value) return false;
                    break;
                case 'not_equals':
                    if ($contextValue == $value) return false;
                    break;
                case 'greater_than':
                    if ($contextValue <= $value) return false;
                    break;
                case 'less_than':
                    if ($contextValue >= $value) return false;
                    break;
                case 'contains':
                    if (strpos($contextValue, $value) === false) return false;
                    break;
            }
        }
    }
    
    return true;
}

/**
 * Execute workflow step
 * @param array $step Step definition
 * @param array $context Context data
 * @return array Step result
 */
function access_execute_workflow_step($step, $context) {
    $stepType = $step['type'] ?? 'action';
    
    switch ($stepType) {
        case 'approval':
            // Handle approval step
            return ['success' => true, 'type' => 'approval'];
            
        case 'notification':
            // Handle notification step
            return ['success' => true, 'type' => 'notification'];
            
        case 'custom':
            // Execute custom hook
            access_do_action($step['hook'] ?? '', $context);
            return ['success' => true, 'type' => 'custom'];
            
        default:
            return ['success' => false, 'errors' => ['Unknown step type']];
    }
}

/**
 * Execute workflow action
 * @param array $action Action definition
 * @param array $context Context data
 * @return array Action result
 */
function access_execute_workflow_action($action, $context) {
    $actionType = $action['type'] ?? 'custom';
    
    switch ($actionType) {
        case 'send_email':
            // Send email action
            return ['success' => true, 'type' => 'send_email'];
            
        case 'create_account':
            // Create account action
            return ['success' => true, 'type' => 'create_account'];
            
        case 'assign_role':
            // Assign role action
            return ['success' => true, 'type' => 'assign_role'];
            
        case 'custom':
            // Execute custom hook
            access_do_action($action['hook'] ?? '', $context);
            return ['success' => true, 'type' => 'custom'];
            
        default:
            return ['success' => false, 'errors' => ['Unknown action type']];
    }
}

