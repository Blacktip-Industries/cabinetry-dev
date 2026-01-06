<?php
/**
 * Payment Processing Component - Fraud Detection Engine
 * Handles fraud detection and risk scoring
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/payment-method-rules.php'; // For evaluate_rule_conditions

/**
 * Evaluate fraud rules and calculate risk score
 * @param array $transactionData Transaction data
 * @return array Fraud evaluation result
 */
function payment_processing_evaluate_fraud($transactionData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return [
            'success' => false,
            'risk_score' => 0,
            'status' => 'clean'
        ];
    }
    
    try {
        $tableName = payment_processing_get_table_name('fraud_rules');
        $result = $conn->query("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY priority DESC");
        
        $riskScore = 0;
        $triggeredRules = [];
        $action = 'allow';
        
        while ($row = $result->fetch_assoc()) {
            $ruleConfig = json_decode($row['rule_config'], true);
            
            // Check if rule conditions match
            if (payment_processing_evaluate_rule_conditions($ruleConfig['conditions'] ?? [], $transactionData)) {
                $triggeredRules[] = [
                    'rule_id' => $row['id'],
                    'rule_name' => $row['rule_name'],
                    'rule_type' => $row['rule_type']
                ];
                
                // Add to risk score
                $riskScore += $ruleConfig['risk_points'] ?? 10;
                
                // Check if action should be updated
                if ($row['action'] === 'block' && $action !== 'blocked') {
                    $action = 'blocked';
                } elseif ($row['action'] === 'review' && $action === 'allow') {
                    $action = 'review';
                }
            }
        }
        
        // Log fraud event if rules triggered
        if (!empty($triggeredRules)) {
            $transactionId = $transactionData['id'] ?? null;
            if ($transactionId) {
                payment_processing_log_fraud_event($transactionId, $triggeredRules, $riskScore, $action);
            }
        }
        
        return [
            'success' => true,
            'risk_score' => min($riskScore, 100), // Cap at 100
            'status' => $action,
            'triggered_rules' => $triggeredRules
        ];
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error evaluating fraud: " . $e->getMessage());
        return [
            'success' => false,
            'risk_score' => 0,
            'status' => 'clean'
        ];
    }
}

/**
 * Log fraud detection event
 * @param int $transactionId Transaction ID
 * @param array $triggeredRules Triggered rules
 * @param float $riskScore Risk score
 * @param string $action Action taken
 * @return bool Success
 */
function payment_processing_log_fraud_event($transactionId, $triggeredRules, $riskScore, $action) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = payment_processing_get_table_name('fraud_events');
        
        foreach ($triggeredRules as $rule) {
            $details = [
                'risk_score' => $riskScore,
                'action' => $action,
                'all_triggered_rules' => $triggeredRules
            ];
            
            $stmt = $conn->prepare("INSERT INTO {$tableName} (transaction_id, rule_id, event_type, risk_score, details, action_taken) VALUES (?, ?, 'fraud_detected', ?, ?, ?)");
            $detailsJson = json_encode($details);
            $stmt->bind_param("iidss", $transactionId, $rule['rule_id'], $riskScore, $detailsJson, $action);
            $stmt->execute();
            $stmt->close();
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error logging fraud event: " . $e->getMessage());
        return false;
    }
}

/**
 * Create fraud rule
 * @param array $ruleData Rule data
 * @return array Result with rule ID
 */
function payment_processing_create_fraud_rule($ruleData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('fraud_rules');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (rule_name, rule_type, rule_config, is_active, priority, action) VALUES (?, ?, ?, ?, ?, ?)");
        
        $ruleConfigJson = json_encode($ruleData['rule_config'] ?? []);
        $isActive = $ruleData['is_active'] ?? 1;
        $priority = $ruleData['priority'] ?? 0;
        $action = $ruleData['action'] ?? 'review';
        
        $stmt->bind_param("sssiis",
            $ruleData['rule_name'],
            $ruleData['rule_type'],
            $ruleConfigJson,
            $isActive,
            $priority,
            $action
        );
        $stmt->execute();
        $ruleId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'rule_id' => $ruleId];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

