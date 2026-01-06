<?php
/**
 * Layout Component - Design Systems CRUD Functions
 * CRUD operations for design systems with hierarchical inheritance
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/element_templates.php';

/**
 * Create design system
 * @param array $data Design system data
 * @return array Result with success status and design system ID
 */
function layout_design_system_create($data) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate required fields
    if (empty($data['name'])) {
        return ['success' => false, 'error' => 'Name is required'];
    }
    
    try {
        $tableName = layout_get_table_name('design_systems');
        
        $name = $data['name'];
        $description = $data['description'] ?? null;
        $parentDesignSystemId = $data['parent_design_system_id'] ?? null;
        $themeData = isset($data['theme_data']) ? json_encode($data['theme_data']) : null;
        $performanceSettings = isset($data['performance_settings']) ? json_encode($data['performance_settings']) : null;
        $accessibilitySettings = isset($data['accessibility_settings']) ? json_encode($data['accessibility_settings']) : null;
        $isDefault = isset($data['is_default']) ? (int)$data['is_default'] : 0;
        $isPublished = isset($data['is_published']) ? (int)$data['is_published'] : 0;
        $version = $data['version'] ?? '1.0.0';
        $tags = isset($data['tags']) ? json_encode($data['tags']) : null;
        $category = $data['category'] ?? null;
        $createdBy = $data['created_by'] ?? ($_SESSION['user_id'] ?? null);
        
        // If setting as default, unset other defaults
        if ($isDefault) {
            $unsetStmt = $conn->prepare("UPDATE {$tableName} SET is_default = 0");
            $unsetStmt->execute();
            $unsetStmt->close();
        }
        
        $query = "INSERT INTO {$tableName} (name, description, parent_design_system_id, theme_data, performance_settings, accessibility_settings, is_default, is_published, version, tags, category, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param("ssisssiisssi", $name, $description, $parentDesignSystemId, $themeData, $performanceSettings, $accessibilitySettings, $isDefault, $isPublished, $version, $tags, $category, $createdBy);
        
        if ($stmt->execute()) {
            $designSystemId = $conn->insert_id;
            $stmt->close();
            
            // Add element templates if provided
            if (!empty($data['element_templates'])) {
                foreach ($data['element_templates'] as $elementTemplateId) {
                    layout_design_system_add_element($designSystemId, $elementTemplateId, isset($data['element_overrides'][$elementTemplateId]));
                }
            }
            
            // Log audit trail
            layout_audit_log('create', 'design_system', $designSystemId, ['name' => $name, 'parent_id' => $parentDesignSystemId]);
            
            return ['success' => true, 'id' => $designSystemId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Design Systems: Error creating design system: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get design system by ID
 * @param int $designSystemId Design system ID
 * @param bool $includeInherited Whether to include inherited elements from parent
 * @return array|null Design system data or null
 */
function layout_design_system_get($designSystemId, $includeInherited = true) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('design_systems');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $designSystemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $designSystem = $result->fetch_assoc();
        $stmt->close();
        
        if ($designSystem) {
            // Decode JSON fields
            $jsonFields = ['theme_data', 'performance_settings', 'accessibility_settings', 'tags'];
            foreach ($jsonFields as $field) {
                if (isset($designSystem[$field]) && $designSystem[$field] !== null) {
                    $designSystem[$field] = json_decode($designSystem[$field], true);
                }
            }
            
            // Get element templates
            $designSystem['element_templates'] = layout_design_system_get_elements($designSystemId, $includeInherited);
        }
        
        return $designSystem;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Design Systems: Error getting design system: " . $e->getMessage());
        return null;
    }
}

/**
 * Update design system
 * @param int $designSystemId Design system ID
 * @param array $data Design system data
 * @return array Result with success status
 */
function layout_design_system_update($designSystemId, $data) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('design_systems');
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        $types = '';
        
        $allowedFields = ['name', 'description', 'parent_design_system_id', 'theme_data', 'performance_settings', 'accessibility_settings', 'is_default', 'is_published', 'version', 'tags', 'category'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                if (in_array($field, ['theme_data', 'performance_settings', 'accessibility_settings', 'tags'])) {
                    $params[] = json_encode($data[$field]);
                    $types .= 's';
                } elseif (in_array($field, ['parent_design_system_id', 'is_default', 'is_published'])) {
                    $params[] = $data[$field];
                    $types .= 'i';
                } else {
                    $params[] = $data[$field];
                    $types .= 's';
                }
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        // If setting as default, unset other defaults
        if (isset($data['is_default']) && $data['is_default']) {
            $unsetStmt = $conn->prepare("UPDATE {$tableName} SET is_default = 0 WHERE id != ?");
            $unsetStmt->bind_param("i", $designSystemId);
            $unsetStmt->execute();
            $unsetStmt->close();
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $designSystemId;
        $types .= 'i';
        
        $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update element templates if provided
            if (isset($data['element_templates'])) {
                // Remove all existing associations
                layout_design_system_remove_all_elements($designSystemId);
                
                // Add new associations
                foreach ($data['element_templates'] as $elementTemplateId) {
                    $isOverride = isset($data['element_overrides'][$elementTemplateId]);
                    layout_design_system_add_element($designSystemId, $elementTemplateId, $isOverride, $data['element_overrides'][$elementTemplateId] ?? null);
                }
            }
            
            // Log audit trail
            layout_audit_log('update', 'design_system', $designSystemId, $data);
            
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Design Systems: Error updating design system: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete design system
 * @param int $designSystemId Design system ID
 * @return array Result with success status
 */
function layout_design_system_delete($designSystemId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('design_systems');
        
        // Get design system name for audit log
        $designSystem = layout_design_system_get($designSystemId, false);
        $designSystemName = $designSystem ? $designSystem['name'] : 'Unknown';
        
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param("i", $designSystemId);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log audit trail
            layout_audit_log('delete', 'design_system', $designSystemId, ['name' => $designSystemName]);
            
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Design Systems: Error deleting design system: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get all design systems
 * @param array $filters Optional filters
 * @return array Array of design systems
 */
function layout_design_system_get_all($filters = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('design_systems');
        
        $query = "SELECT * FROM {$tableName} WHERE 1=1";
        $params = [];
        $types = '';
        
        if (isset($filters['is_published'])) {
            $query .= " AND is_published = ?";
            $params[] = (int)$filters['is_published'];
            $types .= 'i';
        }
        
        if (isset($filters['is_default'])) {
            $query .= " AND is_default = ?";
            $params[] = (int)$filters['is_default'];
            $types .= 'i';
        }
        
        if (!empty($filters['category'])) {
            $query .= " AND category = ?";
            $params[] = $filters['category'];
            $types .= 's';
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
        $designSystems = [];
        
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields
            $jsonFields = ['theme_data', 'performance_settings', 'accessibility_settings', 'tags'];
            foreach ($jsonFields as $field) {
                if (isset($row[$field]) && $row[$field] !== null) {
                    $row[$field] = json_decode($row[$field], true);
                }
            }
            $designSystems[] = $row;
        }
        
        $stmt->close();
        return $designSystems;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Design Systems: Error getting design systems: " . $e->getMessage());
        return [];
    }
}

/**
 * Handle hierarchical inheritance
 * @param int $designSystemId Design system ID
 * @return array Merged design system with inherited data
 */
function layout_design_system_inherit($designSystemId) {
    $designSystem = layout_design_system_get($designSystemId, false);
    if (!$designSystem) {
        return null;
    }
    
    // If no parent, return as-is
    if (empty($designSystem['parent_design_system_id'])) {
        return $designSystem;
    }
    
    // Get parent design system
    $parent = layout_design_system_inherit($designSystem['parent_design_system_id']);
    if (!$parent) {
        return $designSystem;
    }
    
    // Merge theme data (child overrides parent)
    if (empty($designSystem['theme_data']) && !empty($parent['theme_data'])) {
        $designSystem['theme_data'] = $parent['theme_data'];
    } elseif (!empty($designSystem['theme_data']) && !empty($parent['theme_data'])) {
        $designSystem['theme_data'] = array_merge($parent['theme_data'], $designSystem['theme_data']);
    }
    
    // Merge performance settings
    if (empty($designSystem['performance_settings']) && !empty($parent['performance_settings'])) {
        $designSystem['performance_settings'] = $parent['performance_settings'];
    } elseif (!empty($designSystem['performance_settings']) && !empty($parent['performance_settings'])) {
        $designSystem['performance_settings'] = array_merge($parent['performance_settings'], $designSystem['performance_settings']);
    }
    
    // Merge accessibility settings
    if (empty($designSystem['accessibility_settings']) && !empty($parent['accessibility_settings'])) {
        $designSystem['accessibility_settings'] = $parent['accessibility_settings'];
    } elseif (!empty($designSystem['accessibility_settings']) && !empty($parent['accessibility_settings'])) {
        $designSystem['accessibility_settings'] = array_merge($parent['accessibility_settings'], $designSystem['accessibility_settings']);
    }
    
    // Merge element templates (child overrides parent)
    $parentElements = $parent['element_templates'] ?? [];
    $childElements = $designSystem['element_templates'] ?? [];
    
    // Create map of child elements by template ID
    $childElementMap = [];
    foreach ($childElements as $element) {
        $childElementMap[$element['element_template_id']] = $element;
    }
    
    // Start with parent elements, then override with child elements
    $mergedElements = [];
    foreach ($parentElements as $parentElement) {
        $templateId = $parentElement['element_template_id'];
        if (isset($childElementMap[$templateId])) {
            // Child overrides parent
            $mergedElements[] = $childElementMap[$templateId];
            unset($childElementMap[$templateId]);
        } else {
            // Inherit from parent
            $mergedElements[] = $parentElement;
        }
    }
    
    // Add any remaining child elements that weren't in parent
    foreach ($childElementMap as $element) {
        $mergedElements[] = $element;
    }
    
    $designSystem['element_templates'] = $mergedElements;
    
    return $designSystem;
}

/**
 * Add element template to design system
 * @param int $designSystemId Design system ID
 * @param int $elementTemplateId Element template ID
 * @param bool $isOverride Whether this is an override
 * @param array|null $overrideData Override data
 * @return bool Success
 */
function layout_design_system_add_element($designSystemId, $elementTemplateId, $isOverride = false, $overrideData = null) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('design_system_elements');
        $overrideDataJson = $overrideData ? json_encode($overrideData) : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (design_system_id, element_template_id, is_override, override_data) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_override = ?, override_data = ?");
        if (!$stmt) {
            return false;
        }
        
        $isOverrideInt = $isOverride ? 1 : 0;
        $stmt->bind_param("iiisisi", $designSystemId, $elementTemplateId, $isOverrideInt, $overrideDataJson, $isOverrideInt, $overrideDataJson);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Design Systems: Error adding element: " . $e->getMessage());
        return false;
    }
}

/**
 * Get element templates for design system
 * @param int $designSystemId Design system ID
 * @param bool $includeInherited Whether to include inherited elements
 * @return array Array of element template associations
 */
function layout_design_system_get_elements($designSystemId, $includeInherited = true) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('design_system_elements');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE design_system_id = ?");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $designSystemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $elements = [];
        
        while ($row = $result->fetch_assoc()) {
            if (isset($row['override_data']) && $row['override_data'] !== null) {
                $row['override_data'] = json_decode($row['override_data'], true);
            }
            $elements[] = $row;
        }
        
        $stmt->close();
        
        // If including inherited, merge with parent
        if ($includeInherited) {
            $designSystem = layout_design_system_get($designSystemId, false);
            if ($designSystem && !empty($designSystem['parent_design_system_id'])) {
                $parentElements = layout_design_system_get_elements($designSystem['parent_design_system_id'], true);
                
                // Merge: child overrides parent
                $childElementMap = [];
                foreach ($elements as $element) {
                    $childElementMap[$element['element_template_id']] = $element;
                }
                
                $merged = [];
                foreach ($parentElements as $parentElement) {
                    $templateId = $parentElement['element_template_id'];
                    if (isset($childElementMap[$templateId])) {
                        $merged[] = $childElementMap[$templateId];
                        unset($childElementMap[$templateId]);
                    } else {
                        $merged[] = $parentElement;
                    }
                }
                
                foreach ($childElementMap as $element) {
                    $merged[] = $element;
                }
                
                $elements = $merged;
            }
        }
        
        return $elements;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Design Systems: Error getting elements: " . $e->getMessage());
        return [];
    }
}

/**
 * Remove all element templates from design system
 * @param int $designSystemId Design system ID
 * @return bool Success
 */
function layout_design_system_remove_all_elements($designSystemId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('design_system_elements');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE design_system_id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $designSystemId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Design Systems: Error removing elements: " . $e->getMessage());
        return false;
    }
}

