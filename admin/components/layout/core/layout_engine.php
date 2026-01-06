<?php
/**
 * Layout Component - Layout Engine
 * Parse, validate, and render layout definitions
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Parse layout JSON data
 * @param string|array $layoutData JSON string or array
 * @return array Parsed layout data or null on error
 */
function layout_parse_layout($layoutData) {
    if (is_string($layoutData)) {
        $decoded = json_decode($layoutData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Layout Engine: JSON decode error: " . json_last_error_msg());
            return null;
        }
        return $decoded;
    }
    
    if (is_array($layoutData)) {
        return $layoutData;
    }
    
    return null;
}

/**
 * Validate layout structure
 * @param array $layoutData Layout data
 * @param bool $strict Whether to use strict validation
 * @return array Validation result with 'valid' (bool) and 'errors' (array)
 */
function layout_validate_layout($layoutData, $strict = true) {
    $errors = [];
    
    if (!is_array($layoutData)) {
        return ['valid' => false, 'errors' => ['Layout data must be an array']];
    }
    
    // Validate root structure
    if (!isset($layoutData['type'])) {
        $errors[] = 'Missing required field: type';
    } elseif (!in_array($layoutData['type'], ['split', 'component'])) {
        $errors[] = 'Invalid type. Must be "split" or "component"';
    }
    
    // Validate split type
    if (isset($layoutData['type']) && $layoutData['type'] === 'split') {
        if (!isset($layoutData['direction'])) {
            $errors[] = 'Split type requires "direction" field';
        } elseif (!in_array($layoutData['direction'], ['vertical', 'horizontal'])) {
            $errors[] = 'Invalid direction. Must be "vertical" or "horizontal"';
        }
        
        if (!isset($layoutData['sections']) || !is_array($layoutData['sections'])) {
            $errors[] = 'Split type requires "sections" array';
        } elseif (empty($layoutData['sections'])) {
            $errors[] = 'Sections array cannot be empty';
        } else {
            // Validate each section
            foreach ($layoutData['sections'] as $index => $section) {
                $sectionErrors = layout_validate_section($section, $index);
                $errors = array_merge($errors, $sectionErrors);
            }
        }
    }
    
    // Validate component type
    if (isset($layoutData['type']) && $layoutData['type'] === 'component') {
        if (!isset($layoutData['component']) || empty($layoutData['component'])) {
            $errors[] = 'Component type requires "component" field';
        }
    }
    
    // Check nesting depth
    $depth = layout_calculate_nesting_depth($layoutData);
    $maxDepth = (int)layout_get_parameter('Layout', 'nesting_depth_warning_threshold', 10);
    if ($depth > $maxDepth) {
        $errors[] = "Nesting depth ($depth) exceeds recommended maximum ($maxDepth)";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'depth' => $depth
    ];
}

/**
 * Validate a section
 * @param array $section Section data
 * @param int $index Section index
 * @return array Array of error messages
 */
function layout_validate_section($section, $index = 0) {
    $errors = [];
    
    if (!is_array($section)) {
        return ["Section $index: Must be an array"];
    }
    
    // Required fields
    if (!isset($section['id'])) {
        $errors[] = "Section $index: Missing required field 'id'";
    }
    
    if (!isset($section['type'])) {
        $errors[] = "Section $index: Missing required field 'type'";
    } elseif (!in_array($section['type'], ['split', 'component'])) {
        $errors[] = "Section $index: Invalid type. Must be 'split' or 'component'";
    }
    
    // Validate dimensions
    if (isset($section['minWidth']) && isset($section['maxWidth'])) {
        $minW = layout_parse_dimension($section['minWidth']);
        $maxW = layout_parse_dimension($section['maxWidth']);
        if ($minW !== null && $maxW !== null && $minW > $maxW) {
            $errors[] = "Section $index: minWidth cannot be greater than maxWidth";
        }
    }
    
    if (isset($section['minHeight']) && isset($section['maxHeight'])) {
        $minH = layout_parse_dimension($section['minHeight']);
        $maxH = layout_parse_dimension($section['maxHeight']);
        if ($minH !== null && $maxH !== null && $minH > $maxH) {
            $errors[] = "Section $index: minHeight cannot be greater than maxHeight";
        }
    }
    
    // Validate component
    if (isset($section['type']) && $section['type'] === 'component') {
        if (!isset($section['component']) || empty($section['component'])) {
            $errors[] = "Section $index: Component type requires 'component' field";
        }
    }
    
    // Validate nested sections
    if (isset($section['type']) && $section['type'] === 'split') {
        if (!isset($section['direction'])) {
            $errors[] = "Section $index: Split type requires 'direction' field";
        }
        
        if (isset($section['sections']) && is_array($section['sections'])) {
            foreach ($section['sections'] as $subIndex => $subSection) {
                $subErrors = layout_validate_section($subSection, "$index.$subIndex");
                $errors = array_merge($errors, $subErrors);
            }
        }
    }
    
    return $errors;
}

/**
 * Parse dimension string to numeric value
 * @param string $dimension Dimension string (e.g., "280px", "1fr", "auto")
 * @return float|null Numeric value or null if not parseable
 */
function layout_parse_dimension($dimension) {
    if ($dimension === null || $dimension === 'auto' || $dimension === '1fr') {
        return null;
    }
    
    if (is_numeric($dimension)) {
        return (float)$dimension;
    }
    
    if (preg_match('/^(\d+(?:\.\d+)?)px$/', $dimension, $matches)) {
        return (float)$matches[1];
    }
    
    return null;
}

/**
 * Calculate nesting depth of layout
 * @param array $layoutData Layout data
 * @param int $currentDepth Current depth (internal use)
 * @return int Nesting depth
 */
function layout_calculate_nesting_depth($layoutData, $currentDepth = 0) {
    if (!is_array($layoutData)) {
        return $currentDepth;
    }
    
    $maxDepth = $currentDepth;
    
    if (isset($layoutData['sections']) && is_array($layoutData['sections'])) {
        foreach ($layoutData['sections'] as $section) {
            if (isset($section['type']) && $section['type'] === 'split') {
                $depth = layout_calculate_nesting_depth($section, $currentDepth + 1);
                $maxDepth = max($maxDepth, $depth);
            }
        }
    }
    
    return $maxDepth;
}

/**
 * Render layout to HTML
 * @param array $layoutData Layout data
 * @param string $pageName Page name for context
 * @param array $context Additional context data
 * @param array $variables Variables to replace
 * @return string Generated HTML
 */
function layout_render_layout($layoutData, $pageName = '', $context = [], $variables = []) {
    if (!is_array($layoutData)) {
        return '';
    }
    
    // Check cache if enabled
    if (function_exists('layout_performance_is_caching_enabled') && layout_performance_is_caching_enabled()) {
        require_once __DIR__ . '/performance.php';
        $layoutId = $context['layout_id'] ?? null;
        if ($layoutId) {
            $cacheKey = 'layout_' . $layoutId . '_' . md5(json_encode($layoutData));
            $cached = layout_cache_get($layoutId, $cacheKey);
            if ($cached !== null) {
                return $cached['data'];
            }
        }
    }
    
    $html = layout_render_section($layoutData, $pageName, $context, $variables, 0);
    
    // Apply element templates if specified
    if (isset($layoutData['element_template_ids']) && is_array($layoutData['element_template_ids'])) {
        require_once __DIR__ . '/element_templates.php';
        // Templates are applied during section rendering
    }
    
    // Cache the result if enabled
    if (function_exists('layout_performance_is_caching_enabled') && layout_performance_is_caching_enabled()) {
        $layoutId = $context['layout_id'] ?? null;
        if ($layoutId) {
            $cacheKey = 'layout_' . $layoutId . '_' . md5(json_encode($layoutData));
            layout_cache_set($layoutId, $cacheKey, $html, 3600);
        }
    }
    
    // Minify HTML if enabled
    if (function_exists('layout_performance_is_minification_enabled') && layout_performance_is_minification_enabled()) {
        require_once __DIR__ . '/performance.php';
        $html = layout_minify_html($html);
    }
    
    return $html;
}

/**
 * Render a section
 * @param array $section Section data
 * @param string $pageName Page name
 * @param array $context Context data
 * @param array $variables Variables
 * @param int $depth Current depth
 * @return string Generated HTML
 */
function layout_render_section($section, $pageName = '', $context = [], $variables = [], $depth = 0) {
    if (!is_array($section)) {
        return '';
    }
    
    $type = $section['type'] ?? 'component';
    $sectionId = $section['id'] ?? 'section-' . uniqid();
    
    // Check conditions
    if (!layout_check_conditions($section, $context)) {
        return '';
    }
    
    if ($type === 'split') {
        return layout_render_split($section, $pageName, $context, $variables, $depth);
    } else {
        return layout_render_component($section, $pageName, $context, $variables);
    }
}

/**
 * Render a split section
 * @param array $section Section data
 * @param string $pageName Page name
 * @param array $context Context data
 * @param array $variables Variables
 * @param int $depth Current depth
 * @return string Generated HTML
 */
function layout_render_split($section, $pageName = '', $context = [], $variables = [], $depth = 0) {
    $sectionId = $section['id'] ?? 'split-' . uniqid();
    $direction = $section['direction'] ?? 'horizontal';
    $sections = $section['sections'] ?? [];
    
    $classes = ['layout-split', "layout-split--$direction"];
    if (isset($section['classes'])) {
        $classes = array_merge($classes, (array)$section['classes']);
    }
    
    $style = layout_build_section_style($section);
    
    $html = '<div class="' . htmlspecialchars(implode(' ', $classes)) . '"';
    $html .= ' id="' . htmlspecialchars($sectionId) . '"';
    if ($style) {
        $html .= ' style="' . htmlspecialchars($style) . '"';
    }
    $html .= ' data-layout-depth="' . $depth . '"';
    $html .= '>';
    
    foreach ($sections as $subSection) {
        $html .= layout_render_section($subSection, $pageName, $context, $variables, $depth + 1);
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render a component section
 * @param array $section Section data
 * @param string $pageName Page name
 * @param array $context Context data
 * @param array $variables Variables
 * @return string Generated HTML
 */
function layout_render_component($section, $pageName = '', $context = [], $variables = []) {
    $sectionId = $section['id'] ?? 'component-' . uniqid();
    $component = $section['component'] ?? null;
    $componentParams = $section['componentParams'] ?? [];
    
    $classes = ['layout-component'];
    if (isset($section['classes'])) {
        $classes = array_merge($classes, (array)$section['classes']);
    }
    
    $style = layout_build_section_style($section);
    
    $html = '<div class="' . htmlspecialchars(implode(' ', $classes)) . '"';
    $html .= ' id="' . htmlspecialchars($sectionId) . '"';
    if ($style) {
        $html .= ' style="' . htmlspecialchars($style) . '"';
    }
    $html .= ' data-component="' . htmlspecialchars($component ?? '') . '"';
    $html .= '>';
    
    // Check component dependencies before rendering
    if ($component) {
        // Validate component is installed
        if (!layout_is_component_installed($component)) {
            $requiredVersion = $componentParams['required_version'] ?? null;
            $html .= '<div class="layout-component-error">';
            $html .= '<p><strong>Component "' . htmlspecialchars($component) . '" is not installed.</strong></p>';
            if ($requiredVersion) {
                $html .= '<p>Required version: ' . htmlspecialchars($requiredVersion) . '</p>';
            }
            $html .= '</div>';
        } else {
            // Check version compatibility if specified
            if (isset($componentParams['required_version'])) {
                require_once __DIR__ . '/component_integration.php';
                $compatibility = layout_check_component_compatibility($component, $componentParams['required_version']);
                if (!$compatibility['compatible']) {
                    $html .= '<div class="layout-component-warning">';
                    $html .= '<p>' . htmlspecialchars($compatibility['message']) . '</p>';
                    $html .= '</div>';
                }
            }
            
            // Validate component parameters if validation function exists
            if (function_exists('layout_validate_component_params')) {
                $paramValidation = layout_validate_component_params($component, $componentParams);
                if (!$paramValidation['valid']) {
                    error_log("Layout Engine: Component parameter validation failed: " . json_encode($paramValidation['errors']));
                }
            }
            
            // Apply component template if specified
            if (isset($componentParams['template_id'])) {
                require_once __DIR__ . '/component_integration.php';
                $templateResult = layout_component_template_apply($component, $componentParams['template_id'], $componentParams);
                if (!$templateResult['success']) {
                    error_log("Layout Engine: Failed to apply template: " . $templateResult['error']);
                }
            }
            
            // Include component
            $html .= layout_include_component($component, $componentParams);
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Build CSS style string for section
 * @param array $section Section data
 * @return string CSS style string
 */
function layout_build_section_style($section) {
    $styles = [];
    
    if (isset($section['width'])) {
        $styles[] = 'width: ' . $section['width'];
    }
    if (isset($section['height'])) {
        $styles[] = 'height: ' . $section['height'];
    }
    if (isset($section['minWidth'])) {
        $styles[] = 'min-width: ' . $section['minWidth'];
    }
    if (isset($section['maxWidth'])) {
        $styles[] = 'max-width: ' . $section['maxWidth'];
    }
    if (isset($section['minHeight'])) {
        $styles[] = 'min-height: ' . $section['minHeight'];
    }
    if (isset($section['maxHeight'])) {
        $styles[] = 'max-height: ' . $section['maxHeight'];
    }
    
    return implode('; ', $styles);
}

/**
 * Check if section conditions are met
 * @param array $section Section data
 * @param array $context Context data
 * @return bool True if conditions are met
 */
function layout_check_conditions($section, $context = []) {
    if (!isset($section['conditions']) || !is_array($section['conditions'])) {
        return true;
    }
    
    $conditions = $section['conditions'];
    
    // Check user role
    if (isset($conditions['userRole'])) {
        $userRole = $context['userRole'] ?? null;
        $allowedRoles = (array)($conditions['userRole'] ?? []);
        if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles)) {
            return false;
        }
    }
    
    // Check time conditions
    if (isset($conditions['time'])) {
        $currentHour = (int)date('H');
        $timeRange = $conditions['time'];
        if (isset($timeRange['start']) && $currentHour < (int)$timeRange['start']) {
            return false;
        }
        if (isset($timeRange['end']) && $currentHour >= (int)$timeRange['end']) {
            return false;
        }
    }
    
    // Check day of week
    if (isset($conditions['dayOfWeek'])) {
        $currentDay = (int)date('w');
        $allowedDays = (array)($conditions['dayOfWeek'] ?? []);
        if (!empty($allowedDays) && !in_array($currentDay, $allowedDays)) {
            return false;
        }
    }
    
    // Check custom PHP expression
    if (isset($conditions['phpExpression'])) {
        try {
            $result = eval('return ' . $conditions['phpExpression'] . ';');
            if (!$result) {
                return false;
            }
        } catch (Exception $e) {
            error_log("Layout Engine: PHP expression error: " . $e->getMessage());
            return false;
        }
    }
    
    return true;
}

/**
 * Include component
 * @param string $componentName Component name
 * @param array $params Component parameters
 * @return string Component output
 */
function layout_include_component($componentName, $params = []) {
    // Check if component is installed
    if (!layout_is_component_installed($componentName)) {
        return '<div class="layout-component-placeholder">Component "' . htmlspecialchars($componentName) . '" not installed</div>';
    }
    
    // Try to find component include file
    $componentBasePath = __DIR__ . '/../../' . $componentName;
    $includeFiles = [
        $componentBasePath . '/includes/' . $componentName . '.php',
        $componentBasePath . '/includes/index.php',
        $componentBasePath . '/' . $componentName . '.php',
        $componentBasePath . '/index.php'
    ];
    
    $includePath = null;
    foreach ($includeFiles as $file) {
        if (file_exists($file)) {
            $includePath = $file;
            break;
        }
    }
    
    if ($includePath) {
        ob_start();
        // Extract params for use in component
        if (!empty($params)) {
            extract($params);
        }
        include $includePath;
        return ob_get_clean();
    }
    
    // Use existing component detector as fallback
    if (function_exists('layout_include_component_or_placeholder')) {
        ob_start();
        layout_include_component_or_placeholder($componentName, '', '');
        return ob_get_clean();
    }
    
    return '<div class="layout-component-placeholder">Component "' . htmlspecialchars($componentName) . '" not found</div>';
}

/**
 * Generate CSS for layout
 * @param array $layoutData Layout data
 * @param string $pageName Page name
 * @return string Generated CSS
 */
function layout_generate_css($layoutData, $pageName = '') {
    $css = [];
    
    // Generate CSS for splits
    $css[] = layout_generate_split_css($layoutData, 0);
    
    // Generate responsive CSS
    if (isset($layoutData['breakpoints'])) {
        $css[] = layout_generate_breakpoint_css($layoutData['breakpoints'], $layoutData);
    }
    
    // Apply element template CSS if specified
    if (isset($layoutData['element_template_ids']) && is_array($layoutData['element_template_ids'])) {
        require_once __DIR__ . '/element_templates.php';
        foreach ($layoutData['element_template_ids'] as $templateId) {
            $template = layout_element_template_get($templateId);
            if ($template && !empty($template['css'])) {
                $css[] = $template['css'];
            }
        }
    }
    
    // Apply design system CSS if specified
    if (isset($layoutData['design_system_id'])) {
        require_once __DIR__ . '/design_systems.php';
        $designSystem = layout_design_system_get($layoutData['design_system_id']);
        if ($designSystem && isset($designSystem['theme_data']['css'])) {
            $css[] = $designSystem['theme_data']['css'];
        }
    }
    
    $generatedCss = implode("\n", array_filter($css));
    
    // Minify if enabled
    if (function_exists('layout_performance_is_minification_enabled') && layout_performance_is_minification_enabled()) {
        require_once __DIR__ . '/performance.php';
        $generatedCss = layout_minify_css($generatedCss);
    }
    
    return $generatedCss;
}

/**
 * Generate CSS for split sections
 * @param array $section Section data
 * @param int $depth Current depth
 * @return string Generated CSS
 */
function layout_generate_split_css($section, $depth = 0) {
    $css = [];
    
    if (!isset($section['type']) || $section['type'] !== 'split') {
        return '';
    }
    
    $sectionId = $section['id'] ?? 'split-' . $depth;
    $direction = $section['direction'] ?? 'horizontal';
    
    // Generate grid or flexbox CSS
    if ($direction === 'horizontal') {
        $css[] = "#$sectionId {";
        $css[] = "  display: grid;";
        $css[] = "  grid-template-columns: " . layout_build_grid_columns($section) . ";";
        $css[] = "}";
    } else {
        $css[] = "#$sectionId {";
        $css[] = "  display: grid;";
        $css[] = "  grid-template-rows: " . layout_build_grid_rows($section) . ";";
        $css[] = "}";
    }
    
    // Generate CSS for nested sections
    if (isset($section['sections']) && is_array($section['sections'])) {
        foreach ($section['sections'] as $subSection) {
            $css[] = layout_generate_split_css($subSection, $depth + 1);
        }
    }
    
    return implode("\n", $css);
}

/**
 * Build grid columns string
 * @param array $section Section data
 * @return string Grid columns string
 */
function layout_build_grid_columns($section) {
    $columns = [];
    
    if (isset($section['sections']) && is_array($section['sections'])) {
        foreach ($section['sections'] as $subSection) {
            $width = $subSection['width'] ?? '1fr';
            $columns[] = $width;
        }
    }
    
    return empty($columns) ? '1fr' : implode(' ', $columns);
}

/**
 * Build grid rows string
 * @param array $section Section data
 * @return string Grid rows string
 */
function layout_build_grid_rows($section) {
    $rows = [];
    
    if (isset($section['sections']) && is_array($section['sections'])) {
        foreach ($section['sections'] as $subSection) {
            $height = $subSection['height'] ?? '1fr';
            $rows[] = $height;
        }
    }
    
    return empty($rows) ? '1fr' : implode(' ', $rows);
}

/**
 * Generate breakpoint CSS
 * @param array $breakpoints Breakpoints configuration
 * @param array $layoutData Layout data
 * @return string Generated CSS
 */
function layout_generate_breakpoint_css($breakpoints, $layoutData) {
    $css = [];
    
    foreach ($breakpoints as $breakpointName => $breakpointConfig) {
        if (!isset($breakpointConfig['min']) && !isset($breakpointConfig['max'])) {
            continue;
        }
        
        $mediaQuery = '@media ';
        if (isset($breakpointConfig['min'])) {
            $mediaQuery .= '(min-width: ' . $breakpointConfig['min'] . 'px)';
        }
        if (isset($breakpointConfig['min']) && isset($breakpointConfig['max'])) {
            $mediaQuery .= ' and ';
        }
        if (isset($breakpointConfig['max'])) {
            $mediaQuery .= '(max-width: ' . $breakpointConfig['max'] . 'px)';
        }
        
        $css[] = $mediaQuery . ' {';
        // Generate CSS for breakpoint-specific layout
        if (isset($breakpointConfig['layout'])) {
            $css[] = layout_generate_split_css($breakpointConfig['layout'], 0);
        }
        $css[] = '}';
    }
    
    return implode("\n", $css);
}

/**
 * Get render context
 * @param string $pageName Page name
 * @return array Context data
 */
function layout_get_render_context($pageName = '') {
    $context = [
        'pageName' => $pageName,
        'userRole' => null,
        'userId' => null,
        'timestamp' => time()
    ];
    
    // Get user role if available
    if (isset($_SESSION['user_id'])) {
        $context['userId'] = $_SESSION['user_id'];
        
        // Try to get user role from access component or session
        if (function_exists('get_user_role')) {
            $context['userRole'] = get_user_role($_SESSION['user_id']);
        } elseif (isset($_SESSION['user_role'])) {
            $context['userRole'] = $_SESSION['user_role'];
        }
    }
    
    return $context;
}

