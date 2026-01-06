<?php
/**
 * Commerce Component - Bulk Order Functions
 * Bulk order table configuration and management
 */

require_once __DIR__ . '/database.php';

/**
 * Get bulk order table configuration
 * @param int $tableId Table ID
 * @return array|null Table configuration
 */
function commerce_get_bulk_order_table($tableId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('bulk_order_tables');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        $table = $result->fetch_assoc();
        $stmt->close();
        
        if ($table && !empty($table['config_json'])) {
            $table['config'] = json_decode($table['config_json'], true);
        }
        
        return $table;
    }
    
    return null;
}

/**
 * Get bulk order tables for product
 * @param int $productId Product ID
 * @return array Tables array
 */
function commerce_get_bulk_order_tables($productId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('bulk_order_tables');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE product_id = ? AND is_active = 1 ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tables = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['config_json'])) {
                $row['config'] = json_decode($row['config_json'], true);
            }
            $tables[] = $row;
        }
        $stmt->close();
        return $tables;
    }
    
    return [];
}

/**
 * Get bulk order table columns
 * @param int $tableId Table ID
 * @return array Columns array
 */
function commerce_get_bulk_order_table_columns($tableId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('bulk_order_table_columns');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE table_id = ? ORDER BY display_order ASC");
    if ($stmt) {
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['validation_rules'])) {
                $row['validation_rules'] = json_decode($row['validation_rules'], true);
            }
            $columns[] = $row;
        }
        $stmt->close();
        return $columns;
    }
    
    return [];
}

/**
 * Create bulk order table configuration
 * @param array $data Table data
 * @return array Result with table ID
 */
function commerce_create_bulk_order_table($data) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('bulk_order_tables');
    $configJson = json_encode($data['config'] ?? []);
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (product_id, table_name, description, is_active, config_json) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $productId = $data['product_id'];
        $tableNameValue = $data['table_name'];
        $description = $data['description'] ?? null;
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        
        $stmt->bind_param("issis", $productId, $tableNameValue, $description, $isActive, $configJson);
        if ($stmt->execute()) {
            $tableId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'table_id' => $tableId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Add bulk order table column
 * @param int $tableId Table ID
 * @param array $columnData Column data
 * @return array Result with column ID
 */
function commerce_add_bulk_order_table_column($tableId, $columnData) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('bulk_order_table_columns');
    $validationRulesJson = json_encode($columnData['validation_rules'] ?? []);
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (table_id, column_key, column_label, column_type, validation_rules, pricing_formula, display_order, is_required, default_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $columnKey = $columnData['column_key'];
        $columnLabel = $columnData['column_label'];
        $columnType = $columnData['column_type'];
        $pricingFormula = $columnData['pricing_formula'] ?? null;
        $displayOrder = $columnData['display_order'] ?? 0;
        $isRequired = isset($columnData['is_required']) ? (int)$columnData['is_required'] : 0;
        $defaultValue = $columnData['default_value'] ?? null;
        
        $stmt->bind_param("isssssiis", $tableId, $columnKey, $columnLabel, $columnType, $validationRulesJson, $pricingFormula, $displayOrder, $isRequired, $defaultValue);
        if ($stmt->execute()) {
            $columnId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'column_id' => $columnId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Calculate bulk order item price
 * @param int $tableId Table ID
 * @param array $rowData Row data
 * @return float Calculated price
 */
function commerce_calculate_bulk_order_item_price($tableId, $rowData) {
    $columns = commerce_get_bulk_order_table_columns($tableId);
    $total = 0.00;
    
    foreach ($columns as $column) {
        if (!empty($column['pricing_formula']) && isset($rowData[$column['column_key']])) {
            // Evaluate pricing formula
            $value = $rowData[$column['column_key']];
            $formula = $column['pricing_formula'];
            
            // Replace variables in formula
            $formula = str_replace('{' . $column['column_key'] . '}', $value, $formula);
            foreach ($rowData as $key => $val) {
                $formula = str_replace('{' . $key . '}', $val, $formula);
            }
            
            // Evaluate formula (basic math only, for security)
            try {
                $result = @eval("return {$formula};");
                if (is_numeric($result)) {
                    $total += (float)$result;
                }
            } catch (Exception $e) {
                error_log("Commerce: Error evaluating pricing formula: " . $e->getMessage());
            }
        }
    }
    
    return max(0.00, $total);
}

/**
 * Validate bulk order row data
 * @param int $tableId Table ID
 * @param array $rowData Row data
 * @return array Validation result
 */
function commerce_validate_bulk_order_row($tableId, $rowData) {
    $columns = commerce_get_bulk_order_table_columns($tableId);
    $errors = [];
    
    foreach ($columns as $column) {
        $key = $column['column_key'];
        $value = $rowData[$key] ?? null;
        
        // Check required
        if ($column['is_required'] && empty($value)) {
            $errors[] = "Column '{$column['column_label']}' is required";
            continue;
        }
        
        // Validate based on type
        if (!empty($value) && !empty($column['validation_rules'])) {
            $rules = $column['validation_rules'];
            
            // Type validation
            switch ($column['column_type']) {
                case 'number':
                case 'decimal':
                    if (!is_numeric($value)) {
                        $errors[] = "Column '{$column['column_label']}' must be a number";
                    }
                    break;
                case 'integer':
                    if (!is_numeric($value) || (int)$value != $value) {
                        $errors[] = "Column '{$column['column_label']}' must be an integer";
                    }
                    break;
            }
            
            // Min/Max validation
            if (isset($rules['min']) && is_numeric($value) && $value < $rules['min']) {
                $errors[] = "Column '{$column['column_label']}' must be at least {$rules['min']}";
            }
            if (isset($rules['max']) && is_numeric($value) && $value > $rules['max']) {
                $errors[] = "Column '{$column['column_label']}' must be at most {$rules['max']}";
            }
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Add bulk order items to order
 * @param int $orderId Order ID
 * @param int $tableId Table ID
 * @param array $rows Array of row data
 * @return array Result
 */
function commerce_add_bulk_order_items($orderId, $tableId, $rows) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('bulk_order_items');
    $errors = [];
    $added = 0;
    
    foreach ($rows as $rowData) {
        // Validate row
        $validation = commerce_validate_bulk_order_row($tableId, $rowData);
        if (!$validation['valid']) {
            $errors = array_merge($errors, $validation['errors']);
            continue;
        }
        
        // Calculate line total
        $lineTotal = commerce_calculate_bulk_order_item_price($tableId, $rowData);
        
        // Insert item
        $rowDataJson = json_encode($rowData);
        $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, table_id, row_data_json, line_total) VALUES (?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("iisd", $orderId, $tableId, $rowDataJson, $lineTotal);
            if ($stmt->execute()) {
                $added++;
            } else {
                $errors[] = "Failed to add row: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    return [
        'success' => empty($errors),
        'added' => $added,
        'errors' => $errors
    ];
}

