<?php
/**
 * Layout Component - Version Management
 * Version history and rollback functionality
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/element_templates.php';

/**
 * Create version for element template
 * @param int $templateId Template ID
 * @param string $changeDescription Description of changes
 * @return array Result
 */
function layout_element_template_create_version($templateId, $changeDescription = '') {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get current template
    $template = layout_element_template_get($templateId);
    if (!$template) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    // Get current version count
    $tableName = layout_get_table_name('element_template_versions');
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM {$tableName} WHERE element_template_id = ?");
    $countStmt->bind_param("i", $templateId);
    $countStmt->execute();
    $result = $countStmt->get_result();
    $row = $result->fetch_assoc();
    $versionNumber = ($row['count'] ?? 0) + 1;
    $version = '1.' . $versionNumber . '.0';
    $countStmt->close();
    
    // Prepare template data for version
    $templateData = [
        'name' => $template['name'],
        'description' => $template['description'],
        'element_type' => $template['element_type'],
        'category' => $template['category'],
        'html' => $template['html'],
        'css' => $template['css'],
        'js' => $template['js'],
        'custom_code' => $template['custom_code'],
        'animations' => $template['animations'],
        'properties' => $template['properties'],
        'variants' => $template['variants'],
        'tags' => $template['tags'],
        'accessibility_data' => $template['accessibility_data']
    ];
    
    try {
        $templateDataJson = json_encode($templateData);
        $createdBy = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (element_template_id, version, template_data, change_description, created_by) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param("isssi", $templateId, $version, $templateDataJson, $changeDescription, $createdBy);
        
        if ($stmt->execute()) {
            $versionId = $conn->insert_id;
            $stmt->close();
            
            // Log audit trail
            layout_audit_log('create_version', 'element_template', $templateId, ['version' => $version]);
            
            return ['success' => true, 'id' => $versionId, 'version' => $version];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Versioning: Error creating version: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get version history for template
 * @param int $templateId Template ID
 * @return array Array of versions
 */
function layout_element_template_get_versions($templateId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('element_template_versions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE element_template_id = ? ORDER BY created_at DESC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        $versions = [];
        
        while ($row = $result->fetch_assoc()) {
            if (isset($row['template_data']) && $row['template_data'] !== null) {
                $row['template_data'] = json_decode($row['template_data'], true);
            }
            $versions[] = $row;
        }
        
        $stmt->close();
        return $versions;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Versioning: Error getting versions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get specific version
 * @param int $versionId Version ID
 * @return array|null Version data
 */
function layout_element_template_get_version($versionId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('element_template_versions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $versionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $version = $result->fetch_assoc();
        $stmt->close();
        
        if ($version && isset($version['template_data'])) {
            $version['template_data'] = json_decode($version['template_data'], true);
        }
        
        return $version;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Versioning: Error getting version: " . $e->getMessage());
        return null;
    }
}

/**
 * Rollback template to specific version
 * @param int $templateId Template ID
 * @param int $versionId Version ID to rollback to
 * @return array Result
 */
function layout_element_template_rollback($templateId, $versionId) {
    $version = layout_element_template_get_version($versionId);
    if (!$version || $version['element_template_id'] != $templateId) {
        return ['success' => false, 'error' => 'Version not found or does not belong to this template'];
    }
    
    // Create new version of current state before rollback
    layout_element_template_create_version($templateId, 'Auto-created before rollback');
    
    // Restore template data from version
    $templateData = $version['template_data'];
    $updateData = [
        'name' => $templateData['name'],
        'description' => $templateData['description'],
        'element_type' => $templateData['element_type'],
        'category' => $templateData['category'],
        'html' => $templateData['html'],
        'css' => $templateData['css'] ?? '',
        'js' => $templateData['js'] ?? '',
        'custom_code' => $templateData['custom_code'] ?? [],
        'animations' => $templateData['animations'] ?? [],
        'properties' => $templateData['properties'] ?? [],
        'variants' => $templateData['variants'] ?? [],
        'tags' => $templateData['tags'] ?? [],
        'accessibility_data' => $templateData['accessibility_data'] ?? []
    ];
    
    $result = layout_element_template_update($templateId, $updateData);
    
    if ($result['success']) {
        // Create version record for rollback
        layout_element_template_create_version($templateId, 'Rolled back to version ' . $version['version']);
        
        // Log audit trail
        layout_audit_log('rollback', 'element_template', $templateId, ['version_id' => $versionId, 'version' => $version['version']]);
    }
    
    return $result;
}

/**
 * Compare two versions
 * @param int $versionId1 First version ID
 * @param int $versionId2 Second version ID
 * @return array Comparison data
 */
function layout_element_template_compare_versions($versionId1, $versionId2) {
    $version1 = layout_element_template_get_version($versionId1);
    $version2 = layout_element_template_get_version($versionId2);
    
    if (!$version1 || !$version2) {
        return ['success' => false, 'error' => 'One or both versions not found'];
    }
    
    $data1 = $version1['template_data'];
    $data2 = $version2['template_data'];
    
    $differences = [];
    
    // Compare each field
    $fields = ['name', 'description', 'element_type', 'category', 'html', 'css', 'js'];
    foreach ($fields as $field) {
        if (($data1[$field] ?? '') !== ($data2[$field] ?? '')) {
            $differences[$field] = [
                'old' => $data1[$field] ?? '',
                'new' => $data2[$field] ?? ''
            ];
        }
    }
    
    // Compare JSON fields
    $jsonFields = ['custom_code', 'animations', 'properties', 'variants', 'tags', 'accessibility_data'];
    foreach ($jsonFields as $field) {
        $json1 = json_encode($data1[$field] ?? []);
        $json2 = json_encode($data2[$field] ?? []);
        if ($json1 !== $json2) {
            $differences[$field] = [
                'old' => $data1[$field] ?? [],
                'new' => $data2[$field] ?? []
            ];
        }
    }
    
    return [
        'success' => true,
        'version1' => $version1,
        'version2' => $version2,
        'differences' => $differences
    ];
}

