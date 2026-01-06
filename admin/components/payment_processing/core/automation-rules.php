<?php
/**
 * Payment Processing Component - Automation Rules Engine
 * Handles event-triggered automation
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/payment-method-rules.php'; // For evaluate_rule_conditions
require_once __DIR__ . '/refund-processor.php';
require_once __DIR__ . '/audit-logger.php';

/**
 * Process automation rules for an event
 * @param string $eventType Event type (e.g., 'payment.completed', 'payment.failed')
 * @param array $eventData Event data
 * @return array Results of executed rules
 */
function payment_processing_process_automation_rules($eventType, $eventData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('automation_rules');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE trigger_event = ? AND is_active = 1 ORDER BY priority DESC");
        $stmt->bind_param("s", $eventType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $executed = [];
        
        while ($row = $result->fetch_assoc()) {
            $conditions = json_decode($row['conditions'], true);
            $actions = json_decode($row['actions'], true);
            
            // Check if conditions match
            if (payment_processing_evaluate_rule_conditions($conditions, $eventData)) {
                // Execute actions
                $actionResults = payment_processing_execute_automation_actions($actions, $eventData);
                $executed[] = [
                    'rule_id' => $row['id'],
                    'rule_name' => $row['rule_name'],
                    'actions' => $actionResults
                ];
            }
        }
        
        $stmt->close();
        
        return [
            'success' => true,
            'executed_rules' => $executed
        ];
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error processing automation rules: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Execute automation actions
 * @param array $actions Actions to execute
 * @param array $eventData Event data
 * @return array Action results
 */
function payment_processing_execute_automation_actions($actions, $eventData) {
    $results = [];
    
    foreach ($actions as $action) {
        $actionType = $action['type'] ?? null;
        $actionParams = $action['params'] ?? [];
        
        switch ($actionType) {
            case 'auto_refund':
                if (!empty($eventData['transaction_id'])) {
                    $refundResult = payment_processing_process_refund([
                        'transaction_id' => $eventData['transaction_id'],
                        'amount' => $actionParams['amount'] ?? null,
                        'reason' => $actionParams['reason'] ?? 'Automated refund'
                    ]);
                    $results[] = ['type' => 'auto_refund', 'result' => $refundResult];
                }
                break;
                
            case 'update_status':
                if (!empty($eventData['transaction_id'])) {
                    require_once __DIR__ . '/database.php';
                    payment_processing_update_transaction($eventData['transaction_id'], [
                        'status' => $actionParams['status'] ?? 'completed'
                    ]);
                    $results[] = ['type' => 'update_status', 'success' => true];
                }
                break;
                
            case 'send_notification':
                // Trigger notification
                if (function_exists('payment_processing_send_notification')) {
                    payment_processing_send_notification(
                        $actionParams['template'] ?? null,
                        $actionParams['recipient'] ?? $eventData['customer_email'],
                        $eventData
                    );
                    $results[] = ['type' => 'send_notification', 'success' => true];
                }
                break;
                
            case 'trigger_webhook':
                // Trigger outbound webhook
                if (function_exists('payment_processing_trigger_outbound_webhook')) {
                    payment_processing_trigger_outbound_webhook(
                        $actionParams['webhook_id'] ?? null,
                        $eventData
                    );
                    $results[] = ['type' => 'trigger_webhook', 'success' => true];
                }
                break;
                
            case 'delay_capture':
                // Schedule delayed capture
                if (!empty($eventData['transaction_id'])) {
                    $delayHours = $actionParams['hours'] ?? 24;
                    $captureDate = date('Y-m-d H:i:s', strtotime("+{$delayHours} hours"));
                    // Store in metadata or separate table
                    $results[] = ['type' => 'delay_capture', 'capture_date' => $captureDate];
                }
                break;
        }
    }
    
    return $results;
}

/**
 * Create automation rule
 * @param array $ruleData Rule data
 * @return array Result with rule ID
 */
function payment_processing_create_automation_rule($ruleData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('automation_rules');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (rule_name, description, trigger_event, conditions, actions, priority, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $conditionsJson = json_encode($ruleData['conditions'] ?? []);
        $actionsJson = json_encode($ruleData['actions'] ?? []);
        $priority = $ruleData['priority'] ?? 0;
        $isActive = $ruleData['is_active'] ?? 1;
        
        $stmt->bind_param("sssssii",
            $ruleData['rule_name'],
            $ruleData['description'] ?? null,
            $ruleData['trigger_event'],
            $conditionsJson,
            $actionsJson,
            $priority,
            $isActive
        );
        $stmt->execute();
        $ruleId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'rule_id' => $ruleId];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

