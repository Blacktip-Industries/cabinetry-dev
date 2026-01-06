<?php
/**
 * Layout Component - Database CRUD Functions
 * CRUD operations for layout definitions and assignments
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/layout_engine.php';

/**
 * Get layout definition by ID
 * @param int $layoutId Layout ID
 * @return array|null Layout definition or null
 */
function layout_get_definition($layoutId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('definitions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $layoutId);
        $stmt->execute();
        $result = $stmt->get_result();
        $layout = $result->fetch_assoc();
        $stmt->close();
        
        if ($layout && isset($layout['layout_data'])) {
            $layout['layout_data'] = json_decode($layout['layout_data'], true);
            if (isset($layout['tags'])) {
                $layout['tags'] = json_decode($layout['tags'], true);
            }
        }
        
        return $layout;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error getting layout: " . $e->getMessage());
        return null;
    }
}

/**
 * Get layout definition by name
 * @param string $name Layout name
 * @return array|null Layout definition or null
 */
function layout_get_definition_by_name($name) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('definitions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE name = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $layout = $result->fetch_assoc();
        $stmt->close();
        
        if ($layout && isset($layout['layout_data'])) {
            $layout['layout_data'] = json_decode($layout['layout_data'], true);
            if (isset($layout['tags'])) {
                $layout['tags'] = json_decode($layout['tags'], true);
            }
        }
        
        return $layout;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error getting layout by name: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all layout definitions
 * @param array $filters Filters (status, category, is_preset, etc.)
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Array of layout definitions
 */
function layout_get_definitions($filters = [], $limit = 100, $offset = 0) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('definitions');
        $where = [];
        $params = [];
        $types = '';
        
        if (isset($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (isset($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
            $types .= 's';
        }
        
        if (isset($filters['is_preset'])) {
            $where[] = "is_preset = ?";
            $params[] = $filters['is_preset'] ? 1 : 0;
            $types .= 'i';
        }
        
        if (isset($filters['is_default'])) {
            $where[] = "is_default = ?";
            $params[] = $filters['is_default'] ? 1 : 0;
            $types .= 'i';
        }
        
        if (isset($filters['created_by'])) {
            $where[] = "created_by = ?";
            $params[] = $filters['created_by'];
            $types .= 'i';
        }
        
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $layouts = [];
        
        while ($row = $result->fetch_assoc()) {
            if (isset($row['layout_data'])) {
                $row['layout_data'] = json_decode($row['layout_data'], true);
            }
            if (isset($row['tags'])) {
                $row['tags'] = json_decode($row['tags'], true);
            }
            $layouts[] = $row;
        }
        
        $stmt->close();
        return $layouts;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error getting layouts: " . $e->getMessage());
        return [];
    }
}

/**
 * Create layout definition
 * @param array $data Layout data
 * @return array Result with 'success' (bool) and 'id' (int) or 'error' (string)
 */
function layout_create_definition($data) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate required fields
    if (empty($data['name'])) {
        return ['success' => false, 'error' => 'Layout name is required'];
    }
    
    if (empty($data['layout_data'])) {
        return ['success' => false, 'error' => 'Layout data is required'];
    }
    
    // Validate layout data
    $validation = layout_validate_layout($data['layout_data'], true);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => 'Invalid layout data: ' . implode(', ', $validation['errors'])];
    }
    
    try {
        $tableName = layout_get_table_name('definitions');
        
        $name = $data['name'];
        $description = $data['description'] ?? null;
        $layoutDataJson = json_encode($data['layout_data']);
        $isDefault = isset($data['is_default']) ? (int)$data['is_default'] : 0;
        $isPreset = isset($data['is_preset']) ? (int)$data['is_preset'] : 0;
        $version = $data['version'] ?? '1.0.0';
        $tagsJson = isset($data['tags']) ? json_encode($data['tags']) : null;
        $category = $data['category'] ?? null;
        $notes = $data['notes'] ?? null;
        $status = $data['status'] ?? 'draft';
        $parentLayoutId = $data['parent_layout_id'] ?? null;
        $createdBy = $data['created_by'] ?? ($_SESSION['user_id'] ?? null);
        
        $query = "INSERT INTO {$tableName} (name, description, layout_data, is_default, is_preset, version, tags, category, notes, status, parent_layout_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement'];
        }
        
        $stmt->bind_param("sssiissssssii", $name, $description, $layoutDataJson, $isDefault, $isPreset, $version, $tagsJson, $category, $notes, $status, $parentLayoutId, $createdBy);
        
        if ($stmt->execute()) {
            $layoutId = $conn->insert_id;
            $stmt->close();
            
            // Track component dependencies
            layout_update_component_dependencies($layoutId, $data['layout_data']);
            
            return ['success' => true, 'id' => $layoutId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error creating layout: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update layout definition
 * @param int $layoutId Layout ID
 * @param array $data Layout data
 * @return array Result with 'success' (bool) or 'error' (string)
 */
function layout_update_definition($layoutId, $data) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate layout data if provided
    if (isset($data['layout_data'])) {
        $validation = layout_validate_layout($data['layout_data'], true);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => 'Invalid layout data: ' . implode(', ', $validation['errors'])];
        }
    }
    
    try {
        $tableName = layout_get_table_name('definitions');
        
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = $data['name'];
            $types .= 's';
        }
        
        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
            $types .= 's';
        }
        
        if (isset($data['layout_data'])) {
            $updates[] = "layout_data = ?";
            $params[] = json_encode($data['layout_data']);
            $types .= 's';
        }
        
        if (isset($data['is_default'])) {
            $updates[] = "is_default = ?";
            $params[] = (int)$data['is_default'];
            $types .= 'i';
        }
        
        if (isset($data['is_preset'])) {
            $updates[] = "is_preset = ?";
            $params[] = (int)$data['is_preset'];
            $types .= 'i';
        }
        
        if (isset($data['version'])) {
            $updates[] = "version = ?";
            $params[] = $data['version'];
            $types .= 's';
        }
        
        if (isset($data['tags'])) {
            $updates[] = "tags = ?";
            $params[] = json_encode($data['tags']);
            $types .= 's';
        }
        
        if (isset($data['category'])) {
            $updates[] = "category = ?";
            $params[] = $data['category'];
            $types .= 's';
        }
        
        if (isset($data['notes'])) {
            $updates[] = "notes = ?";
            $params[] = $data['notes'];
            $types .= 's';
        }
        
        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
            $types .= 's';
        }
        
        if (isset($data['parent_layout_id'])) {
            $updates[] = "parent_layout_id = ?";
            $params[] = $data['parent_layout_id'];
            $types .= 'i';
        }
        
        if (isset($data['last_edited_by'])) {
            $updates[] = "last_edited_by = ?";
            $params[] = $data['last_edited_by'];
            $types .= 'i';
        } else {
            $updates[] = "last_edited_by = ?";
            $params[] = $_SESSION['user_id'] ?? null;
            $types .= 'i';
        }
        
        $updates[] = "last_edited_at = NOW()";
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = $layoutId;
        $types .= 'i';
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement'];
        }
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update component dependencies if layout_data changed
            if (isset($data['layout_data'])) {
                layout_update_component_dependencies($layoutId, $data['layout_data']);
                
                // Validate dependencies after update
                if (function_exists('layout_validate_layout_dependencies')) {
                    require_once __DIR__ . '/component_integration.php';
                    $validation = layout_validate_layout_dependencies($layoutId);
                    if (!$validation['valid']) {
                        error_log("Layout Database: Layout updated with missing dependencies: " . json_encode($validation['issues']));
                    }
                }
            }
            
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error updating layout: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete layout definition
 * @param int $layoutId Layout ID
 * @return array Result with 'success' (bool) or 'error' (string)
 */
function layout_delete_definition($layoutId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Dependencies will be automatically deleted via CASCADE, but we can validate first
        if (function_exists('layout_validate_layout_dependencies')) {
            require_once __DIR__ . '/component_integration.php';
            $validation = layout_validate_layout_dependencies($layoutId);
            // Log warnings but don't block deletion
            if (!empty($validation['warnings'])) {
                error_log("Layout Database: Deleting layout with warnings: " . json_encode($validation['warnings']));
            }
        }
        
        $tableName = layout_get_table_name('definitions');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement'];
        }
        
        $stmt->bind_param("i", $layoutId);
        $result = $stmt->execute();
        $stmt->close();
        
        // Note: Dependencies are automatically deleted via CASCADE DELETE foreign key
        
        return ['success' => $result];
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error deleting layout: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get layout assignment for page
 * @param string $pageName Page name
 * @return array|null Assignment or null
 */
function layout_get_assignment($pageName) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('assignments');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE page_name = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $pageName);
        $stmt->execute();
        $result = $stmt->get_result();
        $assignment = $result->fetch_assoc();
        $stmt->close();
        
        if ($assignment && isset($assignment['custom_overrides'])) {
            $assignment['custom_overrides'] = json_decode($assignment['custom_overrides'], true);
        }
        
        return $assignment;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error getting assignment: " . $e->getMessage());
        return null;
    }
}

/**
 * Set layout assignment for page
 * @param string $pageName Page name
 * @param int $layoutId Layout ID
 * @param array $customOverrides Custom overrides (optional)
 * @return array Result with 'success' (bool) or 'error' (string)
 */
function layout_set_assignment($pageName, $layoutId, $customOverrides = null) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('assignments');
        $overridesJson = $customOverrides ? json_encode($customOverrides) : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (page_name, layout_id, custom_overrides) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE layout_id = ?, custom_overrides = ?, updated_at = NOW()");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement'];
        }
        
        $stmt->bind_param("sisis", $pageName, $layoutId, $overridesJson, $layoutId, $overridesJson);
        $result = $stmt->execute();
        $stmt->close();
        
        return ['success' => $result];
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error setting assignment: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete layout assignment
 * @param string $pageName Page name
 * @return array Result with 'success' (bool) or 'error' (string)
 */
function layout_delete_assignment($pageName) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('assignments');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE page_name = ?");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement'];
        }
        
        $stmt->bind_param("s", $pageName);
        $result = $stmt->execute();
        $stmt->close();
        
        return ['success' => $result];
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error deleting assignment: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get layout versions
 * @param int $layoutId Layout ID
 * @param int $limit Limit
 * @return array Array of versions
 */
function layout_get_versions($layoutId, $limit = 50) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('versions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE layout_id = ? ORDER BY created_at DESC LIMIT ?");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("ii", $layoutId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $versions = [];
        
        while ($row = $result->fetch_assoc()) {
            if (isset($row['layout_data'])) {
                $row['layout_data'] = json_decode($row['layout_data'], true);
            }
            $versions[] = $row;
        }
        
        $stmt->close();
        return $versions;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error getting versions: " . $e->getMessage());
        return [];
    }
}

/**
 * Create layout version
 * @param int $layoutId Layout ID
 * @param array $layoutData Layout data
 * @param string $changeDescription Change description
 * @return array Result with 'success' (bool) and 'id' (int) or 'error' (string)
 */
function layout_create_version($layoutId, $layoutData, $changeDescription = '') {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('versions');
        
        // Get current version number
        $currentVersion = layout_get_latest_version($layoutId);
        $versionNumber = $currentVersion ? (floatval($currentVersion['version']) + 0.1) : 1.0;
        $versionString = number_format($versionNumber, 1, '.', '');
        
        $layoutDataJson = json_encode($layoutData);
        $createdBy = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (layout_id, version, layout_data, change_description, created_by) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement'];
        }
        
        $stmt->bind_param("isssi", $layoutId, $versionString, $layoutDataJson, $changeDescription, $createdBy);
        
        if ($stmt->execute()) {
            $versionId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $versionId, 'version' => $versionString];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error creating version: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get latest version for layout
 * @param int $layoutId Layout ID
 * @return array|null Version or null
 */
function layout_get_latest_version($layoutId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('versions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE layout_id = ? ORDER BY version DESC LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $layoutId);
        $stmt->execute();
        $result = $stmt->get_result();
        $version = $result->fetch_assoc();
        $stmt->close();
        
        if ($version && isset($version['layout_data'])) {
            $version['layout_data'] = json_decode($version['layout_data'], true);
        }
        
        return $version;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error getting latest version: " . $e->getMessage());
        return null;
    }
}

/**
 * Update component dependencies for layout
 * @param int $layoutId Layout ID
 * @param array $layoutData Layout data
 * @return bool Success
 */
function layout_update_component_dependencies($layoutId, $layoutData) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('component_dependencies');
        
        // Delete existing dependencies
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE layout_id = ?");
        $stmt->bind_param("i", $layoutId);
        $stmt->execute();
        $stmt->close();
        
        // Extract component names from layout data
        $components = layout_extract_components($layoutData);
        
        // Insert new dependencies
        // Check if component_integration functions are available for better handling
        if (function_exists('layout_component_dependency_create')) {
            require_once __DIR__ . '/component_integration.php';
            foreach ($components as $componentName) {
                // Default to required, but could be made configurable
                layout_component_dependency_create($layoutId, $componentName, true);
            }
        } else {
            // Fallback to direct insert
            foreach ($components as $componentName) {
                $stmt = $conn->prepare("INSERT INTO {$tableName} (layout_id, component_name, is_required) VALUES (?, ?, 1)");
                $stmt->bind_param("is", $layoutId, $componentName);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Database: Error updating dependencies: " . $e->getMessage());
        return false;
    }
}

/**
 * Extract component names from layout data
 * @param array $layoutData Layout data
 * @return array Array of component names
 */
function layout_extract_components($layoutData) {
    $components = [];
    
    if (!is_array($layoutData)) {
        return $components;
    }
    
    if (isset($layoutData['component']) && !empty($layoutData['component'])) {
        $components[] = $layoutData['component'];
    }
    
    if (isset($layoutData['sections']) && is_array($layoutData['sections'])) {
        foreach ($layoutData['sections'] as $section) {
            $subComponents = layout_extract_components($section);
            $components = array_merge($components, $subComponents);
        }
    }
    
    return array_unique($components);
}

