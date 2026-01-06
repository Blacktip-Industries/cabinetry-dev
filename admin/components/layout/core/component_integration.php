<?php
/**
 * Layout Component - Component Integration Functions
 * Comprehensive component dependency and template management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/component_detector.php';
require_once __DIR__ . '/functions.php';

/**
 * Create a component dependency
 * @param int $layoutId Layout ID
 * @param string $componentName Component name
 * @param bool $isRequired Whether component is required
 * @return array Result with success status and dependency ID
 */
function layout_component_dependency_create($layoutId, $componentName, $isRequired = true) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('component_dependencies');
        
        // Check if dependency already exists
        $checkStmt = $conn->prepare("SELECT id FROM {$tableName} WHERE layout_id = ? AND component_name = ?");
        $checkStmt->bind_param("is", $layoutId, $componentName);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            $checkStmt->close();
            return ['success' => true, 'id' => $existing['id'], 'existing' => true];
        }
        $checkStmt->close();
        
        // Insert new dependency
        $isRequiredInt = $isRequired ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO {$tableName} (layout_id, component_name, is_required) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $layoutId, $componentName, $isRequiredInt);
        
        if ($stmt->execute()) {
            $dependencyId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $dependencyId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error creating dependency: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get component dependency by ID
 * @param int $dependencyId Dependency ID
 * @return array|null Dependency data or null if not found
 */
function layout_component_dependency_get($dependencyId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('component_dependencies');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $dependencyId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $dependency = $result->fetch_assoc();
            $stmt->close();
            $dependency['is_required'] = (bool)$dependency['is_required'];
            return $dependency;
        }
        
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error getting dependency: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all component dependencies for a layout
 * @param int $layoutId Layout ID
 * @return array Array of dependency data
 */
function layout_component_dependency_get_by_layout($layoutId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('component_dependencies');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE layout_id = ? ORDER BY component_name");
        $stmt->bind_param("i", $layoutId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $dependencies = [];
        while ($row = $result->fetch_assoc()) {
            $row['is_required'] = (bool)$row['is_required'];
            $dependencies[] = $row;
        }
        
        $stmt->close();
        return $dependencies;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error getting dependencies: " . $e->getMessage());
        return [];
    }
}

/**
 * Update component dependency
 * @param int $dependencyId Dependency ID
 * @param bool $isRequired Whether component is required
 * @return array Result with success status
 */
function layout_component_dependency_update($dependencyId, $isRequired) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('component_dependencies');
        $isRequiredInt = $isRequired ? 1 : 0;
        $stmt = $conn->prepare("UPDATE {$tableName} SET is_required = ? WHERE id = ?");
        $stmt->bind_param("ii", $isRequiredInt, $dependencyId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error updating dependency: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete component dependency
 * @param int $dependencyId Dependency ID
 * @return array Result with success status
 */
function layout_component_dependency_delete($dependencyId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('component_dependencies');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $dependencyId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error deleting dependency: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Check if all required components for a layout are installed
 * @param int $layoutId Layout ID
 * @return array Result with status and missing components
 */
function layout_component_dependency_check_all($layoutId) {
    $dependencies = layout_component_dependency_get_by_layout($layoutId);
    $missing = [];
    $installed = [];
    $optional = [];
    
    foreach ($dependencies as $dependency) {
        $componentName = $dependency['component_name'];
        $isInstalled = layout_is_component_installed($componentName);
        
        if ($isInstalled) {
            $installed[] = $componentName;
        } else {
            if ($dependency['is_required']) {
                $missing[] = $componentName;
            } else {
                $optional[] = $componentName;
            }
        }
    }
    
    return [
        'all_installed' => empty($missing),
        'missing_required' => $missing,
        'missing_optional' => $optional,
        'installed' => $installed,
        'total_required' => count(array_filter($dependencies, function($d) { return $d['is_required']; })),
        'total_optional' => count(array_filter($dependencies, function($d) { return !$d['is_required']; }))
    ];
}

/**
 * Get list of all installed components
 * @return array Array of component names and metadata
 */
function layout_component_get_installed() {
    $componentsDir = __DIR__ . '/../../';
    $installed = [];
    
    if (!is_dir($componentsDir)) {
        return $installed;
    }
    
    $dirs = scandir($componentsDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..' || $dir === 'layout') {
            continue;
        }
        
        $componentPath = $componentsDir . $dir;
        if (is_dir($componentPath) && layout_is_component_installed($dir)) {
            $metadata = layout_component_get_metadata($dir);
            $installed[] = array_merge([
                'name' => $dir,
                'installed' => true
            ], $metadata);
        }
    }
    
    return $installed;
}

/**
 * Get component version if available
 * @param string $componentName Component name
 * @return string|null Version string or null if not available
 */
function layout_component_get_version($componentName) {
    $componentPath = __DIR__ . '/../../' . $componentName;
    $versionFile = $componentPath . '/VERSION';
    
    if (file_exists($versionFile)) {
        $version = trim(file_get_contents($versionFile));
        return $version ?: null;
    }
    
    // Try to get from config.php
    $configFile = $componentPath . '/config.php';
    if (file_exists($configFile)) {
        $configContent = file_get_contents($configFile);
        if (preg_match('/version[\'"]?\s*[=:]\s*[\'"]([^\'"]+)[\'"]/', $configContent, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Get component metadata
 * @param string $componentName Component name
 * @return array Component metadata
 */
function layout_component_get_metadata($componentName) {
    $componentPath = __DIR__ . '/../../' . $componentName;
    $metadata = [
        'version' => layout_component_get_version($componentName),
        'description' => null,
        'capabilities' => []
    ];
    
    // Try to read README.md for description
    $readmeFile = $componentPath . '/README.md';
    if (file_exists($readmeFile)) {
        $readmeContent = file_get_contents($readmeFile);
        if (preg_match('/^#\s+.*?\n\n(.+?)(?:\n\n|$)/s', $readmeContent, $matches)) {
            $metadata['description'] = trim($matches[1]);
        }
    }
    
    // Check for common capabilities
    $capabilities = [];
    if (file_exists($componentPath . '/includes/header.php')) {
        $capabilities[] = 'header';
    }
    if (file_exists($componentPath . '/includes/sidebar.php') || file_exists($componentPath . '/includes/menu.php')) {
        $capabilities[] = 'menu';
    }
    if (file_exists($componentPath . '/includes/footer.php')) {
        $capabilities[] = 'footer';
    }
    if (file_exists($componentPath . '/admin')) {
        $capabilities[] = 'admin_interface';
    }
    if (file_exists($componentPath . '/api')) {
        $capabilities[] = 'api';
    }
    
    $metadata['capabilities'] = $capabilities;
    
    return $metadata;
}

/**
 * Validate component integration requirements
 * @param array $requirements Requirements array with component names and optional version constraints
 * @return array Validation result with status and issues
 */
function layout_component_validate_integration($requirements) {
    $issues = [];
    $warnings = [];
    
    foreach ($requirements as $componentName => $requirement) {
        $isInstalled = layout_is_component_installed($componentName);
        
        if (!$isInstalled) {
            $isRequired = is_array($requirement) ? ($requirement['required'] ?? true) : true;
            if ($isRequired) {
                $issues[] = [
                    'type' => 'missing_required',
                    'component' => $componentName,
                    'message' => "Required component '{$componentName}' is not installed"
                ];
            } else {
                $warnings[] = [
                    'type' => 'missing_optional',
                    'component' => $componentName,
                    'message' => "Optional component '{$componentName}' is not installed"
                ];
            }
            continue;
        }
        
        // Check version if specified
        if (is_array($requirement) && isset($requirement['version'])) {
            $requiredVersion = $requirement['version'];
            $installedVersion = layout_component_get_version($componentName);
            
            if ($installedVersion && !layout_check_version_compatibility($installedVersion, $requiredVersion)) {
                $warnings[] = [
                    'type' => 'version_mismatch',
                    'component' => $componentName,
                    'installed_version' => $installedVersion,
                    'required_version' => $requiredVersion,
                    'message' => "Component '{$componentName}' version '{$installedVersion}' may not be compatible with required version '{$requiredVersion}'"
                ];
            }
        }
    }
    
    return [
        'valid' => empty($issues),
        'issues' => $issues,
        'warnings' => $warnings
    ];
}

/**
 * Check version compatibility
 * @param string $installedVersion Installed version
 * @param string $requiredVersion Required version (can be like ">=1.0.0" or "1.0.0")
 * @return bool True if compatible
 */
function layout_check_version_compatibility($installedVersion, $requiredVersion) {
    // Simple version comparison - can be enhanced
    if (preg_match('/^([><=]+)\s*(.+)$/', $requiredVersion, $matches)) {
        $operator = $matches[1];
        $required = $matches[2];
    } else {
        $operator = '>=';
        $required = $requiredVersion;
    }
    
    return version_compare($installedVersion, $required, $operator);
}

/**
 * Get list of missing required components for a layout
 * @param int $layoutId Layout ID
 * @return array Array of missing component names
 */
function layout_component_get_missing_dependencies($layoutId) {
    $checkResult = layout_component_dependency_check_all($layoutId);
    return $checkResult['missing_required'];
}

/**
 * Create component template association
 * @param string $componentName Component name
 * @param int|null $elementTemplateId Element template ID
 * @param int|null $designSystemId Design system ID
 * @param array $templateData Template data
 * @return array Result with success status and template ID
 */
function layout_component_template_create($componentName, $elementTemplateId = null, $designSystemId = null, $templateData = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('component_templates');
        $templateDataJson = json_encode($templateData);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (component_name, element_template_id, design_system_id, template_data) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $componentName, $elementTemplateId, $designSystemId, $templateDataJson);
        
        if ($stmt->execute()) {
            $templateId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $templateId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error creating template: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get component template by ID
 * @param int $templateId Template ID
 * @return array|null Template data or null if not found
 */
function layout_component_template_get($templateId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('component_templates');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $template = $result->fetch_assoc();
            $stmt->close();
            $template['template_data'] = json_decode($template['template_data'], true) ?? [];
            return $template;
        }
        
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error getting template: " . $e->getMessage());
        return null;
    }
}

/**
 * Get component templates for a component
 * @param string $componentName Component name
 * @return array Array of template data
 */
function layout_component_template_get_by_component($componentName) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('component_templates');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE component_name = ? ORDER BY id DESC");
        $stmt->bind_param("s", $componentName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            $row['template_data'] = json_decode($row['template_data'], true) ?? [];
            $templates[] = $row;
        }
        
        $stmt->close();
        return $templates;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error getting templates: " . $e->getMessage());
        return [];
    }
}

/**
 * Update component template
 * @param int $templateId Template ID
 * @param int|null $elementTemplateId Element template ID
 * @param int|null $designSystemId Design system ID
 * @param array $templateData Template data
 * @return array Result with success status
 */
function layout_component_template_update($templateId, $elementTemplateId = null, $designSystemId = null, $templateData = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('component_templates');
        $templateDataJson = json_encode($templateData);
        
        $stmt = $conn->prepare("UPDATE {$tableName} SET element_template_id = ?, design_system_id = ?, template_data = ? WHERE id = ?");
        $stmt->bind_param("iisi", $elementTemplateId, $designSystemId, $templateDataJson, $templateId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error updating template: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete component template
 * @param int $templateId Template ID
 * @return array Result with success status
 */
function layout_component_template_delete($templateId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('component_templates');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $templateId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Component Integration: Error deleting template: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Apply template to component in layout
 * @param string $componentName Component name
 * @param int $templateId Template ID
 * @param array $params Additional parameters
 * @return array Result with success status
 */
function layout_component_template_apply($componentName, $templateId, $params = []) {
    $template = layout_component_template_get($templateId);
    if (!$template) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    if ($template['component_name'] !== $componentName) {
        return ['success' => false, 'error' => 'Template does not match component'];
    }
    
    // Apply element template if specified
    if ($template['element_template_id']) {
        require_once __DIR__ . '/element_templates.php';
        $elementTemplate = layout_element_template_get($template['element_template_id']);
        if ($elementTemplate) {
            // Template application logic would go here
            // This would integrate with the layout rendering system
        }
    }
    
    // Apply design system if specified
    if ($template['design_system_id']) {
        require_once __DIR__ . '/design_systems.php';
        $designSystem = layout_design_system_get($template['design_system_id']);
        if ($designSystem) {
            // Design system application logic would go here
        }
    }
    
    return ['success' => true, 'template' => $template];
}

/**
 * Validate all dependencies for a layout
 * @param int $layoutId Layout ID
 * @return array Validation result
 */
function layout_validate_layout_dependencies($layoutId) {
    $dependencies = layout_component_dependency_get_by_layout($layoutId);
    $issues = [];
    $warnings = [];
    
    foreach ($dependencies as $dependency) {
        $componentName = $dependency['component_name'];
        $isInstalled = layout_is_component_installed($componentName);
        
        if (!$isInstalled) {
            if ($dependency['is_required']) {
                $issues[] = [
                    'type' => 'missing_required',
                    'component' => $componentName,
                    'dependency_id' => $dependency['id'],
                    'message' => "Required component '{$componentName}' is not installed"
                ];
            } else {
                $warnings[] = [
                    'type' => 'missing_optional',
                    'component' => $componentName,
                    'dependency_id' => $dependency['id'],
                    'message' => "Optional component '{$componentName}' is not installed"
                ];
            }
        } else {
            // Check component version if metadata available
            $metadata = layout_component_get_metadata($componentName);
            if (isset($metadata['version'])) {
                // Could add version checking here if requirements are stored
            }
        }
    }
    
    return [
        'valid' => empty($issues),
        'issues' => $issues,
        'warnings' => $warnings,
        'total_dependencies' => count($dependencies),
        'installed_count' => count($dependencies) - count($issues) - count($warnings)
    ];
}

/**
 * Check component compatibility
 * @param string $componentName Component name
 * @param string $requiredVersion Required version
 * @return array Compatibility result
 */
function layout_check_component_compatibility($componentName, $requiredVersion = null) {
    if (!layout_is_component_installed($componentName)) {
        return [
            'compatible' => false,
            'installed' => false,
            'message' => "Component '{$componentName}' is not installed"
        ];
    }
    
    $installedVersion = layout_component_get_version($componentName);
    
    if ($requiredVersion && $installedVersion) {
        $compatible = layout_check_version_compatibility($installedVersion, $requiredVersion);
        return [
            'compatible' => $compatible,
            'installed' => true,
            'installed_version' => $installedVersion,
            'required_version' => $requiredVersion,
            'message' => $compatible 
                ? "Component '{$componentName}' version '{$installedVersion}' is compatible"
                : "Component '{$componentName}' version '{$installedVersion}' may not be compatible with required '{$requiredVersion}'"
        ];
    }
    
    return [
        'compatible' => true,
        'installed' => true,
        'installed_version' => $installedVersion,
        'message' => "Component '{$componentName}' is installed" . ($installedVersion ? " (version {$installedVersion})" : "")
    ];
}

/**
 * Get integration warnings
 * @param int|null $layoutId Layout ID (optional, if null checks all layouts)
 * @return array Array of warnings
 */
function layout_get_integration_warnings($layoutId = null) {
    $warnings = [];
    
    if ($layoutId) {
        $validation = layout_validate_layout_dependencies($layoutId);
        $warnings = array_merge($warnings, $validation['warnings']);
    } else {
        // Check all layouts
        require_once __DIR__ . '/layout_database.php';
        $layouts = layout_get_definitions([], 1000, 0); // Get up to 1000 layouts
        foreach ($layouts as $layout) {
            $validation = layout_validate_layout_dependencies($layout['id']);
            foreach ($validation['warnings'] as $warning) {
                $warning['layout_id'] = $layout['id'];
                $warning['layout_name'] = $layout['name'];
                $warnings[] = $warning;
            }
        }
    }
    
    return $warnings;
}

/**
 * Get integration errors
 * @param int|null $layoutId Layout ID (optional, if null checks all layouts)
 * @return array Array of errors
 */
function layout_get_integration_errors($layoutId = null) {
    $errors = [];
    
    if ($layoutId) {
        $validation = layout_validate_layout_dependencies($layoutId);
        $errors = array_merge($errors, $validation['issues']);
    } else {
        // Check all layouts
        require_once __DIR__ . '/layout_database.php';
        $layouts = layout_get_definitions([], 1000, 0); // Get up to 1000 layouts
        foreach ($layouts as $layout) {
            $validation = layout_validate_layout_dependencies($layout['id']);
            foreach ($validation['issues'] as $issue) {
                $issue['layout_id'] = $layout['id'];
                $issue['layout_name'] = $layout['name'];
                $errors[] = $issue;
            }
        }
    }
    
    return $errors;
}

