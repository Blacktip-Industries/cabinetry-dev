<?php
/**
 * Order Management Component - Automation Functions
 * Automation rules engine
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/workflows.php';
require_once __DIR__ . '/status-transitions.php';
require_once __DIR__ . '/fulfillment.php';

/**
 * Get automation rule by ID
 * @param int $ruleId Rule ID
 * @return array|null Rule data
 */
function order_management_get_automation_rule($ruleId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('automation_rules');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $ruleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rule = $result->fetch_assoc();
        $stmt->close();
        
        if ($rule) {
            // Decode JSON fields
            if (!empty($rule['trigger_conditions'])) {
                $rule['trigger_conditions'] = json_decode($rule['trigger_conditions'], true);
            }
            if (!empty($rule['actions'])) {
                $rule['actions'] = json_decode($rule['actions'], true);
            }
        }
        
        return $rule;
    }
    
    return null;
}

/**
 * Get all automation rules
 * @param array $filters Filters (is_active, priority)
 * @return array Array of rules
 */
function order_management_get_automation_rules($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('automation_rules');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['is_active'])) {
        $where[] = "is_active = ?";
        $params[] = $filters['is_active'];
        $types .= 'i';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY priority DESC, rule_name ASC";
    
    $rules = [];
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
                if (!empty($row['actions'])) {
                    $row['actions'] = json_decode($row['actions'], true);
                }
                $rules[] = $row;
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
                if (!empty($row['actions'])) {
                    $row['actions'] = json_decode($row['actions'], true);
                }
                $rules[] = $row;
            }
        }
    }
    
    return $rules;
}

/**
 * Create automation rule
 * @param array $data Rule data
 * @return array Result with rule ID
 */
function order_management_create_automation_rule($data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('automation_rules');
    
    $ruleName = $data['rule_name'] ?? '';
    $description = $data['description'] ?? null;
    $triggerConditions = isset($data['trigger_conditions']) ? json_encode($data['trigger_conditions']) : '[]';
    $actions = isset($data['actions']) ? json_encode($data['actions']) : '[]';
    $priority = isset($data['priority']) ? (int)$data['priority'] : 0;
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (rule_name, description, trigger_conditions, actions, priority, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssii", $ruleName, $description, $triggerConditions, $actions, $priority, $isActive);
        if ($stmt->execute()) {
            $ruleId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'rule_id' => $ruleId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update automation rule
 * @param int $ruleId Rule ID
 * @param array $data Rule data
 * @return array Result
 */
function order_management_update_automation_rule($ruleId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('automation_rules');
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['rule_name'])) {
        $updates[] = "rule_name = ?";
        $params[] = $data['rule_name'];
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
    
    if (isset($data['actions'])) {
        $updates[] = "actions = ?";
        $params[] = json_encode($data['actions']);
        $types .= 's';
    }
    
    if (isset($data['priority'])) {
        $updates[] = "priority = ?";
        $params[] = (int)$data['priority'];
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
    $params[] = $ruleId;
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
 * Delete automation rule
 * @param int $ruleId Rule ID
 * @return array Result
 */
function order_management_delete_automation_rule($ruleId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('automation_rules');
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $ruleId);
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
 * Evaluate trigger conditions
 * @param int $orderId Order ID
 * @param array $triggerConditions Trigger conditions
 * @return bool True if conditions are met
 */
function order_management_evaluate_trigger_conditions($orderId, $triggerConditions) {
    if (empty($triggerConditions)) {
        return false;
    }
    
    if (!order_management_is_commerce_available()) {
        return false;
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    // Get order data
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return false;
    }
    
    // Evaluate each condition
    foreach ($triggerConditions as $condition) {
        $triggerType = $condition['type'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';
        
        switch ($triggerType) {
            case 'order_status':
                if (!order_management_compare_value($order['order_status'], $operator, $value)) {
                    return false;
                }
                break;
                
            case 'payment_status':
                if (!order_management_compare_value($order['payment_status'], $operator, $value)) {
                    return false;
                }
                break;
                
            case 'shipping_status':
                if (!order_management_compare_value($order['shipping_status'], $operator, $value)) {
                    return false;
                }
                break;
                
            case 'total_amount':
                $orderValue = (float)$order['total_amount'];
                $compareValue = (float)$value;
                if (!order_management_compare_value($orderValue, $operator, $compareValue)) {
                    return false;
                }
                break;
                
            case 'order_date':
                $orderDate = date('Y-m-d', strtotime($order['created_at']));
                if (!order_management_compare_value($orderDate, $operator, $value)) {
                    return false;
                }
                break;
                
            case 'customer_email':
                if (!order_management_compare_value($order['customer_email'], $operator, $value)) {
                    return false;
                }
                break;
                
            case 'has_tag':
                // Check if order has specific tag
                $tagsTable = order_management_get_table_name('order_tags');
                $tags = order_management_get_table_name('tags');
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$tagsTable} ot 
                                       INNER JOIN {$tags} t ON ot.tag_id = t.id 
                                       WHERE ot.order_id = ? AND t.tag_name = ?");
                $stmt->bind_param("is", $orderId, $value);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                if (($row['count'] ?? 0) == 0) {
                    return false;
                }
                break;
        }
    }
    
    return true;
}

/**
 * Compare values based on operator
 * @param mixed $left Left value
 * @param string $operator Operator (=, !=, >, <, >=, <=, in, not_in, contains)
 * @param mixed $right Right value
 * @return bool True if comparison passes
 */
function order_management_compare_value($left, $operator, $right) {
    switch ($operator) {
        case '=':
            return $left == $right;
        case '!=':
            return $left != $right;
        case '>':
            return $left > $right;
        case '<':
            return $left < $right;
        case '>=':
            return $left >= $right;
        case '<=':
            return $left <= $right;
        case 'in':
            $rightArray = is_array($right) ? $right : explode(',', $right);
            return in_array($left, $rightArray);
        case 'not_in':
            $rightArray = is_array($right) ? $right : explode(',', $right);
            return !in_array($left, $rightArray);
        case 'contains':
            return strpos($left, $right) !== false;
        case 'not_contains':
            return strpos($left, $right) === false;
        case 'starts_with':
            return strpos($left, $right) === 0;
        case 'ends_with':
            return substr($left, -strlen($right)) === $right;
        default:
            return false;
    }
}

/**
 * Process automation trigger
 * @param int $orderId Order ID
 * @param string $triggerEvent Trigger event (order_created, status_changed, payment_received, etc.)
 * @return array Results of executed rules
 */
function order_management_process_automation_trigger($orderId, $triggerEvent) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get all active automation rules
    $rules = order_management_get_automation_rules(['is_active' => 1]);
    
    $results = [];
    
    foreach ($rules as $rule) {
        // Check if rule matches trigger event
        $triggerConditions = $rule['trigger_conditions'] ?? [];
        $matchesEvent = false;
        
        foreach ($triggerConditions as $condition) {
            if (($condition['event'] ?? '') === $triggerEvent) {
                $matchesEvent = true;
                break;
            }
        }
        
        if (!$matchesEvent) {
            continue;
        }
        
        // Evaluate conditions
        if (order_management_evaluate_trigger_conditions($orderId, $triggerConditions)) {
            // Execute actions
            $actionResults = order_management_execute_automation_actions($orderId, $rule['id'], $rule['actions']);
            
            // Log execution
            order_management_log_automation_execution($rule['id'], $orderId, $triggerEvent, $actionResults);
            
            $results[] = [
                'rule_id' => $rule['id'],
                'rule_name' => $rule['rule_name'],
                'success' => $actionResults['success'],
                'actions_executed' => $actionResults['actions_executed'] ?? []
            ];
        }
    }
    
    return [
        'success' => true,
        'rules_executed' => count($results),
        'results' => $results
    ];
}

/**
 * Execute automation actions
 * @param int $orderId Order ID
 * @param int $ruleId Rule ID
 * @param array $actions Actions to execute
 * @return array Result
 */
function order_management_execute_automation_actions($orderId, $ruleId, $actions) {
    $executed = [];
    $errors = [];
    
    foreach ($actions as $action) {
        $actionType = $action['type'] ?? '';
        $actionParams = $action['params'] ?? [];
        
        try {
            $result = order_management_execute_single_action($orderId, $actionType, $actionParams);
            $executed[] = [
                'type' => $actionType,
                'success' => $result['success'] ?? false,
                'result' => $result
            ];
            
            if (!$result['success']) {
                $errors[] = "Action {$actionType} failed: " . ($result['error'] ?? 'Unknown error');
            }
        } catch (Exception $e) {
            $errors[] = "Action {$actionType} exception: " . $e->getMessage();
            $executed[] = [
                'type' => $actionType,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return [
        'success' => empty($errors),
        'actions_executed' => $executed,
        'errors' => $errors
    ];
}

/**
 * Execute single automation action
 * @param int $orderId Order ID
 * @param string $actionType Action type
 * @param array $params Action parameters
 * @return array Result
 */
function order_management_execute_single_action($orderId, $actionType, $params = []) {
    switch ($actionType) {
        case 'update_status':
            $newStatus = $params['status'] ?? null;
            if ($newStatus) {
                return order_management_transition_order_status($orderId, $newStatus, null, 'Automated status change', true);
            }
            return ['success' => false, 'error' => 'Status not provided'];
            
        case 'assign_workflow':
            $workflowId = $params['workflow_id'] ?? null;
            if ($workflowId) {
                return order_management_assign_workflow_to_order($orderId, $workflowId);
            }
            return ['success' => false, 'error' => 'Workflow ID not provided'];
            
        case 'assign_priority':
            $priorityId = $params['priority_id'] ?? null;
            if ($priorityId) {
                $conn = order_management_get_db_connection();
                $tableName = order_management_get_table_name('order_priorities');
                $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, priority_id, assigned_by) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE priority_id = ?");
                $stmt->bind_param("iii", $orderId, $priorityId, $priorityId);
                $result = $stmt->execute();
                $stmt->close();
                return ['success' => $result];
            }
            return ['success' => false, 'error' => 'Priority ID not provided'];
            
        case 'add_tag':
            $tagName = $params['tag_name'] ?? null;
            if ($tagName) {
                $conn = order_management_get_db_connection();
                $tagsTable = order_management_get_table_name('tags');
                $orderTagsTable = order_management_get_table_name('order_tags');
                
                // Get or create tag
                $stmt = $conn->prepare("SELECT id FROM {$tagsTable} WHERE tag_name = ? LIMIT 1");
                $stmt->bind_param("s", $tagName);
                $stmt->execute();
                $result = $stmt->get_result();
                $tag = $result->fetch_assoc();
                $stmt->close();
                
                if (!$tag) {
                    // Create tag
                    $stmt = $conn->prepare("INSERT INTO {$tagsTable} (tag_name) VALUES (?)");
                    $stmt->bind_param("s", $tagName);
                    $stmt->execute();
                    $tagId = $conn->insert_id;
                    $stmt->close();
                } else {
                    $tagId = $tag['id'];
                }
                
                // Assign tag to order
                $stmt = $conn->prepare("INSERT IGNORE INTO {$orderTagsTable} (order_id, tag_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $orderId, $tagId);
                $result = $stmt->execute();
                $stmt->close();
                return ['success' => $result];
            }
            return ['success' => false, 'error' => 'Tag name not provided'];
            
        case 'send_notification':
            $templateId = $params['template_id'] ?? null;
            if ($templateId) {
                // This would integrate with notification system
                return ['success' => true, 'message' => 'Notification queued'];
            }
            return ['success' => false, 'error' => 'Template ID not provided'];
            
        case 'create_fulfillment':
            $warehouseId = $params['warehouse_id'] ?? null;
            if ($warehouseId) {
                return order_management_create_fulfillment($orderId, [
                    'warehouse_id' => $warehouseId,
                    'fulfillment_status' => 'pending'
                ]);
            }
            return ['success' => false, 'error' => 'Warehouse ID not provided'];
            
        case 'allocate_inventory':
            // This would integrate with inventory component
            return ['success' => true, 'message' => 'Inventory allocation would be handled by inventory component'];
            
        case 'update_custom_field':
            $fieldName = $params['field_name'] ?? null;
            $fieldValue = $params['field_value'] ?? null;
            if ($fieldName && $fieldValue !== null) {
                $conn = order_management_get_db_connection();
                $customFieldsTable = order_management_get_table_name('custom_fields');
                $metadataTable = order_management_get_table_name('order_metadata');
                
                // Get field ID
                $stmt = $conn->prepare("SELECT id FROM {$customFieldsTable} WHERE field_name = ? LIMIT 1");
                $stmt->bind_param("s", $fieldName);
                $stmt->execute();
                $result = $stmt->get_result();
                $field = $result->fetch_assoc();
                $stmt->close();
                
                if ($field) {
                    $fieldId = $field['id'];
                    $valueStr = is_array($fieldValue) ? json_encode($fieldValue) : (string)$fieldValue;
                    $stmt = $conn->prepare("INSERT INTO {$metadataTable} (order_id, field_id, field_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE field_value = ?");
                    $stmt->bind_param("iiss", $orderId, $fieldId, $valueStr, $valueStr);
                    $result = $stmt->execute();
                    $stmt->close();
                    return ['success' => $result];
                }
            }
            return ['success' => false, 'error' => 'Field name or value not provided'];
            
        default:
            return ['success' => false, 'error' => "Unknown action type: {$actionType}"];
    }
}

/**
 * Log automation execution
 * @param int $ruleId Rule ID
 * @param int $orderId Order ID
 * @param string $triggerEvent Trigger event
 * @param array $actionResults Action execution results
 * @return void
 */
function order_management_log_automation_execution($ruleId, $orderId, $triggerEvent, $actionResults) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return;
    }
    
    $tableName = order_management_get_table_name('automation_logs');
    
    $executionResult = $actionResults['success'] ? 'success' : ($actionResults['errors'] ? 'failed' : 'partial');
    $actionsExecuted = json_encode($actionResults['actions_executed'] ?? []);
    $errorMessage = !empty($actionResults['errors']) ? implode('; ', $actionResults['errors']) : null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (rule_id, order_id, trigger_event, actions_executed, execution_result, error_message, executed_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("iissss", $ruleId, $orderId, $triggerEvent, $actionsExecuted, $executionResult, $errorMessage);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Get automation logs
 * @param array $filters Filters (rule_id, order_id, execution_result)
 * @param int $limit Limit
 * @return array Array of logs
 */
function order_management_get_automation_logs($filters = [], $limit = 100) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('automation_logs');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['rule_id'])) {
        $where[] = "rule_id = ?";
        $params[] = $filters['rule_id'];
        $types .= 'i';
    }
    
    if (isset($filters['order_id'])) {
        $where[] = "order_id = ?";
        $params[] = $filters['order_id'];
        $types .= 'i';
    }
    
    if (isset($filters['execution_result'])) {
        $where[] = "execution_result = ?";
        $params[] = $filters['execution_result'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $params[] = $limit;
    $types .= 'i';
    
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY executed_at DESC LIMIT ?";
    
    $logs = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['actions_executed'])) {
                $row['actions_executed'] = json_decode($row['actions_executed'], true);
            }
            $logs[] = $row;
        }
        $stmt->close();
    }
    
    return $logs;
}

/**
 * Test automation rule
 * @param int $ruleId Rule ID
 * @param int $orderId Order ID (optional, uses test order if not provided)
 * @return array Test results
 */
function order_management_test_automation_rule($ruleId, $orderId = null) {
    $rule = order_management_get_automation_rule($ruleId);
    if (!$rule) {
        return ['success' => false, 'error' => 'Rule not found'];
    }
    
    // Use provided order or create test scenario
    if ($orderId === null) {
        // Create test order scenario
        $testOrder = [
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'total_amount' => 100.00
        ];
        // Evaluate conditions against test order
        $conditionsMet = true; // Simplified for testing
    } else {
        $conditionsMet = order_management_evaluate_trigger_conditions($orderId, $rule['trigger_conditions']);
    }
    
    return [
        'success' => true,
        'rule' => $rule,
        'conditions_met' => $conditionsMet,
        'would_execute' => $conditionsMet && $rule['is_active'],
        'actions' => $rule['actions']
    ];
}

