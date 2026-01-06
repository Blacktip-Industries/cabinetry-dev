<?php
/**
 * Commerce Component - Rush Surcharge Calculation Engine
 * Rule-based surcharge calculation with customer activity integration
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/customer-activity.php';

/**
 * Main calculation function
 * @param int $orderId Order ID
 * @param array|null $orderData Order data (if not provided, will fetch from database)
 * @return array Calculated surcharge with applied rule ID
 */
function commerce_calculate_rush_surcharge($orderId, $orderData = null) {
    if ($orderData === null) {
        $orderData = commerce_get_order($orderId);
        if ($orderData === null) {
            return [
                'surcharge_amount' => 0.00,
                'rule_id' => null,
                'calculation_type' => null,
                'error' => 'Order not found'
            ];
        }
    }
    
    // Get customer activity data if account_id present
    $customerActivity = null;
    if (!empty($orderData['account_id'])) {
        $customerActivity = commerce_get_customer_activity_summary($orderData['account_id']);
        $customerActivity['tier'] = commerce_get_customer_tier($orderData['account_id']);
    }
    
    // Get applicable rules
    $applicableRules = commerce_get_applicable_rush_rules($orderData, $customerActivity);
    
    if (empty($applicableRules)) {
        return [
            'surcharge_amount' => 0.00,
            'rule_id' => null,
            'calculation_type' => null,
            'message' => 'No applicable rush surcharge rules found'
        ];
    }
    
    // Use first matching rule (highest priority)
    $rule = $applicableRules[0];
    $config = json_decode($rule['config_json'], true) ?? [];
    
    // Calculate base surcharge
    $surchargeAmount = 0.00;
    $calculationDetails = [];
    
    switch ($rule['calculation_type']) {
        case 'fixed':
            $surchargeAmount = commerce_calculate_fixed_surcharge($rule, $orderData, $customerActivity);
            break;
        case 'percentage_subtotal':
            $surchargeAmount = commerce_calculate_percentage_surcharge($rule, $orderData, 'subtotal', $customerActivity);
            break;
        case 'percentage_total':
            $surchargeAmount = commerce_calculate_percentage_surcharge($rule, $orderData, 'total', $customerActivity);
            break;
        case 'tiered':
            $surchargeAmount = commerce_calculate_tiered_surcharge($rule, $orderData, $customerActivity);
            break;
        case 'formula':
            // Formula-based calculation (if formula_builder available)
            $surchargeAmount = commerce_calculate_formula_surcharge($rule, $orderData, $customerActivity);
            break;
    }
    
    $calculationDetails['base_amount'] = $surchargeAmount;
    $calculationDetails['calculation_type'] = $rule['calculation_type'];
    
    // Apply customer activity discounts/waivers
    $originalAmount = $surchargeAmount;
    $surchargeAmount = commerce_apply_customer_discounts($surchargeAmount, $rule, $customerActivity);
    $discountApplied = $originalAmount - $surchargeAmount;
    
    if ($discountApplied > 0) {
        $calculationDetails['discount_applied'] = $discountApplied;
        $calculationDetails['discount_percentage'] = $originalAmount > 0 ? ($discountApplied / $originalAmount * 100) : 0;
    }
    
    // Apply min/max caps
    $surchargeAmount = commerce_apply_surcharge_caps($surchargeAmount, $rule);
    
    $calculationDetails['final_amount'] = $surchargeAmount;
    $calculationDetails['customer_tier'] = $customerActivity['tier'] ?? 'none';
    
    // Log calculation to history
    commerce_log_rush_surcharge_calculation($orderId, $rule['id'], $rule['calculation_type'], $orderData['subtotal'] ?? 0, $originalAmount, $surchargeAmount, $calculationDetails, $customerActivity);
    
    return [
        'surcharge_amount' => round($surchargeAmount, 2),
        'rule_id' => $rule['id'],
        'calculation_type' => $rule['calculation_type'],
        'calculation_details' => $calculationDetails
    ];
}

/**
 * Get matching rules by priority
 * @param array $orderData Order data
 * @param array|null $customerActivity Customer activity data
 * @return array Rules sorted by priority (highest first)
 */
function commerce_get_applicable_rush_rules($orderData, $customerActivity = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('rush_surcharge_rules');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY priority ASC");
    if (!$stmt) {
        return [];
    }
    
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
        if (commerce_check_rule_conditions($rule, $orderData, $customerActivity)) {
            $applicableRules[] = $rule;
        }
    }
    
    return $applicableRules;
}

/**
 * Evaluate rule conditions
 * @param array $rule Rule data
 * @param array $orderData Order data
 * @param array|null $customerActivity Customer activity data
 * @return bool True if all conditions match
 */
function commerce_check_rule_conditions($rule, $orderData, $customerActivity = null) {
    $conditions = json_decode($rule['conditions_json'], true) ?? [];
    
    if (empty($conditions)) {
        return true; // No conditions = applies to all
    }
    
    // Check order value range
    if (isset($conditions['order_value_min']) || isset($conditions['order_value_max'])) {
        $subtotal = $orderData['subtotal'] ?? 0;
        if (isset($conditions['order_value_min']) && $subtotal < $conditions['order_value_min']) {
            return false;
        }
        if (isset($conditions['order_value_max']) && $subtotal > $conditions['order_value_max']) {
            return false;
        }
    }
    
    // Check customer activity conditions
    if ($customerActivity !== null) {
        // Order count
        if (isset($conditions['customer_order_count_min']) && $customerActivity['order_count'] < $conditions['customer_order_count_min']) {
            return false;
        }
        if (isset($conditions['customer_order_count_max']) && $customerActivity['order_count'] > $conditions['customer_order_count_max']) {
            return false;
        }
        
        // Lifetime value
        if (isset($conditions['customer_lifetime_value_min']) && $customerActivity['lifetime_value'] < $conditions['customer_lifetime_value_min']) {
            return false;
        }
        if (isset($conditions['customer_lifetime_value_max']) && $customerActivity['lifetime_value'] > $conditions['customer_lifetime_value_max']) {
            return false;
        }
        
        // Order frequency
        if (isset($conditions['customer_order_frequency_min']) && $customerActivity['order_frequency'] < $conditions['customer_order_frequency_min']) {
            return false;
        }
        if (isset($conditions['customer_order_frequency_max']) && $customerActivity['order_frequency'] > $conditions['customer_order_frequency_max']) {
            return false;
        }
        
        // Account age
        if (isset($conditions['customer_account_age_min']) && $customerActivity['account_age'] < $conditions['customer_account_age_min']) {
            return false;
        }
        if (isset($conditions['customer_account_age_max']) && $customerActivity['account_age'] > $conditions['customer_account_age_max']) {
            return false;
        }
        
        // Customer tier
        if (isset($conditions['customer_tier'])) {
            $allowedTiers = is_array($conditions['customer_tier']) ? $conditions['customer_tier'] : [$conditions['customer_tier']];
            if (!in_array($customerActivity['tier'], $allowedTiers)) {
                return false;
            }
        }
    }
    
    // Check product categories (if specified)
    if (isset($conditions['product_categories']) && !empty($conditions['product_categories'])) {
        // This would require checking order items - simplified for now
        // TODO: Implement product category checking
    }
    
    // Check time-based conditions
    if (isset($conditions['weekend_multiplier']) || isset($conditions['holiday_multiplier'])) {
        $dayOfWeek = date('w');
        $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
        // Weekend/holiday logic can be added here
    }
    
    return true; // All conditions passed
}

/**
 * Apply customer activity discounts
 * @param float $surchargeAmount Base surcharge amount
 * @param array $rule Rule data
 * @param array|null $customerActivity Customer activity data
 * @return float Adjusted surcharge amount
 */
function commerce_apply_customer_discounts($surchargeAmount, $rule, $customerActivity = null) {
    if ($customerActivity === null || $surchargeAmount <= 0) {
        return $surchargeAmount;
    }
    
    $config = json_decode($rule['config_json'], true) ?? [];
    $discounts = $config['customer_discounts'] ?? [];
    
    if (empty($discounts)) {
        return $surchargeAmount;
    }
    
    $tier = $customerActivity['tier'] ?? 'none';
    
    // Check for tier-based discounts
    if (isset($discounts[$tier])) {
        $discount = $discounts[$tier];
        if (is_numeric($discount)) {
            // Percentage discount
            $surchargeAmount = $surchargeAmount * (1 - ($discount / 100));
        }
    }
    
    // Check for waiver thresholds
    if (isset($config['waiver_thresholds'])) {
        foreach ($config['waiver_thresholds'] as $threshold) {
            $metric = $threshold['metric'] ?? '';
            $value = $threshold['value'] ?? 0;
            
            switch ($metric) {
                case 'order_count':
                    if ($customerActivity['order_count'] >= $value) {
                        return 0.00; // Full waiver
                    }
                    break;
                case 'lifetime_value':
                    if ($customerActivity['lifetime_value'] >= $value) {
                        return 0.00; // Full waiver
                    }
                    break;
            }
        }
    }
    
    return $surchargeAmount;
}

/**
 * Calculate fixed surcharge
 * @param array $rule Rule data
 * @param array $orderData Order data
 * @param array|null $customerActivity Customer activity data
 * @return float Surcharge amount
 */
function commerce_calculate_fixed_surcharge($rule, $orderData, $customerActivity = null) {
    $config = json_decode($rule['config_json'], true) ?? [];
    return (float)($config['fixed_amount'] ?? 0.00);
}

/**
 * Calculate percentage surcharge
 * @param array $rule Rule data
 * @param array $orderData Order data
 * @param string $baseType 'subtotal' or 'total'
 * @param array|null $customerActivity Customer activity data
 * @return float Surcharge amount
 */
function commerce_calculate_percentage_surcharge($rule, $orderData, $baseType, $customerActivity = null) {
    $config = json_decode($rule['config_json'], true) ?? [];
    $percentage = (float)($config['percentage'] ?? 0.00);
    
    $baseAmount = 0.00;
    if ($baseType === 'subtotal') {
        $baseAmount = $orderData['subtotal'] ?? 0.00;
    } else {
        $baseAmount = $orderData['total_amount'] ?? 0.00;
    }
    
    return $baseAmount * ($percentage / 100);
}

/**
 * Calculate tiered surcharge
 * @param array $rule Rule data
 * @param array $orderData Order data
 * @param array|null $customerActivity Customer activity data
 * @return float Surcharge amount
 */
function commerce_calculate_tiered_surcharge($rule, $orderData, $customerActivity = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return 0.00;
    }
    
    $subtotal = $orderData['subtotal'] ?? 0.00;
    $tableName = commerce_get_table_name('rush_surcharge_tiers');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE rule_id = ? AND min_order_value <= ? AND (max_order_value IS NULL OR max_order_value >= ?) ORDER BY tier_order ASC LIMIT 1");
    if (!$stmt) {
        return 0.00;
    }
    
    $stmt->bind_param("idd", $rule['id'], $subtotal, $subtotal);
    $stmt->execute();
    $result = $stmt->get_result();
    $tier = $result->fetch_assoc();
    $stmt->close();
    
    if (!$tier) {
        return 0.00;
    }
    
    // Use percentage if set, otherwise use fixed amount
    if ($tier['percentage'] > 0) {
        return $subtotal * ($tier['percentage'] / 100);
    } else {
        return (float)$tier['fixed_amount'];
    }
}

/**
 * Calculate formula-based surcharge (if formula_builder available)
 * @param array $rule Rule data
 * @param array $orderData Order data
 * @param array|null $customerActivity Customer activity data
 * @return float Surcharge amount
 */
function commerce_calculate_formula_surcharge($rule, $orderData, $customerActivity = null) {
    $config = json_decode($rule['config_json'], true) ?? [];
    $formulaId = $config['formula_id'] ?? null;
    
    if ($formulaId === null) {
        return 0.00;
    }
    
    // Check if formula_builder component is available
    if (function_exists('formula_builder_evaluate')) {
        $variables = [
            'subtotal' => $orderData['subtotal'] ?? 0.00,
            'total' => $orderData['total_amount'] ?? 0.00,
            'order_count' => $customerActivity['order_count'] ?? 0,
            'lifetime_value' => $customerActivity['lifetime_value'] ?? 0.00,
            'order_frequency' => $customerActivity['order_frequency'] ?? 0.00,
            'account_age' => $customerActivity['account_age'] ?? 0
        ];
        
        $result = formula_builder_evaluate($formulaId, $variables);
        return (float)($result ?? 0.00);
    }
    
    return 0.00;
}

/**
 * Apply min/max caps
 * @param float $amount Surcharge amount
 * @param array $rule Rule data
 * @return float Capped amount
 */
function commerce_apply_surcharge_caps($amount, $rule) {
    $config = json_decode($rule['config_json'], true) ?? [];
    
    $minCap = isset($config['min_cap']) ? (float)$config['min_cap'] : null;
    $maxCap = isset($config['max_cap']) ? (float)$config['max_cap'] : null;
    
    if ($minCap !== null && $amount < $minCap) {
        $amount = $minCap;
    }
    
    if ($maxCap !== null && $amount > $maxCap) {
        $amount = $maxCap;
    }
    
    return $amount;
}

/**
 * Log rush surcharge calculation to history
 * @param int $orderId Order ID
 * @param int $ruleId Rule ID
 * @param string $calculationType Calculation type
 * @param float $baseAmount Base amount
 * @param float $calculatedAmount Calculated amount (before discounts)
 * @param float $finalAmount Final amount (after discounts and caps)
 * @param array $calculationDetails Calculation details
 * @param array|null $customerActivity Customer activity data
 * @return bool Success
 */
function commerce_log_rush_surcharge_calculation($orderId, $ruleId, $calculationType, $baseAmount, $calculatedAmount, $finalAmount, $calculationDetails, $customerActivity = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('rush_surcharge_history');
    $conditionsMet = json_encode($calculationDetails);
    $calculationDetailsJson = json_encode($calculationDetails);
    $customerActivityJson = $customerActivity ? json_encode($customerActivity) : null;
    $customerDiscount = $calculationDetails['discount_percentage'] ?? 0.00;
    $customerTier = $calculationDetails['customer_tier'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, rule_id, calculation_type, base_amount, calculated_amount, final_amount, conditions_met, calculation_details_json, customer_activity_json, customer_discount_applied, customer_tier) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisdddsdsss", $orderId, $ruleId, $calculationType, $baseAmount, $calculatedAmount, $finalAmount, $conditionsMet, $calculationDetailsJson, $customerActivityJson, $customerDiscount, $customerTier);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

