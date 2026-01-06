<?php
/**
 * Product Options Component - Conditional Logic Engine
 * Rule-based system for show/hide, filtering, dependencies, and validation
 */

require_once __DIR__ . '/database.php';

/**
 * Get conditions for an option
 * @param int $optionId Option ID
 * @param bool $activeOnly Only get active conditions
 * @return array Array of conditions
 */
function product_options_get_conditions($optionId, $activeOnly = true) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = product_options_get_table_name('conditions');
        $where = "WHERE option_id = ?";
        $params = [$optionId];
        $types = 'i';
        
        if ($activeOnly) {
            $where .= " AND is_active = 1";
        }
        
        $query = "SELECT * FROM {$tableName} {$where} ORDER BY display_order ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $conditions = [];
        while ($row = $result->fetch_assoc()) {
            $row['rule_config'] = json_decode($row['rule_config'], true);
            $conditions[] = $row;
        }
        
        $stmt->close();
        return $conditions;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting conditions: " . $e->getMessage());
        return [];
    }
}

/**
 * Evaluate a single condition
 * @param array $condition Condition configuration
 * @param array $formValues Current form values
 * @return bool True if condition is met
 */
function product_options_evaluate_condition($condition, $formValues) {
    $ruleConfig = $condition['rule_config'];
    $ruleType = $condition['rule_type'];
    
    switch ($ruleType) {
        case 'show_if':
        case 'hide_if':
        case 'require_if':
            return product_options_evaluate_show_hide_condition($ruleConfig, $formValues);
            
        case 'filter_if':
            return product_options_evaluate_filter_condition($ruleConfig, $formValues);
            
        case 'validate_if':
            return product_options_evaluate_validation_condition($ruleConfig, $formValues);
            
        default:
            return true;
    }
}

/**
 * Evaluate show/hide/require condition
 * @param array $ruleConfig Rule configuration
 * @param array $formValues Current form values
 * @return bool True if condition is met
 */
function product_options_evaluate_show_hide_condition($ruleConfig, $formValues) {
    $targetOption = $ruleConfig['target_option'] ?? null;
    $operator = $ruleConfig['operator'] ?? 'equals';
    $value = $ruleConfig['value'] ?? null;
    
    if (!$targetOption) {
        return false;
    }
    
    $targetValue = $formValues[$targetOption] ?? null;
    
    switch ($operator) {
        case 'equals':
            return $targetValue == $value;
            
        case 'not_equals':
            return $targetValue != $value;
            
        case 'contains':
            return is_string($targetValue) && strpos($targetValue, $value) !== false;
            
        case 'not_contains':
            return is_string($targetValue) && strpos($targetValue, $value) === false;
            
        case 'greater_than':
            return is_numeric($targetValue) && is_numeric($value) && $targetValue > $value;
            
        case 'less_than':
            return is_numeric($targetValue) && is_numeric($value) && $targetValue < $value;
            
        case 'greater_equal':
            return is_numeric($targetValue) && is_numeric($value) && $targetValue >= $value;
            
        case 'less_equal':
            return is_numeric($targetValue) && is_numeric($value) && $targetValue <= $value;
            
        case 'in_list':
            $list = is_array($value) ? $value : explode(',', $value);
            return in_array($targetValue, $list);
            
        case 'not_in_list':
            $list = is_array($value) ? $value : explode(',', $value);
            return !in_array($targetValue, $list);
            
        case 'is_empty':
            return empty($targetValue);
            
        case 'is_not_empty':
            return !empty($targetValue);
            
        default:
            return false;
    }
}

/**
 * Evaluate filter condition
 * @param array $ruleConfig Rule configuration
 * @param array $formValues Current form values
 * @return bool True if condition is met
 */
function product_options_evaluate_filter_condition($ruleConfig, $formValues) {
    // Filter conditions are similar to show/hide but used for filtering dropdown values
    return product_options_evaluate_show_hide_condition($ruleConfig, $formValues);
}

/**
 * Evaluate validation condition
 * @param array $ruleConfig Rule configuration
 * @param array $formValues Current form values
 * @return bool True if validation passes
 */
function product_options_evaluate_validation_condition($ruleConfig, $formValues) {
    $targetOption = $ruleConfig['target_option'] ?? null;
    $operator = $ruleConfig['operator'] ?? 'equals';
    $value = $ruleConfig['value'] ?? null;
    $errorMessage = $ruleConfig['error_message'] ?? 'Validation failed';
    
    if (!$targetOption) {
        return ['valid' => false, 'error' => 'Target option not specified'];
    }
    
    $targetValue = $formValues[$targetOption] ?? null;
    $isValid = product_options_evaluate_show_hide_condition($ruleConfig, $formValues);
    
    return [
        'valid' => $isValid,
        'error' => $isValid ? null : $errorMessage
    ];
}

/**
 * Evaluate multiple conditions with AND/OR logic
 * @param array $conditions Array of conditions
 * @param array $formValues Current form values
 * @param string $logic Logic operator: 'AND' or 'OR'
 * @return bool True if conditions are met
 */
function product_options_evaluate_conditions($conditions, $formValues, $logic = 'AND') {
    if (empty($conditions)) {
        return true;
    }
    
    $results = [];
    foreach ($conditions as $condition) {
        $results[] = product_options_evaluate_condition($condition, $formValues);
    }
    
    if ($logic === 'OR') {
        return in_array(true, $results);
    } else {
        return !in_array(false, $results);
    }
}

/**
 * Check if option should be shown
 * @param int $optionId Option ID
 * @param array $formValues Current form values
 * @return bool True if option should be shown
 */
function product_options_should_show($optionId, $formValues) {
    $conditions = product_options_get_conditions($optionId, true);
    
    // Filter only show_if and hide_if conditions
    $showConditions = array_filter($conditions, function($c) {
        return in_array($c['rule_type'], ['show_if', 'hide_if']);
    });
    
    if (empty($showConditions)) {
        return true; // No conditions, show by default
    }
    
    // Check for hide_if first (takes precedence)
    $hideConditions = array_filter($showConditions, function($c) {
        return $c['rule_type'] === 'hide_if';
    });
    
    if (!empty($hideConditions)) {
        $shouldHide = product_options_evaluate_conditions($hideConditions, $formValues, 'OR');
        if ($shouldHide) {
            return false;
        }
    }
    
    // Check show_if conditions
    $showIfConditions = array_filter($showConditions, function($c) {
        return $c['rule_type'] === 'show_if';
    });
    
    if (!empty($showIfConditions)) {
        return product_options_evaluate_conditions($showIfConditions, $formValues, 'OR');
    }
    
    return true;
}

/**
 * Filter dropdown values based on conditions
 * @param int $optionId Option ID
 * @param array $values Available values
 * @param array $formValues Current form values
 * @return array Filtered values
 */
function product_options_filter_values($optionId, $values, $formValues) {
    $conditions = product_options_get_conditions($optionId, true);
    
    // Filter only filter_if conditions
    $filterConditions = array_filter($conditions, function($c) {
        return $c['rule_type'] === 'filter_if';
    });
    
    if (empty($filterConditions)) {
        return $values; // No filter conditions, return all values
    }
    
    $filtered = [];
    foreach ($values as $value) {
        $shouldInclude = true;
        
        foreach ($filterConditions as $condition) {
            $ruleConfig = $condition['rule_config'];
            $targetOption = $ruleConfig['target_option'] ?? null;
            $operator = $ruleConfig['operator'] ?? 'equals';
            $targetValue = $ruleConfig['value'] ?? null;
            
            // Check if this value should be filtered based on form values
            $formValue = $formValues[$targetOption] ?? null;
            
            $matches = false;
            switch ($operator) {
                case 'equals':
                    $matches = $formValue == $targetValue;
                    break;
                case 'not_equals':
                    $matches = $formValue != $targetValue;
                    break;
                case 'in_list':
                    $list = is_array($targetValue) ? $targetValue : explode(',', $targetValue);
                    $matches = in_array($formValue, $list);
                    break;
                // Add more operators as needed
            }
            
            // If condition matches, check if value should be included
            if ($matches) {
                $valueFilter = $ruleConfig['value_filter'] ?? null;
                if ($valueFilter) {
                    // Check if this specific value should be included
                    $valueMatches = false;
                    switch ($valueFilter['operator']) {
                        case 'equals':
                            $valueMatches = ($value['value'] ?? $value) == $valueFilter['value'];
                            break;
                        case 'in_list':
                            $list = is_array($valueFilter['value']) ? $valueFilter['value'] : explode(',', $valueFilter['value']);
                            $valueMatches = in_array($value['value'] ?? $value, $list);
                            break;
                    }
                    
                    if (!$valueMatches) {
                        $shouldInclude = false;
                        break;
                    }
                }
            }
        }
        
        if ($shouldInclude) {
            $filtered[] = $value;
        }
    }
    
    return $filtered;
}

/**
 * Check required dependencies
 * @param int $optionId Option ID
 * @param array $formValues Current form values
 * @return array Result with success status and missing dependencies
 */
function product_options_check_dependencies($optionId, $formValues) {
    $conditions = product_options_get_conditions($optionId, true);
    
    // Filter only require_if conditions
    $requireConditions = array_filter($conditions, function($c) {
        return $c['rule_type'] === 'require_if';
    });
    
    if (empty($requireConditions)) {
        return ['success' => true, 'missing' => []];
    }
    
    $missing = [];
    foreach ($requireConditions as $condition) {
        $ruleConfig = $condition['rule_config'];
        $targetOption = $ruleConfig['target_option'] ?? null;
        
        if ($targetOption) {
            $targetValue = $formValues[$targetOption] ?? null;
            $requiredValue = $ruleConfig['value'] ?? null;
            
            // Check if condition is met
            $conditionMet = product_options_evaluate_show_hide_condition($ruleConfig, $formValues);
            
            if ($conditionMet) {
                // Condition is met, check if this option has a value
                $currentValue = $formValues[$optionId] ?? null;
                if (empty($currentValue)) {
                    $missing[] = [
                        'option_id' => $optionId,
                        'condition' => $condition,
                        'message' => $ruleConfig['error_message'] ?? 'This field is required'
                    ];
                }
            }
        }
    }
    
    return [
        'success' => empty($missing),
        'missing' => $missing
    ];
}

/**
 * Validate option value based on conditions
 * @param int $optionId Option ID
 * @param mixed $value Option value
 * @param array $formValues All form values
 * @return array Result with success status and error message
 */
function product_options_validate_option($optionId, $value, $formValues) {
    $conditions = product_options_get_conditions($optionId, true);
    
    // Filter only validate_if conditions
    $validationConditions = array_filter($conditions, function($c) {
        return $c['rule_type'] === 'validate_if';
    });
    
    if (empty($validationConditions)) {
        return ['success' => true];
    }
    
    // Add current value to form values for validation
    $formValues[$optionId] = $value;
    
    foreach ($validationConditions as $condition) {
        $result = product_options_evaluate_validation_condition($condition, $formValues);
        if (!$result['valid']) {
            return [
                'success' => false,
                'error' => $result['error']
            ];
        }
    }
    
    return ['success' => true];
}

/**
 * Save condition
 * @param array $conditionData Condition data
 * @return array Result with success status and condition ID
 */
function product_options_save_condition($conditionData) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = product_options_get_table_name('conditions');
        $ruleConfig = json_encode($conditionData['rule_config']);
        
        if (isset($conditionData['id']) && !empty($conditionData['id'])) {
            // Update existing condition
            $stmt = $conn->prepare("UPDATE {$tableName} SET 
                                    option_id = ?, rule_type = ?, rule_config = ?, 
                                    is_active = ?, display_order = ?
                                    WHERE id = ?");
            
            $stmt->bind_param("isssii",
                $conditionData['option_id'],
                $conditionData['rule_type'],
                $ruleConfig,
                $conditionData['is_active'] ?? 1,
                $conditionData['display_order'] ?? 0,
                $conditionData['id']
            );
            
            $stmt->execute();
            $stmt->close();
            
            return ['success' => true, 'id' => $conditionData['id']];
        } else {
            // Create new condition
            $stmt = $conn->prepare("INSERT INTO {$tableName} 
                                    (option_id, rule_type, rule_config, is_active, display_order)
                                    VALUES (?, ?, ?, ?, ?)");
            
            $stmt->bind_param("issii",
                $conditionData['option_id'],
                $conditionData['rule_type'],
                $ruleConfig,
                $conditionData['is_active'] ?? 1,
                $conditionData['display_order'] ?? 0
            );
            
            $stmt->execute();
            $conditionId = $conn->insert_id;
            $stmt->close();
            
            return ['success' => true, 'id' => $conditionId];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error saving condition: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete condition
 * @param int $conditionId Condition ID
 * @return array Result with success status
 */
function product_options_delete_condition($conditionId) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = product_options_get_table_name('conditions');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $conditionId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error deleting condition: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

