<?php
/**
 * Order Management Component - Templates Functions
 * Order template management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get template by ID
 * @param int $templateId Template ID
 * @return array|null Template data
 */
function order_management_get_template($templateId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('order_templates');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        
        if ($template) {
            $template['template_data'] = json_decode($template['template_data'], true);
        }
        
        return $template;
    }
    
    return null;
}

/**
 * Get all templates
 * @return array Array of templates
 */
function order_management_get_templates() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('order_templates');
    $result = $conn->query("SELECT * FROM {$tableName} ORDER BY name ASC");
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $row['template_data'] = json_decode($row['template_data'], true);
        $templates[] = $row;
    }
    
    return $templates;
}

/**
 * Create template
 * @param array $data Template data
 * @return array Result
 */
function order_management_create_template($data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('order_templates');
    
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? null;
    $templateData = isset($data['template_data']) ? json_encode($data['template_data']) : '{}';
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (name, description, template_data, created_at) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("sss", $name, $description, $templateData);
        if ($stmt->execute()) {
            $templateId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'template_id' => $templateId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Create order from template
 * @param int $templateId Template ID
 * @param array $overrides Override values
 * @return array Result
 */
function order_management_create_order_from_template($templateId, $overrides = []) {
    $template = order_management_get_template($templateId);
    if (!$template) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    $templateData = $template['template_data'];
    
    // Merge overrides
    $orderData = array_merge($templateData, $overrides);
    
    // Create order via commerce component
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    if (function_exists('commerce_create_order')) {
        $result = commerce_create_order($orderData);
        return $result;
    }
    
    return ['success' => false, 'error' => 'Order creation function not available'];
}

