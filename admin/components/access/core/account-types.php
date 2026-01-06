<?php
/**
 * Access Component - Account Type Functions
 * Handles account type management and custom fields
 */

require_once __DIR__ . '/database.php';

/**
 * Create account type
 * @param array $accountTypeData Account type data
 * @return int|false Account type ID on success, false on failure
 */
function access_create_account_type($accountTypeData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_account_types (name, slug, description, requires_approval, auto_approve, special_requirements, registration_workflow, custom_validation_hook, is_active, display_order, icon, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiisssiiss",
            $accountTypeData['name'],
            $accountTypeData['slug'],
            $accountTypeData['description'] ?? null,
            $accountTypeData['requires_approval'] ?? 0,
            $accountTypeData['auto_approve'] ?? 0,
            $accountTypeData['special_requirements'] ?? null,
            $accountTypeData['registration_workflow'] ?? null,
            $accountTypeData['custom_validation_hook'] ?? null,
            $accountTypeData['is_active'] ?? 1,
            $accountTypeData['display_order'] ?? 0,
            $accountTypeData['icon'] ?? null,
            $accountTypeData['color'] ?? null
        );
        
        if ($stmt->execute()) {
            $accountTypeId = $conn->insert_id;
            $stmt->close();
            return $accountTypeId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error creating account type: " . $e->getMessage());
        return false;
    }
}

/**
 * Update account type
 * @param int $accountTypeId Account type ID
 * @param array $accountTypeData Account type data to update
 * @return bool Success
 */
function access_update_account_type($accountTypeId, $accountTypeData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $fields = [];
    $values = [];
    $types = '';
    
    $allowedFields = ['name', 'slug', 'description', 'requires_approval', 'auto_approve', 'special_requirements', 'registration_workflow', 'custom_validation_hook', 'is_active', 'display_order', 'icon', 'color'];
    
    foreach ($accountTypeData as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $fields[] = "$key = ?";
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $values[] = $accountTypeId;
    $types .= 'i';
    
    try {
        $sql = "UPDATE access_account_types SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error updating account type: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete account type
 * @param int $accountTypeId Account type ID
 * @return bool Success
 */
function access_delete_account_type($accountTypeId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM access_account_types WHERE id = ?");
        $stmt->bind_param("i", $accountTypeId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error deleting account type: " . $e->getMessage());
        return false;
    }
}

/**
 * Create account type field
 * @param array $fieldData Field data
 * @return int|false Field ID on success, false on failure
 */
function access_create_account_type_field($fieldData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_account_type_fields (account_type_id, field_name, field_label, field_type, is_required, validation_rules, options_json, default_value, placeholder, help_text, conditional_logic, display_order, section, field_group, css_class, wrapper_class) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssissssssississ",
            $fieldData['account_type_id'],
            $fieldData['field_name'],
            $fieldData['field_label'],
            $fieldData['field_type'],
            $fieldData['is_required'] ?? 0,
            $fieldData['validation_rules'] ?? null,
            $fieldData['options_json'] ?? null,
            $fieldData['default_value'] ?? null,
            $fieldData['placeholder'] ?? null,
            $fieldData['help_text'] ?? null,
            $fieldData['conditional_logic'] ?? null,
            $fieldData['display_order'] ?? 0,
            $fieldData['section'] ?? null,
            $fieldData['field_group'] ?? null,
            $fieldData['css_class'] ?? null,
            $fieldData['wrapper_class'] ?? null
        );
        
        if ($stmt->execute()) {
            $fieldId = $conn->insert_id;
            $stmt->close();
            return $fieldId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error creating account type field: " . $e->getMessage());
        return false;
    }
}

/**
 * Update account type field
 * @param int $fieldId Field ID
 * @param array $fieldData Field data to update
 * @return bool Success
 */
function access_update_account_type_field($fieldId, $fieldData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $fields = [];
    $values = [];
    $types = '';
    
    $allowedFields = ['field_name', 'field_label', 'field_type', 'is_required', 'validation_rules', 'options_json', 'default_value', 'placeholder', 'help_text', 'conditional_logic', 'display_order', 'section', 'field_group', 'css_class', 'wrapper_class'];
    
    foreach ($fieldData as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $fields[] = "$key = ?";
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $values[] = $fieldId;
    $types .= 'i';
    
    try {
        $sql = "UPDATE access_account_type_fields SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error updating account type field: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete account type field
 * @param int $fieldId Field ID
 * @return bool Success
 */
function access_delete_account_type_field($fieldId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM access_account_type_fields WHERE id = ?");
        $stmt->bind_param("i", $fieldId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error deleting account type field: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate account type field value
 * @param array $field Field definition
 * @param mixed $value Field value
 * @return array ['valid' => bool, 'errors' => array]
 */
function access_validate_account_type_field($field, $value) {
    $errors = [];
    
    // Check required
    if (!empty($field['is_required']) && (empty($value) && $value !== '0')) {
        $errors[] = "Field '{$field['field_label']}' is required";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Skip validation if empty and not required
    if (empty($value) && $value !== '0') {
        return ['valid' => true, 'errors' => []];
    }
    
    // Type-specific validation
    switch ($field['field_type']) {
        case 'email':
            if (!access_validate_email($value)) {
                $errors[] = "Invalid email format";
            }
            break;
            
        case 'number':
        case 'decimal':
            if (!is_numeric($value)) {
                $errors[] = "Must be a number";
            }
            break;
            
        case 'url':
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[] = "Invalid URL format";
            }
            break;
    }
    
    // Custom validation rules
    if (!empty($field['validation_rules'])) {
        $rules = is_string($field['validation_rules']) ? json_decode($field['validation_rules'], true) : $field['validation_rules'];
        
        if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
            $errors[] = "Minimum length is {$rules['min_length']}";
        }
        
        if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
            $errors[] = "Maximum length is {$rules['max_length']}";
        }
        
        if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
            $errors[] = "Invalid format";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

