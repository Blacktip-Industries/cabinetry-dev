<?php
/**
 * Order Management Component - Collection Automation System
 * Automated scheduling, reminders, capacity management, workflows
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/collection-management.php';

/**
 * Auto-schedule collection for an order
 * @param int $orderId Order ID
 * @return array Result
 */
function order_management_auto_schedule_collection($orderId) {
    if (!function_exists('commerce_get_order')) {
        return ['success' => false, 'error' => 'Commerce functions not available'];
    }
    
    $order = commerce_get_order($orderId);
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found'];
    }
    
    $completionDate = $order['manual_completion_date'] ?? null;
    if (!$completionDate) {
        return ['success' => false, 'error' => 'No completion date set'];
    }
    
    // Calculate available windows
    $windows = order_management_calculate_collection_windows($completionDate);
    if (empty($windows)) {
        return ['success' => false, 'error' => 'No collection windows available'];
    }
    
    // Select first available window
    $selectedWindow = $windows[0];
    $startDateTime = $selectedWindow['date'] . ' ' . $selectedWindow['start'];
    $endDateTime = $selectedWindow['date'] . ' ' . $selectedWindow['end'];
    
    // Set collection window
    $result = order_management_set_manual_collection_window($orderId, $startDateTime, $endDateTime);
    
    if ($result) {
        // Log automation
        order_management_log_automation('auto_schedule_collection', $orderId, [
            'window_start' => $startDateTime,
            'window_end' => $endDateTime
        ]);
        
        return ['success' => true, 'window_start' => $startDateTime, 'window_end' => $endDateTime];
    }
    
    return ['success' => false, 'error' => 'Failed to set collection window'];
}

/**
 * Send collection reminders
 * @param string $reminderType Reminder type ('7_days', '3_days', '24_hours', '2_hours')
 * @param int|null $hoursBefore Hours before collection (alternative to reminderType)
 * @return int Number of reminders sent
 */
function order_management_send_collection_reminders($reminderType = null, $hoursBefore = null) {
    if (!function_exists('commerce_get_db_connection')) {
        return 0;
    }
    
    $commerceConn = commerce_get_db_connection();
    if (!$commerceConn) {
        return 0;
    }
    
    // Calculate target time
    if ($hoursBefore !== null) {
        $targetTime = date('Y-m-d H:i:s', strtotime("+{$hoursBefore} hours"));
    } else {
        switch ($reminderType) {
            case '7_days':
                $targetTime = date('Y-m-d H:i:s', strtotime('+7 days'));
                break;
            case '3_days':
                $targetTime = date('Y-m-d H:i:s', strtotime('+3 days'));
                break;
            case '24_hours':
                $targetTime = date('Y-m-d H:i:s', strtotime('+24 hours'));
                break;
            case '2_hours':
                $targetTime = date('Y-m-d H:i:s', strtotime('+2 hours'));
                break;
            default:
                return 0;
        }
    }
    
    // Find orders with collection windows starting at target time
    $ordersTable = commerce_get_table_name('orders');
    $stmt = $commerceConn->prepare("SELECT id, customer_name, customer_email, customer_phone, collection_window_start, collection_confirmed_at FROM {$ordersTable} WHERE collection_window_start BETWEEN DATE_SUB(?, INTERVAL 1 HOUR) AND DATE_ADD(?, INTERVAL 1 HOUR) AND collection_status = 'pending' AND collection_confirmed_at IS NULL");
    if ($stmt) {
        $stmt->bind_param("ss", $targetTime, $targetTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        
        $sent = 0;
        foreach ($orders as $order) {
            // Send SMS reminder if SMS gateway available
            if (function_exists('sms_gateway_send_template') && !empty($order['customer_phone'])) {
                $message = "Reminder: Your order collection is scheduled for " . date('Y-m-d H:i', strtotime($order['collection_window_start'])) . ". Please confirm your collection window.";
                sms_gateway_send($order['customer_phone'], $message, [
                    'component_name' => 'order_management',
                    'component_reference_id' => $order['id']
                ]);
                $sent++;
            }
        }
        
        return $sent;
    }
    
    return 0;
}

/**
 * Manage collection capacity for a date
 * @param string $date Date (YYYY-MM-DD)
 * @return array Capacity management result
 */
function order_management_manage_collection_capacity($date) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get all collection windows for this date
    $windows = order_management_get_collection_windows(null, $date);
    
    $capacityTable = order_management_get_table_name('collection_capacity');
    $managed = 0;
    
    foreach ($windows as $window) {
        $timeSlot = $window['window_start'] . '-' . $window['window_end'];
        
        // Check if capacity record exists
        $stmt = $conn->prepare("SELECT id FROM {$capacityTable} WHERE specific_date = ? AND time_slot_start = ? AND time_slot_end = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("sss", $date, $window['window_start'], $window['window_end']);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->fetch_assoc();
            $stmt->close();
            
            if (!$exists) {
                // Create default capacity record
                $maxCapacity = 10; // Default
                $stmt = $conn->prepare("INSERT INTO {$capacityTable} (specific_date, time_slot_start, time_slot_end, max_capacity, current_bookings) VALUES (?, ?, ?, ?, 0)");
                if ($stmt) {
                    $stmt->bind_param("sssi", $date, $window['window_start'], $window['window_end'], $maxCapacity);
                    $stmt->execute();
                    $stmt->close();
                    $managed++;
                }
            }
        }
    }
    
    return ['success' => true, 'managed_slots' => $managed];
}

/**
 * Resolve collection conflicts
 * @param int $orderId Order ID
 * @return array Resolution result
 */
function order_management_resolve_collection_conflicts($orderId) {
    if (!function_exists('commerce_get_order')) {
        return ['success' => false, 'error' => 'Commerce functions not available'];
    }
    
    $order = commerce_get_order($orderId);
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found'];
    }
    
    $collectionDate = $order['collection_window_start'] ?? null;
    if (!$collectionDate) {
        return ['success' => false, 'error' => 'No collection window set'];
    }
    
    $date = date('Y-m-d', strtotime($collectionDate));
    $time = date('H:i:s', strtotime($collectionDate));
    
    // Check for conflicts (capacity, custom office hours, etc.)
    $availability = order_management_check_collection_availability($date, $time);
    
    if (!$availability['available']) {
        // Try to find alternative window
        $windows = order_management_calculate_collection_windows($date);
        if (!empty($windows)) {
            $alternativeWindow = $windows[0];
            $newStart = $alternativeWindow['date'] . ' ' . $alternativeWindow['start'];
            $newEnd = $alternativeWindow['date'] . ' ' . $alternativeWindow['end'];
            
            order_management_set_manual_collection_window($orderId, $newStart, $newEnd);
            
            return [
                'success' => true,
                'conflict_resolved' => true,
                'new_window_start' => $newStart,
                'new_window_end' => $newEnd,
                'reason' => $availability['reason']
            ];
        }
        
        return ['success' => false, 'error' => $availability['reason'], 'conflict_resolved' => false];
    }
    
    return ['success' => true, 'conflict_resolved' => false, 'no_conflicts' => true];
}

/**
 * Auto-assign staff to collection
 * @param int $orderId Order ID
 * @return array Assignment result
 */
function order_management_auto_assign_staff($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get staff availability
    $staffTable = order_management_get_table_name('collection_staff');
    $availabilityTable = order_management_get_table_name('collection_staff_availability');
    
    if (!function_exists('commerce_get_order')) {
        return ['success' => false, 'error' => 'Commerce functions not available'];
    }
    
    $order = commerce_get_order($orderId);
    if (!$order || !$order['collection_window_start']) {
        return ['success' => false, 'error' => 'No collection window set'];
    }
    
    $collectionDate = date('Y-m-d', strtotime($order['collection_window_start']));
    $collectionTime = date('H:i:s', strtotime($order['collection_window_start']));
    
    // Find available staff
    $stmt = $conn->prepare("SELECT s.id, s.staff_name, COUNT(a.id) as current_assignments 
        FROM {$staffTable} s 
        LEFT JOIN {$availabilityTable} a ON s.id = a.staff_id AND a.availability_date = ? 
        WHERE s.is_active = 1 
        GROUP BY s.id 
        ORDER BY current_assignments ASC 
        LIMIT 1");
    
    if ($stmt) {
        $stmt->bind_param("s", $collectionDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();
        
        if ($staff) {
            $assigned = order_management_assign_staff_to_collection($orderId, $staff['id']);
            if ($assigned) {
                return ['success' => true, 'staff_id' => $staff['id'], 'staff_name' => $staff['staff_name']];
            }
        }
    }
    
    return ['success' => false, 'error' => 'No available staff found'];
}

/**
 * Auto-optimize collection routes
 * @param string $date Date (YYYY-MM-DD)
 * @return array Optimization result
 */
function order_management_auto_optimize_routes($date) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if (!function_exists('commerce_get_db_connection')) {
        return ['success' => false, 'error' => 'Commerce functions not available'];
    }
    
    $commerceConn = commerce_get_db_connection();
    if (!$commerceConn) {
        return ['success' => false, 'error' => 'Commerce database connection failed'];
    }
    
    // Get all collections for this date
    $ordersTable = commerce_get_table_name('orders');
    $stmt = $commerceConn->prepare("SELECT id, collection_window_start, shipping_address FROM {$ordersTable} WHERE DATE(collection_window_start) = ? AND collection_status = 'pending'");
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $collections = [];
        while ($row = $result->fetch_assoc()) {
            $collections[] = $row;
        }
        $stmt->close();
        
        // Simple optimization: sort by time
        usort($collections, function($a, $b) {
            return strtotime($a['collection_window_start']) - strtotime($b['collection_window_start']);
        });
        
        // Store optimized route
        $routeTable = order_management_get_table_name('collection_route_optimization');
        $routeOrder = 1;
        foreach ($collections as $collection) {
            $stmt = $conn->prepare("INSERT INTO {$routeTable} (route_date, order_id, route_order, optimized_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE route_order = ?");
            if ($stmt) {
                $stmt->bind_param("siii", $date, $collection['id'], $routeOrder, $routeOrder);
                $stmt->execute();
                $stmt->close();
            }
            $routeOrder++;
        }
        
        return ['success' => true, 'optimized_collections' => count($collections)];
    }
    
    return ['success' => false, 'error' => 'Failed to optimize routes'];
}

/**
 * Process automation workflow
 * @param int $workflowId Workflow ID
 * @param int $orderId Order ID
 * @return array Result
 */
function order_management_process_automation_workflow($workflowId, $orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $workflowsTable = order_management_get_table_name('collection_workflows');
    $stmt = $conn->prepare("SELECT * FROM {$workflowsTable} WHERE id = ? AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $workflowId);
        $stmt->execute();
        $result = $stmt->get_result();
        $workflow = $result->fetch_assoc();
        $stmt->close();
        
        if ($workflow) {
            $steps = json_decode($workflow['workflow_steps_json'], true) ?? [];
            
            foreach ($steps as $step) {
                $stepType = $step['type'] ?? '';
                $stepConfig = $step['config'] ?? [];
                
                switch ($stepType) {
                    case 'send_reminder':
                        order_management_send_collection_reminders($stepConfig['reminder_type'] ?? null, $stepConfig['hours_before'] ?? null);
                        break;
                        
                    case 'assign_staff':
                        order_management_auto_assign_staff($orderId);
                        break;
                        
                    case 'check_capacity':
                        if (isset($stepConfig['date'])) {
                            order_management_manage_collection_capacity($stepConfig['date']);
                        }
                        break;
                        
                    case 'resolve_conflicts':
                        order_management_resolve_collection_conflicts($orderId);
                        break;
                }
            }
            
            // Log workflow execution
            order_management_log_automation('workflow_executed', $orderId, [
                'workflow_id' => $workflowId,
                'workflow_name' => $workflow['workflow_name']
            ]);
            
            return ['success' => true, 'workflow_name' => $workflow['workflow_name']];
        }
    }
    
    return ['success' => false, 'error' => 'Workflow not found'];
}

/**
 * Get automation rules
 * @param string|null $ruleType Rule type or null for all
 * @return array Rules
 */
function order_management_get_automation_rules($ruleType = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('collection_automation_rules');
    $sql = "SELECT * FROM {$tableName} WHERE is_active = 1";
    
    if ($ruleType) {
        $sql .= " AND rule_type = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $ruleType);
            $stmt->execute();
            $result = $stmt->get_result();
            $rules = [];
            while ($row = $result->fetch_assoc()) {
                $rules[] = $row;
            }
            $stmt->close();
            return $rules;
        }
    } else {
        $sql .= " ORDER BY priority ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $rules = [];
            while ($row = $result->fetch_assoc()) {
                $rules[] = $row;
            }
            $stmt->close();
            return $rules;
        }
    }
    
    return [];
}

/**
 * Create automation rule
 * @param array $ruleData Rule data
 * @return array Result
 */
function order_management_create_automation_rule($ruleData) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('collection_automation_rules');
    
    $ruleName = $ruleData['rule_name'] ?? '';
    $ruleType = $ruleData['rule_type'] ?? 'trigger';
    $triggerEvent = $ruleData['trigger_event'] ?? '';
    $conditionsJson = json_encode($ruleData['conditions'] ?? []);
    $actionsJson = json_encode($ruleData['actions'] ?? []);
    $priority = (int)($ruleData['priority'] ?? 0);
    $isActive = isset($ruleData['is_active']) ? (int)$ruleData['is_active'] : 1;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (rule_name, rule_type, trigger_event, conditions_json, actions_json, priority, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssssii", $ruleName, $ruleType, $triggerEvent, $conditionsJson, $actionsJson, $priority, $isActive);
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
 * Trigger automation event
 * @param string $eventType Event type
 * @param int $orderId Order ID
 * @param array $data Event data
 * @return int Number of rules triggered
 */
function order_management_trigger_automation_event($eventType, $orderId, $data = []) {
    $rules = order_management_get_automation_rules();
    $triggered = 0;
    
    foreach ($rules as $rule) {
        if ($rule['trigger_event'] === $eventType) {
            $conditions = json_decode($rule['conditions_json'], true) ?? [];
            $actions = json_decode($rule['actions_json'], true) ?? [];
            
            // Check conditions
            $conditionsMet = true;
            foreach ($conditions as $condition) {
                $conditionType = $condition['type'] ?? '';
                $conditionValue = $condition['value'] ?? '';
                
                // Simple condition checking (can be expanded)
                if ($conditionType === 'order_status' && isset($data['order_status'])) {
                    if ($data['order_status'] !== $conditionValue) {
                        $conditionsMet = false;
                        break;
                    }
                }
            }
            
            if ($conditionsMet) {
                // Execute actions
                foreach ($actions as $action) {
                    $actionType = $action['type'] ?? '';
                    $actionConfig = $action['config'] ?? [];
                    
                    switch ($actionType) {
                        case 'send_reminder':
                            order_management_send_collection_reminders($actionConfig['reminder_type'] ?? null);
                            break;
                            
                        case 'auto_schedule':
                            order_management_auto_schedule_collection($orderId);
                            break;
                            
                        case 'assign_staff':
                            order_management_auto_assign_staff($orderId);
                            break;
                            
                        case 'execute_workflow':
                            if (isset($actionConfig['workflow_id'])) {
                                order_management_process_automation_workflow($actionConfig['workflow_id'], $orderId);
                            }
                            break;
                    }
                }
                
                $triggered++;
                
                // Log automation
                order_management_log_automation('rule_triggered', $orderId, [
                    'rule_id' => $rule['id'],
                    'event_type' => $eventType
                ]);
            }
        }
    }
    
    return $triggered;
}

/**
 * Log automation action
 * @param string $actionType Action type
 * @param int $orderId Order ID
 * @param array $data Additional data
 * @return bool Success
 */
function order_management_log_automation($actionType, $orderId, $data = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('collection_automation_log');
    $dataJson = json_encode($data);
    $executedBy = $_SESSION['user_id'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (action_type, order_id, data_json, executed_by, executed_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("sisi", $actionType, $orderId, $dataJson, $executedBy);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

