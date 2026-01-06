<?php
/**
 * Payment Processing Component - Payment Method Rules Engine
 * Handles conditional payment method availability
 */

require_once __DIR__ . '/database.php';

/**
 * Evaluate payment method rules and get available methods
 * @param array $context Context data (account_id, amount, currency, etc.)
 * @param array $gatewayMethods Methods supported by gateway
 * @return array Available payment methods
 */
function payment_processing_evaluate_payment_method_rules($context, $gatewayMethods = []) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return $gatewayMethods;
    }
    
    try {
        $tableName = payment_processing_get_table_name('payment_method_rules');
        $result = $conn->query("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY priority DESC");
        
        $availableMethods = $gatewayMethods;
        
        while ($row = $result->fetch_assoc()) {
            $conditions = json_decode($row['conditions'], true);
            $allowedMethods = json_decode($row['allowed_methods'], true);
            $blockedMethods = json_decode($row['blocked_methods'] ?? '[]', true);
            
            // Check if rule conditions match
            if (payment_processing_evaluate_rule_conditions($conditions, $context)) {
                // Apply allowed methods (intersection)
                if (!empty($allowedMethods)) {
                    $availableMethods = array_intersect($availableMethods, $allowedMethods);
                }
                
                // Remove blocked methods
                if (!empty($blockedMethods)) {
                    $availableMethods = array_diff($availableMethods, $blockedMethods);
                }
            }
        }
        
        return array_values($availableMethods);
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error evaluating payment method rules: " . $e->getMessage());
        return $gatewayMethods;
    }
}

/**
 * Evaluate rule conditions
 * @param array $conditions Conditions array
 * @param array $context Context data
 * @return bool True if conditions match
 */
function payment_processing_evaluate_rule_conditions($conditions, $context) {
    foreach ($conditions as $condition) {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;
        
        if ($field === null) {
            continue;
        }
        
        $contextValue = $context[$field] ?? null;
        
        $matches = false;
        switch ($operator) {
            case 'equals':
                $matches = $contextValue == $value;
                break;
            case 'not_equals':
                $matches = $contextValue != $value;
                break;
            case 'greater_than':
                $matches = $contextValue > $value;
                break;
            case 'less_than':
                $matches = $contextValue < $value;
                break;
            case 'greater_equal':
                $matches = $contextValue >= $value;
                break;
            case 'less_equal':
                $matches = $contextValue <= $value;
                break;
            case 'in':
                $matches = in_array($contextValue, (array)$value);
                break;
            case 'not_in':
                $matches = !in_array($contextValue, (array)$value);
                break;
            case 'contains':
                $matches = strpos($contextValue ?? '', $value) !== false;
                break;
        }
        
        // If any condition fails (AND logic), return false
        if (!$matches) {
            return false;
        }
    }
    
    return true;
}

/**
 * Create payment method rule
 * @param array $ruleData Rule data
 * @return array Result with rule ID
 */
function payment_processing_create_payment_method_rule($ruleData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('payment_method_rules');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (rule_name, description, conditions, allowed_methods, blocked_methods, priority, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $conditionsJson = json_encode($ruleData['conditions'] ?? []);
        $allowedMethodsJson = json_encode($ruleData['allowed_methods'] ?? []);
        $blockedMethodsJson = json_encode($ruleData['blocked_methods'] ?? []);
        $priority = $ruleData['priority'] ?? 0;
        $isActive = $ruleData['is_active'] ?? 1;
        
        $stmt->bind_param("sssssii",
            $ruleData['rule_name'],
            $ruleData['description'] ?? null,
            $conditionsJson,
            $allowedMethodsJson,
            $blockedMethodsJson,
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

/**
 * Get payment method rule
 * @param int $ruleId Rule ID
 * @return array|null Rule data or null
 */
function payment_processing_get_payment_method_rule($ruleId) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('payment_method_rules');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $ruleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rule = $result->fetch_assoc();
        $stmt->close();
        
        if ($rule) {
            $rule['conditions'] = json_decode($rule['conditions'], true);
            $rule['allowed_methods'] = json_decode($rule['allowed_methods'], true);
            $rule['blocked_methods'] = json_decode($rule['blocked_methods'] ?? '[]', true);
        }
        
        return $rule;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting payment method rule: " . $e->getMessage());
        return null;
    }
}

