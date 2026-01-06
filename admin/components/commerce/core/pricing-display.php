<?php
/**
 * Commerce Component - Pricing Display Rules System
 * Functions for evaluating pricing display rules and formatting prices
 */

require_once __DIR__ . '/database.php';

/**
 * Get applicable pricing display rule
 * @param string $ruleType Rule type (global, quote_stage, charge_type, product, line_item)
 * @param int|null $targetId Target ID (product ID, line item ID, etc.)
 * @param string $quoteStage Quote stage (initial_request, quote_sent, etc.)
 * @return array|null Display rule or null
 */
function commerce_get_pricing_display_rule($ruleType, $targetId = null, $quoteStage = 'initial_request') {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('pricing_display_rules');
    
    // Build query based on rule type
    $sql = "SELECT * FROM {$tableName} WHERE rule_type = ? AND is_active = 1";
    $params = ["s", &$ruleType];
    $types = "s";
    
    if ($targetId !== null) {
        $sql .= " AND target_id = ?";
        $params[] = &$targetId;
        $types .= "i";
    }
    
    if ($ruleType === 'quote_stage') {
        $sql .= " AND quote_stage = ?";
        $params[] = &$quoteStage;
        $types .= "s";
    }
    
    $sql .= " ORDER BY priority ASC LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Bind parameters dynamically
        $bindParams = [$types];
        for ($i = 1; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        
        $stmt->execute();
        $result = $stmt->get_result();
        $rule = $result->fetch_assoc();
        $stmt->close();
        
        return $rule;
    }
    
    return null;
}

/**
 * Check if price should be displayed
 * @param string $ruleType Rule type
 * @param int|null $targetId Target ID
 * @param string $quoteStage Quote stage
 * @param array|null $productData Product data
 * @return array Display state and configuration
 */
function commerce_should_display_price($ruleType, $targetId, $quoteStage, $productData = null) {
    $rule = commerce_get_pricing_display_rule($ruleType, $targetId, $quoteStage);
    
    if (!$rule) {
        // Default: show price
        return [
            'should_display' => true,
            'display_state' => 'show',
            'show_breakdown' => true,
            'show_total_only' => false
        ];
    }
    
    $displayState = $rule['display_state'] ?? 'show';
    $shouldDisplay = ($displayState !== 'hide');
    
    return [
        'should_display' => $shouldDisplay,
        'display_state' => $displayState,
        'show_breakdown' => (bool)($rule['show_breakdown'] ?? true),
        'show_total_only' => (bool)($rule['show_total_only'] ?? false),
        'show_both' => (bool)($rule['show_both'] ?? false),
        'disclaimer_template' => $rule['disclaimer_template'] ?? null
    ];
}

/**
 * Get pricing display configuration
 * @param string $ruleType Rule type
 * @param int|null $targetId Target ID
 * @param string $quoteStage Quote stage
 * @return array Display configuration
 */
function commerce_get_pricing_display_config($ruleType, $targetId, $quoteStage) {
    return commerce_should_display_price($ruleType, $targetId, $quoteStage);
}

/**
 * Format price for display
 * @param float $price Price amount
 * @param array $displayConfig Display configuration
 * @param bool $isEstimated Whether price is estimated
 * @return string Formatted price string
 */
function commerce_format_price_display($price, $displayConfig, $isEstimated = false) {
    $formattedPrice = number_format($price, 2);
    
    if ($displayConfig['display_state'] === 'hide') {
        return '';
    }
    
    if ($displayConfig['display_state'] === 'estimated' || $isEstimated) {
        $disclaimer = $displayConfig['disclaimer_template'] ?? 'Price is estimated and may change';
        return '$' . $formattedPrice . ' <small class="text-muted">(' . htmlspecialchars($disclaimer) . ')</small>';
    }
    
    if ($displayConfig['display_state'] === 'fixed') {
        return '$' . $formattedPrice . ' <small class="text-muted">(Fixed Price)</small>';
    }
    
    return '$' . $formattedPrice;
}

/**
 * Create pricing display rule
 * @param array $ruleData Rule data
 * @return array Result
 */
function commerce_create_pricing_display_rule($ruleData) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('pricing_display_rules');
    
    $ruleName = $ruleData['rule_name'] ?? '';
    $description = $ruleData['description'] ?? null;
    $ruleType = $ruleData['rule_type'] ?? 'global';
    $targetId = $ruleData['target_id'] ?? null;
    $quoteStage = $ruleData['quote_stage'] ?? null;
    $chargeType = $ruleData['charge_type'] ?? null;
    $displayState = $ruleData['display_state'] ?? 'show';
    $showBreakdown = isset($ruleData['show_breakdown']) ? (int)$ruleData['show_breakdown'] : 1;
    $showTotalOnly = isset($ruleData['show_total_only']) ? (int)$ruleData['show_total_only'] : 0;
    $showBoth = isset($ruleData['show_both']) ? (int)$ruleData['show_both'] : 0;
    $disclaimerTemplate = $ruleData['disclaimer_template'] ?? null;
    $priority = (int)($ruleData['priority'] ?? 0);
    $isActive = isset($ruleData['is_active']) ? (int)$ruleData['is_active'] : 1;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (rule_name, description, rule_type, target_id, quote_stage, charge_type, display_state, show_breakdown, show_total_only, show_both, disclaimer_template, priority, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssissssiiisi", $ruleName, $description, $ruleType, $targetId, $quoteStage, $chargeType, $displayState, $showBreakdown, $showTotalOnly, $showBoth, $disclaimerTemplate, $priority, $isActive);
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
 * Update pricing display rule
 * @param int $ruleId Rule ID
 * @param array $ruleData Rule data
 * @return array Result
 */
function commerce_update_pricing_display_rule($ruleId, $ruleData) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('pricing_display_rules');
    
    $updates = [];
    $params = [];
    $types = '';
    
    $fields = ['rule_name', 'description', 'rule_type', 'target_id', 'quote_stage', 'charge_type', 'display_state', 'show_breakdown', 'show_total_only', 'show_both', 'disclaimer_template', 'priority', 'is_active'];
    
    foreach ($fields as $field) {
        if (isset($ruleData[$field])) {
            $updates[] = "{$field} = ?";
            $params[] = &$ruleData[$field];
            $types .= is_int($ruleData[$field]) ? 'i' : 's';
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
 * Delete pricing display rule
 * @param int $ruleId Rule ID
 * @return bool Success
 */
function commerce_delete_pricing_display_rule($ruleId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('pricing_display_rules');
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
 * Get pricing display rules
 * @param string|null $ruleType Rule type filter
 * @param bool $activeOnly Only get active rules
 * @return array Rules
 */
function commerce_get_pricing_display_rules($ruleType = null, $activeOnly = true) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('pricing_display_rules');
    
    $sql = "SELECT * FROM {$tableName}";
    $conditions = [];
    $params = [];
    $types = '';
    
    if ($ruleType !== null) {
        $conditions[] = "rule_type = ?";
        $params[] = &$ruleType;
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

