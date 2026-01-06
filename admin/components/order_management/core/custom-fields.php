<?php
/**
 * Order Management Component - Custom Fields Functions
 * Custom order field management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get custom field definition
 * @param int $fieldId Field ID
 * @return array|null Field data
 */
function order_management_get_custom_field($fieldId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('custom_fields');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $fieldId);
        $stmt->execute();
        $result = $stmt->get_result();
        $field = $result->fetch_assoc();
        $stmt->close();
        
        if ($field) {
            $field['options'] = json_decode($field['options'], true);
            $field['validation_rules'] = json_decode($field['validation_rules'], true);
        }
        
        return $field;
    }
    
    return null;
}

/**
 * Get all custom fields
 * @param bool $activeOnly Only return active fields
 * @return array Array of fields
 */
function order_management_get_custom_fields($activeOnly = false) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('custom_fields');
    $query = "SELECT * FROM {$tableName}";
    if ($activeOnly) {
        $query .= " WHERE is_active = 1";
    }
    $query .= " ORDER BY display_order ASC, name ASC";
    
    $result = $conn->query($query);
    $fields = [];
    while ($row = $result->fetch_assoc()) {
        $row['options'] = json_decode($row['options'], true);
        $row['validation_rules'] = json_decode($row['validation_rules'], true);
        $fields[] = $row;
    }
    
    return $fields;
}

/**
 * Create custom field
 * @param array $data Field data
 * @return array Result
 */
function order_management_create_custom_field($data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('custom_fields');
    
    $name = $data['name'] ?? '';
    $fieldType = $data['field_type'] ?? 'text';
    $label = $data['label'] ?? $name;
    $isRequired = $data['is_required'] ?? 0;
    $isActive = $data['is_active'] ?? 1;
    $displayOrder = $data['display_order'] ?? 0;
    $options = isset($data['options']) ? json_encode($data['options']) : '[]';
    $validationRules = isset($data['validation_rules']) ? json_encode($data['validation_rules']) : '{}';
    $defaultValue = $data['default_value'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (name, field_type, label, is_required, is_active, display_order, options, validation_rules, default_value, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("sssiiisss", $name, $fieldType, $label, $isRequired, $isActive, $displayOrder, $options, $validationRules, $defaultValue);
        if ($stmt->execute()) {
            $fieldId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'field_id' => $fieldId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update custom field
 * @param int $fieldId Field ID
 * @param array $data Field data
 * @return array Result
 */
function order_management_update_custom_field($fieldId, $data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('custom_fields');
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['name'])) {
        $updates[] = "name = ?";
        $params[] = $data['name'];
        $types .= 's';
    }
    
    if (isset($data['field_type'])) {
        $updates[] = "field_type = ?";
        $params[] = $data['field_type'];
        $types .= 's';
    }
    
    if (isset($data['label'])) {
        $updates[] = "label = ?";
        $params[] = $data['label'];
        $types .= 's';
    }
    
    if (isset($data['is_required'])) {
        $updates[] = "is_required = ?";
        $params[] = $data['is_required'];
        $types .= 'i';
    }
    
    if (isset($data['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = $data['is_active'];
        $types .= 'i';
    }
    
    if (isset($data['display_order'])) {
        $updates[] = "display_order = ?";
        $params[] = $data['display_order'];
        $types .= 'i';
    }
    
    if (isset($data['options'])) {
        $updates[] = "options = ?";
        $params[] = json_encode($data['options']);
        $types .= 's';
    }
    
    if (isset($data['validation_rules'])) {
        $updates[] = "validation_rules = ?";
        $params[] = json_encode($data['validation_rules']);
        $types .= 's';
    }
    
    if (isset($data['default_value'])) {
        $updates[] = "default_value = ?";
        $params[] = $data['default_value'];
        $types .= 's';
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $fieldId;
    $types .= 'i';
    
    $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
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
 * Get custom field values for order
 * @param int $orderId Order ID
 * @return array Array of field values
 */
function order_management_get_order_custom_fields($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('order_custom_field_values');
    $fieldsTable = order_management_get_table_name('custom_fields');
    
    $query = "SELECT cfv.*, cf.name, cf.label, cf.field_type 
             FROM {$tableName} cfv
             INNER JOIN {$fieldsTable} cf ON cfv.field_id = cf.id
             WHERE cfv.order_id = ?
             ORDER BY cf.display_order ASC";
    
    $values = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $values[] = $row;
        }
        $stmt->close();
    }
    
    return $values;
}

/**
 * Set custom field value for order
 * @param int $orderId Order ID
 * @param int $fieldId Field ID
 * @param mixed $value Field value
 * @return array Result
 */
function order_management_set_order_custom_field($orderId, $fieldId, $value) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('order_custom_field_values');
    
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE order_id = ? AND field_id = ? LIMIT 1");
    $stmt->bind_param("ii", $orderId, $fieldId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    $valueStr = is_array($value) ? json_encode($value) : (string)$value;
    
    if ($existing) {
        // Update
        $stmt = $conn->prepare("UPDATE {$tableName} SET field_value = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $valueStr, $existing['id']);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, field_id, field_value, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $orderId, $fieldId, $valueStr);
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
}

