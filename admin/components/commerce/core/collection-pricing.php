<?php
/**
 * Commerce Component - Collection Pricing Rules System
 * Functions for calculating Early Bird/After Hours charges with violation integration
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/collection-violations.php';

/**
 * Calculate collection charge (Early Bird or After Hours)
 * @param int $orderId Order ID
 * @param string $collectionType Collection type ('early_bird' or 'after_hours')
 * @param int|null $customerId Customer ID
 * @return array Charge calculation result
 */
function commerce_calculate_collection_charge($orderId, $collectionType, $customerId = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [
            'charge_amount' => 0.00,
            'rule_id' => null,
            'calculation_details' => [],
            'violation_message' => null
        ];
    }
    
    // Get order data
    $order = null;
    if (function_exists('commerce_get_order')) {
        $order = commerce_get_order($orderId);
    }
    
    if (!$order) {
        return [
            'charge_amount' => 0.00,
            'rule_id' => null,
            'calculation_details' => [],
            'violation_message' => null
        ];
    }
    
    // Get customer violation score if customer ID provided
    $customerViolationScore = null;
    if ($customerId) {
        $customerViolationScore = commerce_get_customer_violation_score($customerId);
    }
    
    // Get applicable pricing rules
    $applicableRules = commerce_get_applicable_collection_pricing_rules($collectionType, $order, $customerViolationScore);
    
    if (empty($applicableRules)) {
        return [
            'charge_amount' => 0.00,
            'rule_id' => null,
            'calculation_details' => [],
            'violation_message' => null
        ];
    }
    
    // Use first matching rule
    $rule = $applicableRules[0];
    $config = json_decode($rule['config_json'], true) ?? [];
    
    $chargeAmount = 0.00;
    $calculationDetails = [];
    
    switch ($rule['calculation_type']) {
        case 'fixed':
            $chargeAmount = (float)($config['charge_amount'] ?? 0.00);
            break;
        case 'percentage':
            $baseAmount = $order['total_amount'] ?? 0.00;
            $percentage = (float)($config['charge_percentage'] ?? 0.00);
            $chargeAmount = $baseAmount * ($percentage / 100);
            break;
        case 'tiered':
            // Get tier based on violation score
            if ($customerViolationScore && isset($customerViolationScore['active_score'])) {
                $tiersTable = commerce_get_table_name('collection_pricing_tiers');
                $stmt = $conn->prepare("SELECT * FROM {$tiersTable} WHERE rule_id = ? AND violation_score_min <= ? AND (violation_score_max IS NULL OR violation_score_max >= ?) ORDER BY tier_order ASC LIMIT 1");
                if ($stmt) {
                    $violationScore = $customerViolationScore['active_score'];
                    $stmt->bind_param("iii", $rule['id'], $violationScore, $violationScore);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $tier = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($tier) {
                        if ($tier['charge_percentage'] > 0) {
                            $baseAmount = $order['total_amount'] ?? 0.00;
                            $chargeAmount = $baseAmount * ($tier['charge_percentage'] / 100);
                        } else {
                            $chargeAmount = (float)$tier['charge_amount'];
                        }
                    }
                }
            }
            break;
        case 'formula':
            // Formula-based calculation (if formula_builder available)
            // TODO: Implement formula calculation
            break;
    }
    
    $calculationDetails['base_amount'] = $order['total_amount'] ?? 0.00;
    $calculationDetails['calculation_type'] = $rule['calculation_type'];
    $calculationDetails['violation_score'] = $customerViolationScore['active_score'] ?? 0;
    
    // Get violation message if applicable
    $violationMessage = null;
    if ($customerId && $chargeAmount > 0) {
        $violationMessage = commerce_get_violation_pricing_message($customerId, $collectionType, $chargeAmount);
    }
    
    return [
        'charge_amount' => round($chargeAmount, 2),
        'rule_id' => $rule['id'],
        'calculation_details' => $calculationDetails,
        'violation_message' => $violationMessage
    ];
}

/**
 * Get applicable collection pricing rules
 * @param string $collectionType Collection type
 * @param array $orderData Order data
 * @param array|null $customerViolationScore Customer violation score
 * @return array Applicable rules
 */
function commerce_get_applicable_collection_pricing_rules($collectionType, $orderData, $customerViolationScore = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('collection_pricing_rules');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE collection_type = ? AND is_active = 1 ORDER BY priority ASC");
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("s", $collectionType);
    $stmt->execute();
    $result = $stmt->get_result();
    $allRules = [];
    while ($row = $result->fetch_assoc()) {
        $allRules[] = $row;
    }
    $stmt->close();
    
    // Filter rules by conditions
    $applicableRules = [];
    foreach ($allRules as $rule) {
        if (commerce_check_collection_pricing_conditions($rule, $orderData, $customerViolationScore)) {
            $applicableRules[] = $rule;
        }
    }
    
    return $applicableRules;
}

/**
 * Evaluate pricing rule conditions
 * @param array $rule Rule data
 * @param array $orderData Order data
 * @param array|null $customerViolationScore Customer violation score
 * @return bool True if conditions met
 */
function commerce_check_collection_pricing_conditions($rule, $orderData, $customerViolationScore = null) {
    // Check day of week
    if ($rule['day_of_week'] !== null) {
        $dayOfWeek = date('w');
        if ($dayOfWeek != $rule['day_of_week']) {
            return false;
        }
    }
    
    // Check specific date
    if ($rule['specific_date'] !== null) {
        $today = date('Y-m-d');
        if ($today != $rule['specific_date']) {
            return false;
        }
    }
    
    // Check time range
    if ($rule['time_start'] !== null && $rule['time_end'] !== null) {
        $currentTime = date('H:i:s');
        if ($currentTime < $rule['time_start'] || $currentTime > $rule['time_end']) {
            return false;
        }
    }
    
    // Check customer tier
    if ($rule['customer_tier'] !== null) {
        // TODO: Get customer tier from order data
        // For now, skip this check
    }
    
    // Check violation score
    if ($customerViolationScore && $rule['violation_score_min'] !== null) {
        $activeScore = $customerViolationScore['active_score'] ?? 0;
        if ($activeScore < $rule['violation_score_min']) {
            return false;
        }
    }
    
    if ($customerViolationScore && $rule['violation_score_max'] !== null) {
        $activeScore = $customerViolationScore['active_score'] ?? 0;
        if ($activeScore > $rule['violation_score_max']) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get collection pricing message for customer
 * @param int $orderId Order ID
 * @param string $collectionType Collection type
 * @param float $chargeAmount Charge amount
 * @param string|null $violationMessage Violation message
 * @return string Formatted message
 */
function commerce_get_collection_pricing_message($orderId, $collectionType, $chargeAmount, $violationMessage = null) {
    $message = '';
    
    if ($chargeAmount > 0) {
        $message = "A charge of $" . number_format($chargeAmount, 2) . " applies for this " . str_replace('_', ' ', $collectionType) . " collection.";
        
        if ($violationMessage) {
            $message .= " " . $violationMessage;
        }
    } else {
        $message = "No charge applies for this " . str_replace('_', ' ', $collectionType) . " collection.";
    }
    
    return $message;
}

/**
 * Create collection pricing rule
 * @param array $ruleData Rule data
 * @return array Result
 */
function commerce_create_collection_pricing_rule($ruleData) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('collection_pricing_rules');
    
    $ruleName = $ruleData['rule_name'] ?? '';
    $description = $ruleData['description'] ?? null;
    $collectionType = $ruleData['collection_type'] ?? 'early_bird';
    $calculationType = $ruleData['calculation_type'] ?? 'fixed';
    $dayOfWeek = $ruleData['day_of_week'] ?? null;
    $specificDate = $ruleData['specific_date'] ?? null;
    $timeStart = $ruleData['time_start'] ?? null;
    $timeEnd = $ruleData['time_end'] ?? null;
    $customerTier = $ruleData['customer_tier'] ?? null;
    $violationScoreMin = $ruleData['violation_score_min'] ?? null;
    $violationScoreMax = $ruleData['violation_score_max'] ?? null;
    $chargeAmount = (float)($ruleData['charge_amount'] ?? 0.00);
    $chargePercentage = (float)($ruleData['charge_percentage'] ?? 0.00);
    $configJson = json_encode($ruleData['config'] ?? []);
    $priority = (int)($ruleData['priority'] ?? 0);
    $isActive = isset($ruleData['is_active']) ? (int)$ruleData['is_active'] : 1;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (rule_name, description, collection_type, calculation_type, day_of_week, specific_date, time_start, time_end, customer_tier, violation_score_min, violation_score_max, charge_amount, charge_percentage, config_json, priority, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssissssiiiddsii", $ruleName, $description, $collectionType, $calculationType, $dayOfWeek, $specificDate, $timeStart, $timeEnd, $customerTier, $violationScoreMin, $violationScoreMax, $chargeAmount, $chargePercentage, $configJson, $priority, $isActive);
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
 * Update collection pricing rule
 * @param int $ruleId Rule ID
 * @param array $ruleData Rule data
 * @return array Result
 */
function commerce_update_collection_pricing_rule($ruleId, $ruleData) {
    // Similar to create, but with UPDATE
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('collection_pricing_rules');
    
    // Build update query (similar pattern to pricing display rules update)
    $updates = [];
    $params = [];
    $types = '';
    
    $fields = [
        'rule_name' => 's', 'description' => 's', 'collection_type' => 's', 'calculation_type' => 's',
        'day_of_week' => 'i', 'specific_date' => 's', 'time_start' => 's', 'time_end' => 's',
        'customer_tier' => 's', 'violation_score_min' => 'i', 'violation_score_max' => 'i',
        'charge_amount' => 'd', 'charge_percentage' => 'd', 'config_json' => 's', 'priority' => 'i', 'is_active' => 'i'
    ];
    
    foreach ($fields as $field => $type) {
        if (isset($ruleData[$field])) {
            $updates[] = "{$field} = ?";
            $params[] = &$ruleData[$field];
            $types .= $type;
        }
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $sql = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = &$ruleId;
    $types .= 'i';
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $bindParams = [$types];
        foreach ($params as &$param) {
            $bindParams[] = &$param;
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        
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
 * Delete collection pricing rule
 * @param int $ruleId Rule ID
 * @return bool Success
 */
function commerce_delete_collection_pricing_rule($ruleId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('collection_pricing_rules');
    $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = 0 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $ruleId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Get collection pricing rules
 * @param string|null $collectionType Collection type filter
 * @param bool $activeOnly Only get active rules
 * @return array Rules
 */
function commerce_get_collection_pricing_rules($collectionType = null, $activeOnly = true) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('collection_pricing_rules');
    
    $sql = "SELECT * FROM {$tableName}";
    $conditions = [];
    $params = [];
    $types = '';
    
    if ($collectionType !== null) {
        $conditions[] = "collection_type = ?";
        $params[] = &$collectionType;
        $types .= 's';
    }
    
    if ($activeOnly) {
        $conditions[] = "is_active = 1";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY priority ASC, id DESC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $bindParams = [$types];
            foreach ($params as &$param) {
                $bindParams[] = &$param;
            }
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $rules = [];
        while ($row = $result->fetch_assoc()) {
            $rules[] = $row;
        }
        $stmt->close();
        return $rules;
    }
    
    return [];
}

