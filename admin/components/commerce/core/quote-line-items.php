<?php
/**
 * Commerce Component - Quote Line Items System
 * Functions for adding, editing, deleting line items with display control
 */

require_once __DIR__ . '/database.php';

/**
 * Add line item to quote
 * @param int $quoteId Quote ID (order ID)
 * @param array $lineItemData Line item data
 * @return array Result with line_item_id
 */
function commerce_add_quote_line_item($quoteId, $lineItemData) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('quote_line_items');
    
    $orderId = $lineItemData['order_id'] ?? $quoteId;
    $productId = $lineItemData['product_id'] ?? null;
    $lineItemType = $lineItemData['line_item_type'] ?? 'charge';
    $name = $lineItemData['name'] ?? '';
    $description = $lineItemData['description'] ?? null;
    $quantity = (float)($lineItemData['quantity'] ?? 1.00);
    $unitPrice = (float)($lineItemData['unit_price'] ?? 0.00);
    $totalPrice = $quantity * $unitPrice;
    $calculationType = $lineItemData['calculation_type'] ?? 'fixed';
    $calculationConfigJson = json_encode($lineItemData['calculation_config'] ?? []);
    $displayOnQuote = isset($lineItemData['display_on_quote']) ? (int)$lineItemData['display_on_quote'] : 1;
    $displayText = isset($lineItemData['display_text']) ? (int)$lineItemData['display_text'] : 1;
    $displayPrice = isset($lineItemData['display_price']) ? (int)$lineItemData['display_price'] : 1;
    $displayBreakdown = isset($lineItemData['display_breakdown']) ? (int)$lineItemData['display_breakdown'] : 0;
    $displayTotalOnly = isset($lineItemData['display_total_only']) ? (int)$lineItemData['display_total_only'] : 0;
    $showBoth = isset($lineItemData['show_both']) ? (int)$lineItemData['show_both'] : 0;
    $isHiddenCost = isset($lineItemData['is_hidden_cost']) ? (int)$lineItemData['is_hidden_cost'] : 0;
    $displayOrder = (int)($lineItemData['display_order'] ?? 0);
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (quote_id, order_id, product_id, line_item_type, name, description, quantity, unit_price, total_price, calculation_type, calculation_config_json, display_on_quote, display_text, display_price, display_breakdown, display_total_only, show_both, is_hidden_cost, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiiissdddsiiiiiiiii", $quoteId, $orderId, $productId, $lineItemType, $name, $description, $quantity, $unitPrice, $totalPrice, $calculationType, $calculationConfigJson, $displayOnQuote, $displayText, $displayPrice, $displayBreakdown, $displayTotalOnly, $showBoth, $isHiddenCost, $displayOrder);
        if ($stmt->execute()) {
            $lineItemId = $conn->insert_id;
            $stmt->close();
            
            // Record in history
            commerce_record_line_item_history($lineItemId, 'created', null, $lineItemData, $_SESSION['user_id'] ?? null);
            
            return ['success' => true, 'line_item_id' => $lineItemId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update line item
 * @param int $lineItemId Line item ID
 * @param array $lineItemData Line item data
 * @return array Result
 */
function commerce_update_quote_line_item($lineItemId, $lineItemData) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('quote_line_items');
    
    // Get old values
    $oldStmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($oldStmt) {
        $oldStmt->bind_param("i", $lineItemId);
        $oldStmt->execute();
        $result = $oldStmt->get_result();
        $oldValues = $result->fetch_assoc();
        $oldStmt->close();
    }
    
    // Build update query
    $updates = [];
    $params = [];
    $types = '';
    
    $fields = [
        'name' => 's', 'description' => 's', 'quantity' => 'd', 'unit_price' => 'd',
        'calculation_type' => 's', 'calculation_config_json' => 's',
        'display_on_quote' => 'i', 'display_text' => 'i', 'display_price' => 'i',
        'display_breakdown' => 'i', 'display_total_only' => 'i', 'show_both' => 'i',
        'is_hidden_cost' => 'i', 'display_order' => 'i'
    ];
    
    foreach ($fields as $field => $type) {
        if (isset($lineItemData[$field])) {
            $updates[] = "{$field} = ?";
            $params[] = &$lineItemData[$field];
            $types .= $type;
        }
    }
    
    // Recalculate total_price if quantity or unit_price changed
    if (isset($lineItemData['quantity']) || isset($lineItemData['unit_price'])) {
        $quantity = $lineItemData['quantity'] ?? $oldValues['quantity'];
        $unitPrice = $lineItemData['unit_price'] ?? $oldValues['unit_price'];
        $totalPrice = $quantity * $unitPrice;
        $updates[] = "total_price = ?";
        $params[] = &$totalPrice;
        $types .= 'd';
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $sql = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = &$lineItemId;
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
            
            // Record in history
            $newValues = array_merge($oldValues, $lineItemData);
            commerce_record_line_item_history($lineItemId, 'updated', $oldValues, $newValues, $_SESSION['user_id'] ?? null);
            
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
 * Delete line item
 * @param int $lineItemId Line item ID
 * @param int $deletedBy User ID
 * @param string|null $reason Deletion reason
 * @return bool Success
 */
function commerce_delete_quote_line_item($lineItemId, $deletedBy, $reason = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('quote_line_items');
    
    // Get old values for history
    $oldStmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($oldStmt) {
        $oldStmt->bind_param("i", $lineItemId);
        $oldStmt->execute();
        $result = $oldStmt->get_result();
        $oldValues = $result->fetch_assoc();
        $oldStmt->close();
    }
    
    // Soft delete (set display_on_quote = 0 and is_hidden_cost = 1)
    $stmt = $conn->prepare("UPDATE {$tableName} SET display_on_quote = 0, is_hidden_cost = 1 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $lineItemId);
        if ($stmt->execute()) {
            $stmt->close();
            
            // Record in history
            commerce_record_line_item_history($lineItemId, 'deleted', $oldValues, null, $deletedBy, $reason);
            
            return true;
        }
        $stmt->close();
    }
    
    return false;
}

/**
 * Record line item history
 * @param int $lineItemId Line item ID
 * @param string $changeType Change type
 * @param array|null $oldValues Old values
 * @param array|null $newValues New values
 * @param int|null $changedBy User ID
 * @param string|null $changeReason Change reason
 * @return bool Success
 */
function commerce_record_line_item_history($lineItemId, $changeType, $oldValues, $newValues, $changedBy = null, $changeReason = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('quote_line_item_history');
    $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
    $newValuesJson = $newValues ? json_encode($newValues) : null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (line_item_id, change_type, old_values_json, new_values_json, changed_by, change_reason) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssis", $lineItemId, $changeType, $oldValuesJson, $newValuesJson, $changedBy, $changeReason);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Get quote line items
 * @param int $quoteId Quote ID
 * @param string|null $lineItemType Line item type filter
 * @param bool $includeHidden Include hidden costs
 * @return array Line items
 */
function commerce_get_quote_line_items($quoteId, $lineItemType = null, $includeHidden = false) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('quote_line_items');
    
    $sql = "SELECT * FROM {$tableName} WHERE quote_id = ?";
    $params = ["i", &$quoteId];
    $types = "i";
    
    if ($lineItemType !== null) {
        $sql .= " AND line_item_type = ?";
        $params[] = &$lineItemType;
        $types .= "s";
    }
    
    if (!$includeHidden) {
        $sql .= " AND is_hidden_cost = 0";
    }
    
    $sql .= " ORDER BY display_order ASC, id ASC";
    
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
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }
    
    return [];
}

/**
 * Get product line items
 * @param int $productId Product ID
 * @param int $quoteId Quote ID
 * @return array Line items
 */
function commerce_get_product_line_items($productId, $quoteId) {
    return commerce_get_quote_line_items($quoteId, 'product');
}

/**
 * Get job line items
 * @param int $quoteId Quote ID
 * @param bool $includeHidden Include hidden costs
 * @return array Line items
 */
function commerce_get_job_line_items($quoteId, $includeHidden = false) {
    return commerce_get_quote_line_items($quoteId, 'job', $includeHidden);
}

/**
 * Calculate line item price
 * @param int $lineItemId Line item ID
 * @param float|null $baseAmount Base amount for percentage calculations
 * @return float Calculated price
 */
function commerce_calculate_line_item_price($lineItemId, $baseAmount = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return 0.00;
    }
    
    $tableName = commerce_get_table_name('quote_line_items');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $lineItemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        
        if (!$item) {
            return 0.00;
        }
        
        $calculationType = $item['calculation_type'];
        $config = json_decode($item['calculation_config_json'], true) ?? [];
        
        switch ($calculationType) {
            case 'fixed':
                return (float)$item['unit_price'];
            case 'percentage':
                if ($baseAmount !== null) {
                    $percentage = (float)($config['percentage'] ?? 0);
                    return $baseAmount * ($percentage / 100);
                }
                return (float)$item['unit_price'];
            case 'formula':
                // Formula-based calculation (if formula_builder available)
                if (function_exists('formula_builder_evaluate') && isset($config['formula_id'])) {
                    $variables = ['base_amount' => $baseAmount ?? 0];
                    return (float)(formula_builder_evaluate($config['formula_id'], $variables) ?? 0);
                }
                return (float)$item['unit_price'];
            default:
                return (float)$item['unit_price'];
        }
    }
    
    return 0.00;
}

/**
 * Get quote line items formatted for display
 * @param int $quoteId Quote ID
 * @param string $quoteStage Quote stage
 * @return array Formatted line items
 */
function commerce_get_quote_line_items_for_display($quoteId, $quoteStage = 'initial_request') {
    $items = commerce_get_quote_line_items($quoteId, null, false);
    $formattedItems = [];
    
    require_once __DIR__ . '/pricing-display.php';
    
    foreach ($items as $item) {
        // Check pricing display rules
        $displayConfig = commerce_get_pricing_display_config('line_item', $item['id'], $quoteStage);
        
        if (!$displayConfig['should_display'] && !$item['display_on_quote']) {
            continue; // Skip hidden items
        }
        
        $formattedItem = [
            'id' => $item['id'],
            'name' => $item['name'],
            'description' => $item['description'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price'],
            'display_text' => (bool)$item['display_text'],
            'display_price' => (bool)$item['display_price'] && $displayConfig['should_display'],
            'display_breakdown' => (bool)$item['display_breakdown'],
            'display_total_only' => (bool)$item['display_total_only'],
            'show_both' => (bool)$item['show_both']
        ];
        
        $formattedItems[] = $formattedItem;
    }
    
    return $formattedItems;
}

/**
 * Calculate quote total
 * @param int $quoteId Quote ID
 * @param bool $includeHidden Include hidden costs
 * @return array Total breakdown
 */
function commerce_get_quote_total($quoteId, $includeHidden = false) {
    $items = commerce_get_quote_line_items($quoteId, null, $includeHidden);
    
    $subtotal = 0.00;
    foreach ($items as $item) {
        $subtotal += (float)$item['total_price'];
    }
    
    // Get order for tax calculation
    $order = null;
    if (function_exists('commerce_get_order')) {
        $order = commerce_get_order($quoteId);
    }
    
    $tax = $order ? (float)($order['tax_amount'] ?? 0.00) : 0.00;
    $total = $subtotal + $tax;
    
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total,
        'line_items_count' => count($items)
    ];
}

/**
 * Get line item history
 * @param int $lineItemId Line item ID
 * @return array History records
 */
function commerce_get_line_item_history($lineItemId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('quote_line_item_history');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE line_item_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param("i", $lineItemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['old_values_json']) {
                $row['old_values'] = json_decode($row['old_values_json'], true);
            }
            if ($row['new_values_json']) {
                $row['new_values'] = json_decode($row['new_values_json'], true);
            }
            $history[] = $row;
        }
        $stmt->close();
        return $history;
    }
    
    return [];
}

