<?php
/**
 * Product Options Component - Pricing System
 * Formula-based pricing with per-option enable/disable
 */

require_once __DIR__ . '/database.php';

/**
 * Get pricing formula for an option
 * @param int $optionId Option ID
 * @return array|null Pricing data or null
 */
function product_options_get_pricing($optionId) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = product_options_get_table_name('pricing');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE option_id = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param("i", $optionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $pricing = $result->fetch_assoc();
        $stmt->close();
        
        if ($pricing) {
            $pricing['variables'] = json_decode($pricing['variables'], true);
            $pricing['conditions'] = json_decode($pricing['conditions'], true);
        }
        
        return $pricing ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting pricing: " . $e->getMessage());
        return null;
    }
}

/**
 * Evaluate pricing formula
 * Supports basic math operations and functions
 * @param string $formula Formula string (e.g., "base_price * quantity + 10")
 * @param array $variables Variable values
 * @return float|false Calculated price or false on error
 */
function product_options_evaluate_formula($formula, $variables = []) {
    if (empty($formula)) {
        return 0;
    }
    
    // Replace variables in formula
    $evaluatedFormula = $formula;
    foreach ($variables as $key => $value) {
        // Ensure numeric value
        $numericValue = is_numeric($value) ? $value : 0;
        $evaluatedFormula = str_replace('{' . $key . '}', $numericValue, $evaluatedFormula);
        // Also support $variable syntax
        $evaluatedFormula = str_replace('$' . $key, $numericValue, $evaluatedFormula);
    }
    
    // Remove any remaining variable placeholders
    $evaluatedFormula = preg_replace('/\{[^}]+\}/', '0', $evaluatedFormula);
    $evaluatedFormula = preg_replace('/\$[a-zA-Z_][a-zA-Z0-9_]*/', '0', $evaluatedFormula);
    
    // Sanitize: only allow numbers, operators, parentheses, and basic functions
    $allowedChars = '/^[0-9+\-*\/\(\)\s\.]+$/';
    if (!preg_match($allowedChars, $evaluatedFormula)) {
        // Try to evaluate with safe eval (using create_function alternative)
        // For security, we'll use a simple parser instead
        return product_options_safe_eval_formula($evaluatedFormula);
    }
    
    // Use eval for simple math (in production, consider using a proper expression parser)
    try {
        // Create a safe evaluation context
        $result = @eval("return {$evaluatedFormula};");
        return is_numeric($result) ? (float)$result : false;
    } catch (Exception $e) {
        error_log("Product Options: Error evaluating formula: " . $e->getMessage());
        return false;
    }
}

/**
 * Safe formula evaluation using a simple parser
 * @param string $formula Formula to evaluate
 * @return float|false Calculated result or false on error
 */
function product_options_safe_eval_formula($formula) {
    // Remove whitespace
    $formula = preg_replace('/\s+/', '', $formula);
    
    // Handle parentheses first
    while (preg_match('/\(([^()]+)\)/', $formula, $matches)) {
        $result = product_options_safe_eval_formula($matches[1]);
        $formula = str_replace($matches[0], $result, $formula);
    }
    
    // Handle multiplication and division
    while (preg_match('/(\d+\.?\d*)\s*([*\/])\s*(\d+\.?\d*)/', $formula, $matches)) {
        $a = (float)$matches[1];
        $b = (float)$matches[3];
        $op = $matches[2];
        
        $result = $op === '*' ? $a * $b : ($b != 0 ? $a / $b : 0);
        $formula = str_replace($matches[0], $result, $formula);
    }
    
    // Handle addition and subtraction
    while (preg_match('/(\d+\.?\d*)\s*([+\-])\s*(\d+\.?\d*)/', $formula, $matches)) {
        $a = (float)$matches[1];
        $b = (float)$matches[3];
        $op = $matches[2];
        
        $result = $op === '+' ? $a + $b : $a - $b;
        $formula = str_replace($matches[0], $result, $formula);
    }
    
    return is_numeric($formula) ? (float)$formula : false;
}

/**
 * Calculate price for an option
 * @param int $optionId Option ID
 * @param mixed $optionValue Selected option value
 * @param array $allFormValues All form values (for variable substitution)
 * @param float $basePrice Base price (optional)
 * @return float|false Calculated price or false on error
 */
function product_options_calculate_price($optionId, $optionValue, $allFormValues = [], $basePrice = 0) {
    // Check if pricing is enabled for this option
    $option = product_options_get_option($optionId);
    if (!$option || !$option['pricing_enabled']) {
        return 0; // Pricing disabled, return 0
    }
    
    $pricing = product_options_get_pricing($optionId);
    if (!$pricing) {
        return 0; // No pricing formula, return 0
    }
    
    // Check conditions
    if (!empty($pricing['conditions'])) {
        $conditionMet = product_options_evaluate_pricing_conditions($pricing['conditions'], $allFormValues);
        if (!$conditionMet) {
            return 0; // Conditions not met, return 0
        }
    }
    
    // Prepare variables
    $variables = $pricing['variables'] ?? [];
    $variables['option_value'] = is_numeric($optionValue) ? (float)$optionValue : 0;
    $variables['base_price'] = $basePrice;
    $variables['quantity'] = $allFormValues['quantity'] ?? 1;
    
    // Add all form values as variables
    foreach ($allFormValues as $key => $value) {
        if (is_numeric($value)) {
            $variables[$key] = (float)$value;
        }
    }
    
    // Evaluate formula
    return product_options_evaluate_formula($pricing['formula'], $variables);
}

/**
 * Evaluate pricing conditions
 * @param array $conditions Conditions array
 * @param array $formValues Form values
 * @return bool True if conditions are met
 */
function product_options_evaluate_pricing_conditions($conditions, $formValues) {
    if (empty($conditions)) {
        return true;
    }
    
    $logic = $conditions['logic'] ?? 'AND';
    
    foreach ($conditions['rules'] ?? [] as $rule) {
        $targetOption = $rule['target_option'] ?? null;
        $operator = $rule['operator'] ?? 'equals';
        $value = $rule['value'] ?? null;
        
        if (!$targetOption) {
            continue;
        }
        
        $targetValue = $formValues[$targetOption] ?? null;
        $matches = false;
        
        switch ($operator) {
            case 'equals':
                $matches = $targetValue == $value;
                break;
            case 'not_equals':
                $matches = $targetValue != $value;
                break;
            case 'greater_than':
                $matches = is_numeric($targetValue) && is_numeric($value) && $targetValue > $value;
                break;
            case 'less_than':
                $matches = is_numeric($targetValue) && is_numeric($value) && $targetValue < $value;
                break;
            case 'in_list':
                $list = is_array($value) ? $value : explode(',', $value);
                $matches = in_array($targetValue, $list);
                break;
        }
        
        if ($logic === 'OR' && $matches) {
            return true;
        }
        if ($logic === 'AND' && !$matches) {
            return false;
        }
    }
    
    return $logic === 'AND';
}

/**
 * Get all price modifiers for a set of options
 * @param array $optionValues Array of [option_id => value] pairs
 * @param float $basePrice Base price
 * @return array Array of price modifiers
 */
function product_options_get_price_modifiers($optionValues, $basePrice = 0) {
    $modifiers = [];
    $totalModifier = 0;
    
    foreach ($optionValues as $optionId => $value) {
        $price = product_options_calculate_price($optionId, $value, $optionValues, $basePrice);
        
        if ($price != 0) {
            $option = product_options_get_option($optionId);
            $modifiers[] = [
                'option_id' => $optionId,
                'option_name' => $option['name'] ?? '',
                'option_label' => $option['label'] ?? '',
                'value' => $value,
                'price' => $price
            ];
            $totalModifier += $price;
        }
    }
    
    return [
        'modifiers' => $modifiers,
        'total_modifier' => $totalModifier,
        'final_price' => $basePrice + $totalModifier
    ];
}

/**
 * Save pricing formula
 * @param array $pricingData Pricing data
 * @return array Result with success status and pricing ID
 */
function product_options_save_pricing($pricingData) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = product_options_get_table_name('pricing');
        
        // Prepare JSON fields
        $variables = isset($pricingData['variables']) ? json_encode($pricingData['variables']) : null;
        $conditions = isset($pricingData['conditions']) ? json_encode($pricingData['conditions']) : null;
        
        if (isset($pricingData['id']) && !empty($pricingData['id'])) {
            // Update existing pricing
            $stmt = $conn->prepare("UPDATE {$tableName} SET 
                                    option_id = ?, formula = ?, formula_type = ?, 
                                    variables = ?, conditions = ?, is_active = ?
                                    WHERE id = ?");
            
            $stmt->bind_param("isssssi",
                $pricingData['option_id'],
                $pricingData['formula'],
                $pricingData['formula_type'] ?? 'expression',
                $variables,
                $conditions,
                $pricingData['is_active'] ?? 1,
                $pricingData['id']
            );
            
            $stmt->execute();
            $stmt->close();
            
            return ['success' => true, 'id' => $pricingData['id']];
        } else {
            // Create new pricing
            $stmt = $conn->prepare("INSERT INTO {$tableName} 
                                    (option_id, formula, formula_type, variables, conditions, is_active)
                                    VALUES (?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("issssi",
                $pricingData['option_id'],
                $pricingData['formula'],
                $pricingData['formula_type'] ?? 'expression',
                $variables,
                $conditions,
                $pricingData['is_active'] ?? 1
            );
            
            $stmt->execute();
            $pricingId = $conn->insert_id;
            $stmt->close();
            
            return ['success' => true, 'id' => $pricingId];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error saving pricing: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete pricing
 * @param int $pricingId Pricing ID
 * @return array Result with success status
 */
function product_options_delete_pricing($pricingId) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = product_options_get_table_name('pricing');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $pricingId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error deleting pricing: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

