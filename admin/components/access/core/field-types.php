<?php
/**
 * Access Component - Field Type System
 * Handles custom field type registration and rendering
 */

/**
 * Register custom field type
 * @param array $fieldTypeData Field type data
 * @return int|false Field type ID on success, false on failure
 */
function access_register_field_type($fieldTypeData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_field_types (field_type_key, field_type_name, handler_class, validation_class, render_function, is_system, metadata) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE field_type_name = VALUES(field_type_name), handler_class = VALUES(handler_class), validation_class = VALUES(validation_class), render_function = VALUES(render_function), metadata = VALUES(metadata)");
        $metadata = isset($fieldTypeData['metadata']) ? (is_string($fieldTypeData['metadata']) ? $fieldTypeData['metadata'] : json_encode($fieldTypeData['metadata'])) : null;
        $isSystem = $fieldTypeData['is_system'] ?? 0;
        $stmt->bind_param("sssssis",
            $fieldTypeData['field_type_key'],
            $fieldTypeData['field_type_name'],
            $fieldTypeData['handler_class'] ?? null,
            $fieldTypeData['validation_class'] ?? null,
            $fieldTypeData['render_function'] ?? null,
            $isSystem,
            $metadata
        );
        
        if ($stmt->execute()) {
            $fieldTypeId = $conn->insert_id;
            $stmt->close();
            return $fieldTypeId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error registering field type: " . $e->getMessage());
        return false;
    }
}

/**
 * Get field type by key
 * @param string $fieldTypeKey Field type key
 * @return array|null Field type data or null
 */
function access_get_field_type($fieldTypeKey) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_field_types WHERE field_type_key = ?");
        $stmt->bind_param("s", $fieldTypeKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $fieldType = $result->fetch_assoc();
        $stmt->close();
        return $fieldType;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting field type: " . $e->getMessage());
        return null;
    }
}

/**
 * List all field types
 * @param bool $includeSystem Include system field types
 * @return array Field types list
 */
function access_list_field_types($includeSystem = true) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM access_field_types";
        if (!$includeSystem) {
            $sql .= " WHERE is_system = 0";
        }
        $sql .= " ORDER BY field_type_name ASC";
        
        $result = $conn->query($sql);
        $fieldTypes = [];
        while ($row = $result->fetch_assoc()) {
            $fieldTypes[] = $row;
        }
        return $fieldTypes;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error listing field types: " . $e->getMessage());
        return [];
    }
}

