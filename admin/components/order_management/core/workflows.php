<?php
/**
 * Order Management Component - Workflow Functions
 * Workflow management and status transitions
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get workflow by ID
 * @param int $workflowId Workflow ID
 * @return array|null Workflow data
 */
function order_management_get_workflow($workflowId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('workflows');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $workflowId);
        $stmt->execute();
        $result = $stmt->get_result();
        $workflow = $result->fetch_assoc();
        $stmt->close();
        
        if ($workflow) {
            // Decode JSON fields
            if (!empty($workflow['trigger_conditions'])) {
                $workflow['trigger_conditions'] = json_decode($workflow['trigger_conditions'], true);
            }
        }
        
        return $workflow;
    }
    
    return null;
}

/**
 * Get all workflows
 * @param array $filters Filters (is_active, is_default)
 * @return array Array of workflows
 */
function order_management_get_workflows($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('workflows');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['is_active'])) {
        $where[] = "is_active = ?";
        $params[] = $filters['is_active'];
        $types .= 'i';
    }
    
    if (isset($filters['is_default'])) {
        $where[] = "is_default = ?";
        $params[] = $filters['is_default'];
        $types .= 'i';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY workflow_name ASC";
    
    $workflows = [];
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['trigger_conditions'])) {
                    $row['trigger_conditions'] = json_decode($row['trigger_conditions'], true);
                }
                $workflows[] = $row;
            }
            $stmt->close();
        }
    } else {
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['trigger_conditions'])) {
                    $row['trigger_conditions'] = json_decode($row['trigger_conditions'], true);
                }
                $workflows[] = $row;
            }
        }
    }
    
    return $workflows;
}

/**
 * Create workflow
 * @param array $data Workflow data
 * @return array Result with workflow ID
 */
function order_management_create_workflow($data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('workflows');
    
    $workflowName = $data['workflow_name'] ?? '';
    $description = $data['description'] ?? null;
    $triggerConditions = isset($data['trigger_conditions']) ? json_encode($data['trigger_conditions']) : null;
    $isDefault = isset($data['is_default']) ? (int)$data['is_default'] : 0;
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (workflow_name, description, trigger_conditions, is_default, is_active) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssii", $workflowName, $description, $triggerConditions, $isDefault, $isActive);
        if ($stmt->execute()) {
            $workflowId = $conn->insert_id;
            $stmt->close();
            
            // If this is set as default, unset other defaults
            if ($isDefault) {
                $stmt = $conn->prepare("UPDATE {$tableName} SET is_default = 0 WHERE id != ?");
                $stmt->bind_param("i", $workflowId);
                $stmt->execute();
                $stmt->close();
            }
            
            return ['success' => true, 'workflow_id' => $workflowId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update workflow
 * @param int $workflowId Workflow ID
 * @param array $data Workflow data
 * @return array Result
 */
function order_management_update_workflow($workflowId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('workflows');
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['workflow_name'])) {
        $updates[] = "workflow_name = ?";
        $params[] = $data['workflow_name'];
        $types .= 's';
    }
    
    if (isset($data['description'])) {
        $updates[] = "description = ?";
        $params[] = $data['description'];
        $types .= 's';
    }
    
    if (isset($data['trigger_conditions'])) {
        $updates[] = "trigger_conditions = ?";
        $params[] = json_encode($data['trigger_conditions']);
        $types .= 's';
    }
    
    if (isset($data['is_default'])) {
        $updates[] = "is_default = ?";
        $params[] = (int)$data['is_default'];
        $types .= 'i';
    }
    
    if (isset($data['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = (int)$data['is_active'];
        $types .= 'i';
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $workflowId;
    $types .= 'i';
    
    $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $stmt->close();
            
            // If this is set as default, unset other defaults
            if (isset($data['is_default']) && $data['is_default']) {
                $stmt = $conn->prepare("UPDATE {$tableName} SET is_default = 0 WHERE id != ?");
                $stmt->bind_param("i", $workflowId);
                $stmt->execute();
                $stmt->close();
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
 * Delete workflow
 * @param int $workflowId Workflow ID
 * @return array Result
 */
function order_management_delete_workflow($workflowId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('workflows');
    
    // Check if workflow is default
    $workflow = order_management_get_workflow($workflowId);
    if ($workflow && $workflow['is_default']) {
        return ['success' => false, 'error' => 'Cannot delete default workflow'];
    }
    
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $workflowId);
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
 * Get workflow steps
 * @param int $workflowId Workflow ID
 * @return array Array of workflow steps
 */
function order_management_get_workflow_steps($workflowId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('workflow_steps');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE workflow_id = ? ORDER BY step_order ASC");
    if ($stmt) {
        $stmt->bind_param("i", $workflowId);
        $stmt->execute();
        $result = $stmt->get_result();
        $steps = [];
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields
            if (!empty($row['conditions'])) {
                $row['conditions'] = json_decode($row['conditions'], true);
            }
            if (!empty($row['actions'])) {
                $row['actions'] = json_decode($row['actions'], true);
            }
            if (!empty($row['notifications'])) {
                $row['notifications'] = json_decode($row['notifications'], true);
            }
            $steps[] = $row;
        }
        $stmt->close();
        return $steps;
    }
    
    return [];
}

/**
 * Create workflow step
 * @param array $data Step data
 * @return array Result with step ID
 */
function order_management_create_workflow_step($data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('workflow_steps');
    
    $workflowId = $data['workflow_id'] ?? 0;
    $stepOrder = $data['step_order'] ?? 0;
    $statusName = $data['status_name'] ?? '';
    $conditions = isset($data['conditions']) ? json_encode($data['conditions']) : null;
    $actions = isset($data['actions']) ? json_encode($data['actions']) : null;
    $requiresApproval = isset($data['requires_approval']) ? (int)$data['requires_approval'] : 0;
    $approvalRole = $data['approval_role'] ?? null;
    $notifications = isset($data['notifications']) ? json_encode($data['notifications']) : null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (workflow_id, step_order, status_name, conditions, actions, requires_approval, approval_role, notifications) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisssiss", $workflowId, $stepOrder, $statusName, $conditions, $actions, $requiresApproval, $approvalRole, $notifications);
        if ($stmt->execute()) {
            $stepId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'step_id' => $stepId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update workflow step
 * @param int $stepId Step ID
 * @param array $data Step data
 * @return array Result
 */
function order_management_update_workflow_step($stepId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('workflow_steps');
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['step_order'])) {
        $updates[] = "step_order = ?";
        $params[] = (int)$data['step_order'];
        $types .= 'i';
    }
    
    if (isset($data['status_name'])) {
        $updates[] = "status_name = ?";
        $params[] = $data['status_name'];
        $types .= 's';
    }
    
    if (isset($data['conditions'])) {
        $updates[] = "conditions = ?";
        $params[] = json_encode($data['conditions']);
        $types .= 's';
    }
    
    if (isset($data['actions'])) {
        $updates[] = "actions = ?";
        $params[] = json_encode($data['actions']);
        $types .= 's';
    }
    
    if (isset($data['requires_approval'])) {
        $updates[] = "requires_approval = ?";
        $params[] = (int)$data['requires_approval'];
        $types .= 'i';
    }
    
    if (isset($data['approval_role'])) {
        $updates[] = "approval_role = ?";
        $params[] = $data['approval_role'];
        $types .= 's';
    }
    
    if (isset($data['notifications'])) {
        $updates[] = "notifications = ?";
        $params[] = json_encode($data['notifications']);
        $types .= 's';
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $stepId;
    $types .= 'i';
    
    $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
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
 * Delete workflow step
 * @param int $stepId Step ID
 * @return array Result
 */
function order_management_delete_workflow_step($stepId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('workflow_steps');
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $stepId);
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
 * Get default workflow
 * @return array|null Default workflow
 */
function order_management_get_default_workflow() {
    $workflows = order_management_get_workflows(['is_default' => 1]);
    return !empty($workflows) ? $workflows[0] : null;
}

/**
 * Assign workflow to order
 * @param int $orderId Order ID (from commerce_orders)
 * @param int $workflowId Workflow ID
 * @return array Result
 */
function order_management_assign_workflow_to_order($orderId, $workflowId) {
    // This will be stored in order metadata or a separate assignment table
    // For now, we'll use the migration_status table to track workflow assignments
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Store workflow assignment in order metadata
    $metadataTable = order_management_get_table_name('order_metadata');
    
    // Check if metadata field exists for workflow_id
    $customFieldsTable = order_management_get_table_name('custom_fields');
    $stmt = $conn->prepare("SELECT id FROM {$customFieldsTable} WHERE field_name = 'workflow_id' LIMIT 1");
    $fieldId = null;
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        if ($row) {
            $fieldId = $row['id'];
        }
    }
    
    // Create field if it doesn't exist
    if (!$fieldId) {
        $stmt = $conn->prepare("INSERT INTO {$customFieldsTable} (field_name, field_type, is_active) VALUES ('workflow_id', 'number', 1)");
        $stmt->execute();
        $fieldId = $conn->insert_id;
        $stmt->close();
    }
    
    // Store workflow assignment
    $stmt = $conn->prepare("INSERT INTO {$metadataTable} (order_id, field_id, field_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE field_value = ?");
    if ($stmt) {
        $workflowIdStr = (string)$workflowId;
        $stmt->bind_param("iiss", $orderId, $fieldId, $workflowIdStr, $workflowIdStr);
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

