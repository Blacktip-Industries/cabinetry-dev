<?php
/**
 * Layout Component - Element Templates CRUD Functions
 * CRUD operations for element templates
 */

require_once __DIR__ . '/database.php';

/**
 * Create element template
 * @param array $data Template data
 * @return array Result with success status and template ID
 */
function layout_element_template_create($data) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate required fields
    if (empty($data['name']) || empty($data['element_type']) || empty($data['html'])) {
        return ['success' => false, 'error' => 'Name, element_type, and html are required'];
    }
    
    try {
        $tableName = layout_get_table_name('element_templates');
        
        $name = $data['name'];
        $description = $data['description'] ?? null;
        $elementType = $data['element_type'];
        $category = $data['category'] ?? null;
        $html = $data['html'];
        $css = $data['css'] ?? null;
        $js = $data['js'] ?? null;
        $customCode = isset($data['custom_code']) ? json_encode($data['custom_code']) : null;
        $animations = isset($data['animations']) ? json_encode($data['animations']) : null;
        $properties = isset($data['properties']) ? json_encode($data['properties']) : null;
        $variants = isset($data['variants']) ? json_encode($data['variants']) : null;
        $tags = isset($data['tags']) ? json_encode($data['tags']) : null;
        $accessibilityData = isset($data['accessibility_data']) ? json_encode($data['accessibility_data']) : null;
        $validationStatus = $data['validation_status'] ?? 'pending';
        $performanceScore = $data['performance_score'] ?? null;
        $isPublished = isset($data['is_published']) ? (int)$data['is_published'] : 0;
        $createdBy = $data['created_by'] ?? ($_SESSION['user_id'] ?? null);
        
        $query = "INSERT INTO {$tableName} (name, description, element_type, category, html, css, js, custom_code, animations, properties, variants, tags, accessibility_data, validation_status, performance_score, is_published, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param("sssssssssssssssiii", $name, $description, $elementType, $category, $html, $css, $js, $customCode, $animations, $properties, $variants, $tags, $accessibilityData, $validationStatus, $performanceScore, $isPublished, $createdBy);
        
        if ($stmt->execute()) {
            $templateId = $conn->insert_id;
            $stmt->close();
            
            // Log audit trail
            layout_audit_log('create', 'element_template', $templateId, ['name' => $name, 'element_type' => $elementType]);
            
            return ['success' => true, 'id' => $templateId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Element Templates: Error creating template: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get element template by ID
 * @param int $templateId Template ID
 * @return array|null Template data or null
 */
function layout_element_template_get($templateId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('element_templates');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        
        if ($template) {
            // Decode JSON fields
            $jsonFields = ['custom_code', 'animations', 'properties', 'variants', 'tags', 'accessibility_data'];
            foreach ($jsonFields as $field) {
                if (isset($template[$field]) && $template[$field] !== null) {
                    $template[$field] = json_decode($template[$field], true);
                }
            }
        }
        
        return $template;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Element Templates: Error getting template: " . $e->getMessage());
        return null;
    }
}

/**
 * Update element template
 * @param int $templateId Template ID
 * @param array $data Template data
 * @return array Result with success status
 */
function layout_element_template_update($templateId, $data) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('element_templates');
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['name', 'description', 'element_type', 'category', 'html', 'css', 'js', 'custom_code', 'animations', 'properties', 'variants', 'tags', 'accessibility_data', 'validation_status', 'performance_score', 'is_published'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                if (in_array($field, ['custom_code', 'animations', 'properties', 'variants', 'tags', 'accessibility_data'])) {
                    $params[] = json_encode($data[$field]);
                    $types .= 's';
                } elseif (in_array($field, ['validation_status', 'performance_score', 'is_published'])) {
                    $params[] = $data[$field];
                    $types .= is_int($data[$field]) ? 'i' : 's';
                } else {
                    $params[] = $data[$field];
                    $types .= 's';
                }
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $templateId;
        $types .= 'i';
        
        $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log audit trail
            layout_audit_log('update', 'element_template', $templateId, $data);
            
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Element Templates: Error updating template: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete element template
 * @param int $templateId Template ID
 * @return array Result with success status
 */
function layout_element_template_delete($templateId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('element_templates');
        
        // Get template name for audit log
        $template = layout_element_template_get($templateId);
        $templateName = $template ? $template['name'] : 'Unknown';
        
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param("i", $templateId);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log audit trail
            layout_audit_log('delete', 'element_template', $templateId, ['name' => $templateName]);
            
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Element Templates: Error deleting template: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get all element templates
 * @param array $filters Optional filters (element_type, category, is_published, etc.)
 * @return array Array of templates
 */
function layout_element_template_get_all($filters = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('element_templates');
        
        $query = "SELECT * FROM {$tableName} WHERE 1=1";
        $params = [];
        $types = '';
        
        if (!empty($filters['element_type'])) {
            $query .= " AND element_type = ?";
            $params[] = $filters['element_type'];
            $types .= 's';
        }
        
        if (!empty($filters['category'])) {
            $query .= " AND category = ?";
            $params[] = $filters['category'];
            $types .= 's';
        }
        
        if (isset($filters['is_published'])) {
            $query .= " AND is_published = ?";
            $params[] = (int)$filters['is_published'];
            $types .= 'i';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (name LIKE ? OR description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        $query .= " ORDER BY created_at DESC";
        
        if (!empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $templates = [];
        
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields
            $jsonFields = ['custom_code', 'animations', 'properties', 'variants', 'tags', 'accessibility_data'];
            foreach ($jsonFields as $field) {
                if (isset($row[$field]) && $row[$field] !== null) {
                    $row[$field] = json_decode($row[$field], true);
                }
            }
            $templates[] = $row;
        }
        
        $stmt->close();
        return $templates;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Element Templates: Error getting templates: " . $e->getMessage());
        return [];
    }
}

/**
 * Audit log helper function
 * @param string $action Action performed
 * @param string $resourceType Resource type
 * @param int $resourceId Resource ID
 * @param array $details Additional details
 */
function layout_audit_log($action, $resourceType, $resourceId, $details = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return;
    }
    
    try {
        $tableName = layout_get_table_name('audit_logs');
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $actionDetails = !empty($details) ? json_encode($details) : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (user_id, action, resource_type, resource_id, action_details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ississs", $userId, $action, $resourceType, $resourceId, $actionDetails, $ipAddress, $userAgent);
            $stmt->execute();
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Audit Log: Error: " . $e->getMessage());
    }
}

