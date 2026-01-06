<?php
/**
 * Layout Component - Export/Import Functions
 * Export and import templates and design systems
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/element_templates.php';
require_once __DIR__ . '/design_systems.php';
require_once __DIR__ . '/thumbnail_generator.php';

/**
 * Export element template
 * @param int $templateId Template ID
 * @param bool $includeDependencies Whether to include dependencies
 * @return array Export data
 */
function layout_export_element_template($templateId, $includeDependencies = true) {
    $template = layout_element_template_get($templateId);
    if (!$template) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    $export = [
        'format_version' => '1.0',
        'export_type' => 'element_template',
        'exported_at' => date('Y-m-d H:i:s'),
        'template' => $template
    ];
    
    if ($includeDependencies) {
        $export['dependencies'] = [
            'css_variables' => layout_get_css_variables(),
            'fonts' => layout_get_fonts(),
            'required_components' => []
        ];
    }
    
    // Generate thumbnail if not exists
    $thumbnailPath = layout_get_thumbnail_path($templateId, 'element_template');
    if (!$thumbnailPath) {
        $thumbnailResult = layout_generate_thumbnail($templateId, 'element_template');
        if ($thumbnailResult['success']) {
            $thumbnailPath = $thumbnailResult['path'];
        }
    }
    
    $export['preview_images'] = [
        'thumbnail' => $thumbnailPath
    ];
    
    // Add metadata
    $export['metadata'] = [
        'version' => '1.0.0',
        'author' => 'System',
        'description' => $template['description'] ?? '',
        'tags' => $template['tags'] ?? [],
        'compatibility' => [
            'layout_component_version' => '3.0.0',
            'php_version' => '7.4+'
        ]
    ];
    
    return ['success' => true, 'data' => $export];
}

/**
 * Export design system
 * @param int $designSystemId Design system ID
 * @param bool $includeDependencies Whether to include dependencies
 * @return array Export data
 */
function layout_export_design_system($designSystemId, $includeDependencies = true) {
    $designSystem = layout_design_system_inherit($designSystemId);
    if (!$designSystem) {
        return ['success' => false, 'error' => 'Design system not found'];
    }
    
    // Get all element templates in this design system
    $elementTemplates = [];
    foreach ($designSystem['element_templates'] ?? [] as $element) {
        $template = layout_element_template_get($element['element_template_id']);
        if ($template) {
            $elementTemplates[] = $template;
        }
    }
    
    // Get parent design system information if exists
    $parentInfo = null;
    $parentDesignSystem = null;
    if (!empty($designSystem['parent_design_system_id'])) {
        $parent = layout_design_system_get($designSystem['parent_design_system_id'], false);
        if ($parent) {
            $parentInfo = [
                'id' => $parent['id'],
                'name' => $parent['name'],
                'version' => $parent['version'] ?? '1.0.0'
            ];
            
            // If including dependencies, include parent design system in export
            if ($includeDependencies) {
                $parentExport = layout_export_design_system($parent['id'], false);
                if ($parentExport['success']) {
                    $parentDesignSystem = $parentExport['data']['design_system'];
                }
            }
        }
    }
    
    $export = [
        'format_version' => '1.0',
        'export_type' => 'design_system',
        'exported_at' => date('Y-m-d H:i:s'),
        'design_system' => $designSystem,
        'element_templates' => $elementTemplates,
        'parent_info' => $parentInfo
    ];
    
    if ($includeDependencies) {
        $export['dependencies'] = [
            'css_variables' => layout_get_css_variables(),
            'fonts' => layout_get_fonts(),
            'required_components' => []
        ];
        
        // Add parent design system if included
        if ($parentDesignSystem) {
            $export['parent_design_system'] = $parentDesignSystem;
        }
    }
    
    // Generate thumbnail if not exists
    $thumbnailPath = layout_get_thumbnail_path($designSystemId, 'design_system');
    if (!$thumbnailPath) {
        $thumbnailResult = layout_generate_thumbnail($designSystemId, 'design_system');
        if ($thumbnailResult['success']) {
            $thumbnailPath = $thumbnailResult['path'];
        }
    }
    
    // Generate thumbnails for element templates
    $screenshots = [];
    foreach ($elementTemplates as $template) {
        $templateThumb = layout_get_thumbnail_path($template['id'], 'element_template');
        if (!$templateThumb) {
            $thumbResult = layout_generate_thumbnail($template['id'], 'element_template');
            if ($thumbResult['success']) {
                $templateThumb = $thumbResult['path'];
            }
        }
        if ($templateThumb) {
            $screenshots[] = $templateThumb;
        }
    }
    
    $export['preview_images'] = [
        'thumbnail' => $thumbnailPath,
        'screenshots' => $screenshots
    ];
    
    // Add metadata
    $export['metadata'] = [
        'version' => $designSystem['version'],
        'author' => 'System',
        'description' => $designSystem['description'] ?? '',
        'tags' => $designSystem['tags'] ?? [],
        'compatibility' => [
            'layout_component_version' => '3.0.0',
            'php_version' => '7.4+'
        ],
        'marketplace_ready' => $designSystem['is_published'] ? true : false
    ];
    
    return ['success' => true, 'data' => $export];
}

/**
 * Import element template
 * @param array $importData Import data
 * @return array Result with template ID
 */
function layout_import_element_template($importData) {
    if (!isset($importData['template'])) {
        return ['success' => false, 'error' => 'Invalid import data: template missing'];
    }
    
    $template = $importData['template'];
    
    // Check for conflicts
    $existing = layout_element_template_get_all(['search' => $template['name']]);
    $conflict = false;
    foreach ($existing as $existingTemplate) {
        if ($existingTemplate['name'] === $template['name'] && 
            $existingTemplate['element_type'] === $template['element_type']) {
            $conflict = true;
            break;
        }
    }
    
    if ($conflict && !isset($importData['overwrite'])) {
        return ['success' => false, 'error' => 'Template with same name and type already exists', 'conflict' => true];
    }
    
    // Prepare template data
    $templateData = [
        'name' => $template['name'],
        'description' => $template['description'] ?? '',
        'element_type' => $template['element_type'],
        'category' => $template['category'] ?? '',
        'html' => $template['html'],
        'css' => $template['css'] ?? '',
        'js' => $template['js'] ?? '',
        'custom_code' => $template['custom_code'] ?? [],
        'animations' => $template['animations'] ?? [],
        'properties' => $template['properties'] ?? [],
        'variants' => $template['variants'] ?? [],
        'tags' => $template['tags'] ?? [],
        'accessibility_data' => $template['accessibility_data'] ?? [],
        'is_published' => 0 // Import as draft
    ];
    
    // If overwriting, delete existing first
    if ($conflict && isset($importData['overwrite']) && $importData['overwrite']) {
        foreach ($existing as $existingTemplate) {
            if ($existingTemplate['name'] === $template['name']) {
                layout_element_template_delete($existingTemplate['id']);
                break;
            }
        }
    }
    
    $result = layout_element_template_create($templateData);
    
    if ($result['success'] && isset($importData['dependencies'])) {
        // Import dependencies if provided
        layout_import_dependencies($importData['dependencies']);
    }
    
    return $result;
}

/**
 * Resolve parent design system from import data
 * @param array|null $parentInfo Parent design system info from export
 * @param array $existingDesignSystems Existing design systems to search
 * @return int|null Matched design system ID or null
 */
function layout_resolve_parent_design_system($parentInfo, $existingDesignSystems = null) {
    if (!$parentInfo || empty($parentInfo['name'])) {
        return null;
    }
    
    // Get existing design systems if not provided
    if ($existingDesignSystems === null) {
        $existingDesignSystems = layout_design_system_get_all();
    }
    
    $parentName = trim($parentInfo['name']);
    $parentVersion = $parentInfo['version'] ?? null;
    
    // Try exact name match first
    foreach ($existingDesignSystems as $ds) {
        if (strcasecmp(trim($ds['name']), $parentName) === 0) {
            // If version specified, try to match version too
            if ($parentVersion && isset($ds['version'])) {
                if (strcasecmp(trim($ds['version']), trim($parentVersion)) === 0) {
                    return (int)$ds['id'];
                }
            } else {
                // No version specified or no version in existing, return first match
                return (int)$ds['id'];
            }
        }
    }
    
    return null;
}

/**
 * Preview import data and show resolution status
 * @param array $importData Import data
 * @return array Preview data with resolution status
 */
function layout_preview_import($importData) {
    $preview = [
        'export_type' => $importData['export_type'] ?? 'unknown',
        'design_system' => null,
        'parent_resolution' => null,
        'conflicts' => [],
        'warnings' => []
    ];
    
    if ($importData['export_type'] === 'design_system' && isset($importData['design_system'])) {
        $designSystem = $importData['design_system'];
        $preview['design_system'] = [
            'name' => $designSystem['name'],
            'version' => $designSystem['version'] ?? '1.0.0',
            'description' => $designSystem['description'] ?? ''
        ];
        
        // Check for existing design system with same name
        $existing = layout_design_system_get_all(['search' => $designSystem['name']]);
        foreach ($existing as $existingDS) {
            if ($existingDS['name'] === $designSystem['name']) {
                $preview['conflicts'][] = [
                    'type' => 'name_conflict',
                    'message' => 'Design system with same name already exists',
                    'existing_id' => $existingDS['id'],
                    'existing_name' => $existingDS['name']
                ];
            }
        }
        
        // Check parent resolution
        if (isset($importData['parent_info']) && $importData['parent_info']) {
            $parentInfo = $importData['parent_info'];
            $existingDesignSystems = layout_design_system_get_all();
            $resolvedParentId = layout_resolve_parent_design_system($parentInfo, $existingDesignSystems);
            
            $preview['parent_resolution'] = [
                'parent_name' => $parentInfo['name'],
                'parent_version' => $parentInfo['version'] ?? null,
                'resolved' => $resolvedParentId !== null,
                'resolved_id' => $resolvedParentId
            ];
            
            if ($resolvedParentId === null) {
                $preview['warnings'][] = [
                    'type' => 'parent_not_found',
                    'message' => 'Parent design system "' . $parentInfo['name'] . '" not found. Parent relationship will be lost.',
                    'suggestion' => 'Import parent design system first or skip parent relationship'
                ];
            }
        }
    }
    
    return $preview;
}

/**
 * Import design system
 * @param array $importData Import data
 * @return array Result with design system ID
 */
function layout_import_design_system($importData) {
    if (!isset($importData['design_system'])) {
        return ['success' => false, 'error' => 'Invalid import data: design_system missing'];
    }
    
    // First import element templates
    $elementTemplateMap = [];
    if (isset($importData['element_templates'])) {
        foreach ($importData['element_templates'] as $template) {
            $templateImport = layout_import_element_template([
                'template' => $template,
                'overwrite' => $importData['overwrite'] ?? false
            ]);
            if ($templateImport['success']) {
                $elementTemplateMap[$template['id']] = $templateImport['id'];
            }
        }
    }
    
    // Import design system
    $designSystem = $importData['design_system'];
    
    // Check for conflicts
    $existing = layout_design_system_get_all(['search' => $designSystem['name']]);
    $conflict = false;
    foreach ($existing as $existingDS) {
        if ($existingDS['name'] === $designSystem['name']) {
            $conflict = true;
            break;
        }
    }
    
    if ($conflict && !isset($importData['overwrite'])) {
        return ['success' => false, 'error' => 'Design system with same name already exists', 'conflict' => true];
    }
    
    // Resolve parent design system if parent info exists
    $resolvedParentId = null;
    if (isset($importData['parent_info']) && $importData['parent_info']) {
        $parentInfo = $importData['parent_info'];
        
        // First, check if parent design system is included in import data
        if (isset($importData['parent_design_system']) && $importData['parent_design_system']) {
            // Check if parent already exists
            $existingDesignSystems = layout_design_system_get_all();
            $existingParentId = layout_resolve_parent_design_system($parentInfo, $existingDesignSystems);
            
            if ($existingParentId) {
                // Parent already exists, use it
                $resolvedParentId = $existingParentId;
            } else {
                // Import parent first if not already imported
                $parentDS = $importData['parent_design_system'];
                $parentElementTemplates = [];
                
                // Get element templates for parent if included in export
                if (isset($importData['element_templates'])) {
                    // Find templates that belong to parent (this is a simplified approach)
                    // In a full implementation, export would mark which templates belong to parent
                    $parentElementTemplates = $importData['element_templates'];
                }
                
                $parentImportData = [
                    'design_system' => $parentDS,
                    'element_templates' => $parentElementTemplates,
                    'overwrite' => $importData['overwrite'] ?? false,
                    'skip_parent' => true // Don't try to resolve parent's parent recursively
                ];
                $parentImportResult = layout_import_design_system($parentImportData);
                if ($parentImportResult['success']) {
                    $resolvedParentId = $parentImportResult['id'];
                }
            }
        } else {
            // Try to resolve from existing design systems
            $existingDesignSystems = layout_design_system_get_all();
            $resolvedParentId = layout_resolve_parent_design_system($parentInfo, $existingDesignSystems);
        }
        
        // If user wants to skip parent relationship, allow it
        if ($resolvedParentId === null && isset($importData['skip_parent']) && $importData['skip_parent']) {
            $resolvedParentId = null; // Explicitly skip
        }
    }
    
    // Prepare design system data
    $designSystemData = [
        'name' => $designSystem['name'],
        'description' => $designSystem['description'] ?? '',
        'parent_design_system_id' => $resolvedParentId,
        'theme_data' => $designSystem['theme_data'] ?? [],
        'performance_settings' => $designSystem['performance_settings'] ?? [],
        'accessibility_settings' => $designSystem['accessibility_settings'] ?? [],
        'version' => $designSystem['version'] ?? '1.0.0',
        'tags' => $designSystem['tags'] ?? [],
        'category' => $designSystem['category'] ?? '',
        'element_templates' => array_values($elementTemplateMap),
        'is_published' => 0 // Import as draft
    ];
    
    // If overwriting, delete existing first
    if ($conflict && isset($importData['overwrite']) && $importData['overwrite']) {
        foreach ($existing as $existingDS) {
            if ($existingDS['name'] === $designSystem['name']) {
                layout_design_system_delete($existingDS['id']);
                break;
            }
        }
    }
    
    $result = layout_design_system_create($designSystemData);
    
    if ($result['success'] && isset($importData['dependencies'])) {
        // Import dependencies if provided
        layout_import_dependencies($importData['dependencies']);
    }
    
    return $result;
}

/**
 * Get CSS variables from theme
 * @return array CSS variables
 */
function layout_get_css_variables() {
    // This would extract CSS variables from theme component
    // For now, return empty array
    return [];
}

/**
 * Get fonts from theme
 * @return array Fonts
 */
function layout_get_fonts() {
    // This would extract fonts from theme component
    // For now, return empty array
    return [];
}

/**
 * Import dependencies
 * @param array $dependencies Dependencies data
 * @return bool Success
 */
function layout_import_dependencies($dependencies) {
    // Import CSS variables, fonts, etc.
    // This would integrate with theme component
    return true;
}

/**
 * Save export to file
 * @param array $exportData Export data
 * @param string $filename Filename
 * @return array Result with file path
 */
function layout_save_export_file($exportData, $filename) {
    $exportDir = __DIR__ . '/../../exports/';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0755, true);
    }
    
    $filePath = $exportDir . $filename;
    $json = json_encode($exportData, JSON_PRETTY_PRINT);
    
    if (file_put_contents($filePath, $json)) {
        // Save to database
        $conn = layout_get_db_connection();
        if ($conn) {
            $tableName = layout_get_table_name('template_exports');
            $exportType = $exportData['export_type'];
            $exportDataJson = json_encode($exportData);
            $fileSize = filesize($filePath);
            $createdBy = $_SESSION['user_id'] ?? null;
            
            $stmt = $conn->prepare("INSERT INTO {$tableName} (export_name, export_type, export_data, file_path, file_size, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssssii", $filename, $exportType, $exportDataJson, $filePath, $fileSize, $createdBy);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        return ['success' => true, 'file_path' => $filePath];
    }
    
    return ['success' => false, 'error' => 'Failed to save export file'];
}

/**
 * Load import from file
 * @param string $filePath File path
 * @return array Import data
 */
function layout_load_import_file($filePath) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'error' => 'File not found'];
    }
    
    $json = file_get_contents($filePath);
    $importData = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()];
    }
    
    return ['success' => true, 'data' => $importData];
}

