<?php
/**
 * Component Manager - Registry Functions
 * Component registry management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Register a component (manual registration only)
 * @param string $componentName Component name
 * @param string $version Component version
 * @param string $path Component path
 * @param array $dependencies Component dependencies
 * @param array $requirements Component requirements
 * @param string $status Component status
 * @param string $statusCategory Status category (basic, detailed, custom)
 * @return array Registration result
 */
function component_manager_register_component($componentName, $version, $path, $dependencies = [], $requirements = [], $status = 'active', $statusCategory = 'basic') {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Sanitize component name
    $componentName = component_manager_sanitize_component_name($componentName);
    if (!component_manager_validate_component_name($componentName)) {
        return ['success' => false, 'error' => 'Invalid component name'];
    }
    
    try {
        $tableName = component_manager_get_table_name('registry');
        $dependenciesJson = json_encode($dependencies);
        $requirementsJson = json_encode($requirements);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (component_name, current_version, installed_version, status, status_category, component_path, dependencies, requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE current_version = VALUES(current_version), installed_version = VALUES(installed_version), status = VALUES(status), status_category = VALUES(status_category), component_path = VALUES(component_path), dependencies = VALUES(dependencies), requirements = VALUES(requirements), updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("ssssssss", $componentName, $version, $version, $status, $statusCategory, $path, $dependenciesJson, $requirementsJson);
        $result = $stmt->execute();
        $stmt->close();
        
        return ['success' => $result];
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error registering component: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get component from registry
 * @param string $componentName Component name
 * @return array|null Component data or null if not found
 */
function component_manager_get_component($componentName) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $componentName = component_manager_sanitize_component_name($componentName);
    
    try {
        $tableName = component_manager_get_table_name('registry');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE component_name = ?");
        $stmt->bind_param("s", $componentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            // Decode JSON fields
            if (!empty($row['dependencies'])) {
                $row['dependencies'] = json_decode($row['dependencies'], true) ?: [];
            }
            if (!empty($row['requirements'])) {
                $row['requirements'] = json_decode($row['requirements'], true) ?: [];
            }
            if (!empty($row['dependencies_warnings'])) {
                $row['dependencies_warnings'] = json_decode($row['dependencies_warnings'], true) ?: [];
            }
            if (!empty($row['performance_metrics'])) {
                $row['performance_metrics'] = json_decode($row['performance_metrics'], true) ?: [];
            }
            if (!empty($row['security_scan_results'])) {
                $row['security_scan_results'] = json_decode($row['security_scan_results'], true) ?: [];
            }
            if (!empty($row['custom_status_data'])) {
                $row['custom_status_data'] = json_decode($row['custom_status_data'], true) ?: [];
            }
        }
        
        return $row;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting component: " . $e->getMessage());
        return null;
    }
}

/**
 * List all registered components
 * @param array $filters Filters (status, health_status, etc.)
 * @return array Array of components
 */
function component_manager_list_components($filters = []) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = component_manager_get_table_name('registry');
        $where = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['health_status'])) {
            $where[] = "health_status = ?";
            $params[] = $filters['health_status'];
            $types .= 's';
        }
        
        if (!empty($filters['dependencies_status'])) {
            $where[] = "dependencies_status = ?";
            $params[] = $filters['dependencies_status'];
            $types .= 's';
        }
        
        $sql = "SELECT * FROM {$tableName}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY component_name ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $components = [];
        
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields
            if (!empty($row['dependencies'])) {
                $row['dependencies'] = json_decode($row['dependencies'], true) ?: [];
            }
            if (!empty($row['requirements'])) {
                $row['requirements'] = json_decode($row['requirements'], true) ?: [];
            }
            $components[] = $row;
        }
        
        $stmt->close();
        return $components;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error listing components: " . $e->getMessage());
        return [];
    }
}

/**
 * Update component status
 * @param string $componentName Component name
 * @param string $status Status
 * @param string|null $statusCategory Status category
 * @param string|null $healthStatus Health status
 * @param string|null $healthMessage Health message
 * @param array|null $customStatusData Custom status data
 * @return bool Success status
 */
function component_manager_update_status($componentName, $status, $statusCategory = null, $healthStatus = null, $healthMessage = null, $customStatusData = null) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $componentName = component_manager_sanitize_component_name($componentName);
    
    try {
        $tableName = component_manager_get_table_name('registry');
        $updates = ["status = ?"];
        $params = [$status];
        $types = 's';
        
        if ($statusCategory !== null) {
            $updates[] = "status_category = ?";
            $params[] = $statusCategory;
            $types .= 's';
        }
        
        if ($healthStatus !== null) {
            $updates[] = "health_status = ?";
            $updates[] = "health_last_checked_at = CURRENT_TIMESTAMP";
            $params[] = $healthStatus;
            $types .= 's';
        }
        
        if ($healthMessage !== null) {
            $updates[] = "health_message = ?";
            $params[] = $healthMessage;
            $types .= 's';
        }
        
        if ($customStatusData !== null) {
            $updates[] = "custom_status_data = ?";
            $params[] = json_encode($customStatusData);
            $types .= 's';
        }
        
        $sql = "UPDATE {$tableName} SET " . implode(", ", $updates) . " WHERE component_name = ?";
        $params[] = $componentName;
        $types .= 's';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error updating status: " . $e->getMessage());
        return false;
    }
}

/**
 * Manual component registration (no auto-scanning)
 * @param string $componentName Component name
 * @param string $componentPath Component path
 * @param array $options Registration options
 * @return array Registration result
 */
function component_manager_register_manual($componentName, $componentPath, $options = []) {
    // Validate component structure
    $validation = component_manager_validate_component($componentName, $componentPath);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => 'Component validation failed', 'details' => $validation['errors']];
    }
    
    // Get version from VERSION file
    $version = component_manager_get_component_version_file($componentName, $componentPath);
    if ($version === null) {
        return ['success' => false, 'error' => 'VERSION file not found'];
    }
    
    // Extract metadata if requested
    $extractionLevel = $options['metadata_extraction_level'] ?? 'standard';
    $metadata = component_manager_extract_metadata($componentName, $extractionLevel);
    
    // Register component
    $dependencies = $metadata['dependencies'] ?? [];
    $requirements = $metadata['requirements'] ?? [];
    $status = $options['status'] ?? 'active';
    $statusCategory = $options['status_category'] ?? 'basic';
    
    return component_manager_register_component($componentName, $version, $componentPath, $dependencies, $requirements, $status, $statusCategory);
}

/**
 * Validate component structure
 * @param string $componentName Component name
 * @param string $componentPath Component path
 * @return array Validation result
 */
function component_manager_validate_component($componentName, $componentPath) {
    $errors = [];
    $warnings = [];
    
    // Check if path exists
    if (!is_dir($componentPath)) {
        $errors[] = "Component path does not exist: {$componentPath}";
        return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
    }
    
    // Check for required files
    $requiredFiles = ['VERSION', 'README.md'];
    foreach ($requiredFiles as $file) {
        if (!file_exists($componentPath . '/' . $file)) {
            $warnings[] = "Recommended file not found: {$file}";
        }
    }
    
    // Check for install.php (required for orchestrate mode)
    if (!file_exists($componentPath . '/install.php')) {
        $warnings[] = "install.php not found - component can only be tracked, not orchestrated";
    }
    
    // Check for config.example.php
    if (!file_exists($componentPath . '/config.example.php')) {
        $warnings[] = "config.example.php not found";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Extract component metadata
 * @param string $componentName Component name
 * @param string $extractionLevel Extraction level (basic, standard, comprehensive)
 * @return array Extracted metadata
 */
function component_manager_extract_metadata($componentName, $extractionLevel = 'standard') {
    $metadata = [
        'name' => $componentName,
        'version' => null,
        'author' => null,
        'description' => null,
        'dependencies' => [],
        'requirements' => []
    ];
    
    $componentPath = __DIR__ . '/../../' . $componentName;
    if (!is_dir($componentPath)) {
        return $metadata;
    }
    
    // Basic: Get version
    $version = component_manager_get_component_version_file($componentName, $componentPath);
    $metadata['version'] = $version;
    
    if ($extractionLevel === 'basic') {
        return $metadata;
    }
    
    // Standard: Extract from README.md
    $readmeFile = $componentPath . '/README.md';
    if (file_exists($readmeFile)) {
        $readme = file_get_contents($readmeFile);
        
        // Try to extract author
        if (preg_match('/Author[:\s]+([^\n]+)/i', $readme, $matches)) {
            $metadata['author'] = trim($matches[1]);
        }
        
        // Try to extract description (first paragraph)
        if (preg_match('/^#\s+.*?\n\n(.+?)(?:\n\n|$)/s', $readme, $matches)) {
            $metadata['description'] = trim($matches[1]);
        }
    }
    
    if ($extractionLevel === 'standard') {
        return $metadata;
    }
    
    // Comprehensive: Parse all files for dependencies, requirements, etc.
    // This is a simplified version - full implementation would parse more files
    if (file_exists($componentPath . '/composer.json')) {
        $composer = json_decode(file_get_contents($componentPath . '/composer.json'), true);
        if (isset($composer['require'])) {
            $metadata['requirements']['php'] = $composer['require']['php'] ?? null;
        }
    }
    
    return $metadata;
}

/**
 * Get installation preview (what will be done)
 * @param string $componentName Component name
 * @param array $options Installation options
 * @return array Installation preview
 */
function component_manager_get_installation_preview($componentName, $options = []) {
    $componentPath = __DIR__ . '/../../' . $componentName;
    
    if (!is_dir($componentPath)) {
        return ['success' => false, 'error' => 'Component not found'];
    }
    
    $preview = [
        'component_name' => $componentName,
        'component_path' => $componentPath,
        'version' => component_manager_get_component_version_file($componentName, $componentPath),
        'steps' => [],
        'estimated_duration' => '2-5 minutes',
        'resource_requirements' => [],
        'potential_issues' => []
    ];
    
    // Build step list
    $preview['steps'] = [
        ['number' => 1, 'name' => 'Component Validation', 'type' => 'validation', 'description' => 'Validate component structure and files'],
        ['number' => 2, 'name' => 'Dependency Check', 'type' => 'dependency_check', 'description' => 'Check and resolve component dependencies'],
        ['number' => 3, 'name' => 'Pre-installation Backup', 'type' => 'backup', 'description' => 'Create backup before installation (if enabled)'],
        ['number' => 4, 'name' => 'Database Schema Creation', 'type' => 'database', 'description' => 'Create component database tables'],
        ['number' => 5, 'name' => 'File Operations', 'type' => 'file', 'description' => 'Copy/verify component files'],
        ['number' => 6, 'name' => 'Configuration Generation', 'type' => 'config', 'description' => 'Generate component config.php file'],
        ['number' => 7, 'name' => 'Migration Execution', 'type' => 'migration', 'description' => 'Run component migration scripts'],
        ['number' => 8, 'name' => 'Post-installation Verification', 'type' => 'verification', 'description' => 'Verify installation success'],
        ['number' => 9, 'name' => 'Component Registration', 'type' => 'registration', 'description' => 'Register component in registry'],
    ];
    
    // Check for potential issues
    $validation = component_manager_validate_component($componentName, $componentPath);
    if (!empty($validation['errors'])) {
        $preview['potential_issues'] = array_merge($preview['potential_issues'], $validation['errors']);
    }
    if (!empty($validation['warnings'])) {
        $preview['potential_issues'] = array_merge($preview['potential_issues'], $validation['warnings']);
    }
    
    return $preview;
}

/**
 * Install component (orchestrate mode)
 * @param string $componentName Component name
 * @param array $options Installation options
 * @return array Installation result
 */
function component_manager_install_component($componentName, $options = []) {
    // Implementation: Run component's install.php if in orchestrate mode
    $componentPath = $options['component_path'] ?? __DIR__ . '/../../' . $componentName;
    
    if (!is_dir($componentPath)) {
        return ['success' => false, 'error' => 'Component path does not exist'];
    }
    
    $installFile = $componentPath . '/install.php';
    if (!file_exists($installFile)) {
        return ['success' => false, 'error' => 'install.php not found'];
    }
    
    // TODO: Execute install.php and track progress
    // This would involve running the component's installer and tracking steps
    return ['success' => false, 'error' => 'Installation orchestration not yet fully implemented'];
}

/**
 * Install components in dependency order
 * @param array $componentNames Component names
 * @param array $options Options
 * @return array Installation results
 */
function component_manager_install_components_ordered($componentNames, $options = []) {
    // Get installation order
    $orderResult = component_manager_get_installation_order($componentNames);
    if (!$orderResult['success']) {
        return ['success' => false, 'error' => $orderResult['error']];
    }
    
    $results = [];
    foreach ($orderResult['ordered'] as $name) {
        $results[$name] = component_manager_install_component($name, $options);
    }
    
    return ['success' => true, 'results' => $results];
}

/**
 * Track existing component (track mode)
 * @param string $componentName Component name
 * @param string $componentPath Component path
 * @return array Tracking result
 */
function component_manager_track_component($componentName, $componentPath) {
    // Just register without running installer
    return component_manager_register_manual($componentName, $componentPath, ['status' => 'active']);
}

/**
 * Get installation report
 * @param int $installationId Installation ID
 * @return array|null Installation report or null
 */
function component_manager_get_installation_report($installationId) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = component_manager_get_table_name('installation_history');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $installationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            if (!empty($row['installation_preview'])) {
                $row['installation_preview'] = json_decode($row['installation_preview'], true) ?: [];
            }
            if (!empty($row['troubleshooting_guidance'])) {
                $row['troubleshooting_guidance'] = json_decode($row['troubleshooting_guidance'], true) ?: [];
            }
        }
        
        return $row;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting installation report: " . $e->getMessage());
        return null;
    }
}

/**
 * Get installation step details
 * @param int $installationId Installation ID
 * @return array Installation steps
 */
function component_manager_get_installation_steps($installationId) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = component_manager_get_table_name('installation_steps');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE installation_id = ? ORDER BY step_number ASC");
        $stmt->bind_param("i", $installationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $steps = [];
        
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['troubleshooting_steps'])) {
                $row['troubleshooting_steps'] = json_decode($row['troubleshooting_steps'], true) ?: [];
            }
            if (!empty($row['step_data'])) {
                $row['step_data'] = json_decode($row['step_data'], true) ?: [];
            }
            $steps[] = $row;
        }
        
        $stmt->close();
        return $steps;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting installation steps: " . $e->getMessage());
        return [];
    }
}

/**
 * Get troubleshooting guidance for installation errors
 * @param string $componentName Component name
 * @param string $errorType Error type
 * @param array $errorDetails Error details
 * @return array Troubleshooting guidance
 */
function component_manager_get_troubleshooting_guidance($componentName, $errorType, $errorDetails = []) {
    // Basic troubleshooting guidance
    $guidance = [
        'error_type' => $errorType,
        'component' => $componentName,
        'steps' => [],
        'common_causes' => [],
        'solutions' => []
    ];
    
    // Add specific guidance based on error type
    switch ($errorType) {
        case 'database_error':
            $guidance['steps'][] = 'Check database connection settings';
            $guidance['steps'][] = 'Verify database user has CREATE TABLE permissions';
            $guidance['common_causes'][] = 'Incorrect database credentials';
            $guidance['common_causes'][] = 'Database user lacks permissions';
            break;
        case 'file_permission_error':
            $guidance['steps'][] = 'Check file and directory permissions';
            $guidance['steps'][] = 'Ensure component directory is writable';
            $guidance['common_causes'][] = 'Insufficient file permissions';
            break;
        case 'dependency_error':
            $guidance['steps'][] = 'Install missing dependencies first';
            $guidance['steps'][] = 'Check dependency version requirements';
            $guidance['common_causes'][] = 'Missing required components';
            $guidance['common_causes'][] = 'Version mismatch';
            break;
    }
    
    return $guidance;
}

