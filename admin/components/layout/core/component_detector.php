<?php
/**
 * Layout Component - Component Detector
 * Detects if other components (header, menu_system, footer) are installed
 * Enhanced with version detection, metadata extraction, and compatibility checking
 */

/**
 * Check if a component is installed
 * @param string $componentName Component name (e.g., 'header', 'menu_system', 'footer')
 * @return bool True if component is installed
 */
function layout_is_component_installed($componentName) {
    $componentPath = __DIR__ . '/../../' . $componentName;
    
    // Check if component directory exists
    if (!is_dir($componentPath)) {
        return false;
    }
    
    // Check if component has config.php (indicates it's been installed)
    $configPath = $componentPath . '/config.php';
    if (file_exists($configPath)) {
        return true;
    }
    
    // Also check for includes directory (component structure exists)
    $includesPath = $componentPath . '/includes';
    if (is_dir($includesPath)) {
        return true;
    }
    
    return false;
}

/**
 * Get component version
 * @param string $componentName Component name
 * @return string|null Version string or null if not available
 */
function layout_component_detector_get_version($componentName) {
    $componentPath = __DIR__ . '/../../' . $componentName;
    
    // Try VERSION file first
    $versionFile = $componentPath . '/VERSION';
    if (file_exists($versionFile)) {
        $version = trim(file_get_contents($versionFile));
        if (!empty($version)) {
            return $version;
        }
    }
    
    // Try config.php
    $configFile = $componentPath . '/config.php';
    if (file_exists($configFile)) {
        $configContent = file_get_contents($configFile);
        // Look for version definition
        if (preg_match('/version[\'"]?\s*[=:]\s*[\'"]([^\'"]+)[\'"]/', $configContent, $matches)) {
            return $matches[1];
        }
        // Look for VERSION constant
        if (preg_match('/define\s*\(\s*[\'"]VERSION[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/', $configContent, $matches)) {
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
function layout_component_detector_get_metadata($componentName) {
    $componentPath = __DIR__ . '/../../' . $componentName;
    $metadata = [
        'name' => $componentName,
        'version' => layout_component_detector_get_version($componentName),
        'description' => null,
        'capabilities' => [],
        'path' => $componentPath,
        'installed' => layout_is_component_installed($componentName)
    ];
    
    // Try to read README.md for description
    $readmeFile = $componentPath . '/README.md';
    if (file_exists($readmeFile)) {
        $readmeContent = file_get_contents($readmeFile);
        // Extract first paragraph after title
        if (preg_match('/^#\s+.*?\n\n(.+?)(?:\n\n|$)/s', $readmeContent, $matches)) {
            $metadata['description'] = trim($matches[1]);
        }
    }
    
    // Detect capabilities based on file structure
    $capabilities = [];
    
    // Check for common include files
    if (file_exists($componentPath . '/includes/header.php')) {
        $capabilities[] = 'header';
    }
    if (file_exists($componentPath . '/includes/sidebar.php') || file_exists($componentPath . '/includes/menu.php')) {
        $capabilities[] = 'menu';
    }
    if (file_exists($componentPath . '/includes/footer.php')) {
        $capabilities[] = 'footer';
    }
    if (file_exists($componentPath . '/includes/layout.php')) {
        $capabilities[] = 'layout';
    }
    
    // Check for admin interface
    if (is_dir($componentPath . '/admin')) {
        $capabilities[] = 'admin_interface';
    }
    
    // Check for API
    if (is_dir($componentPath . '/api') || file_exists($componentPath . '/api.php')) {
        $capabilities[] = 'api';
    }
    
    // Check for database
    if (is_dir($componentPath . '/install') || file_exists($componentPath . '/install.php')) {
        $capabilities[] = 'database';
    }
    
    $metadata['capabilities'] = $capabilities;
    
    return $metadata;
}

/**
 * Check component compatibility
 * @param string $componentName Component name
 * @param string $requiredVersion Required version (optional)
 * @return array Compatibility information
 */
function layout_component_detector_check_compatibility($componentName, $requiredVersion = null) {
    $isInstalled = layout_is_component_installed($componentName);
    
    if (!$isInstalled) {
        return [
            'compatible' => false,
            'installed' => false,
            'message' => "Component '{$componentName}' is not installed"
        ];
    }
    
    $installedVersion = layout_component_detector_get_version($componentName);
    
    if ($requiredVersion && $installedVersion) {
        $compatible = version_compare($installedVersion, $requiredVersion, '>=');
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
 * Get the include path for a component's main file
 * @param string $componentName Component name
 * @param string $includeFile Include file name (e.g., 'header.php', 'sidebar.php', 'footer.php')
 * @return string|null Full path to include file or null if not found
 */
function layout_get_component_include_path($componentName, $includeFile) {
    $componentPath = __DIR__ . '/../../' . $componentName;
    
    // Try includes directory first
    $includesPath = $componentPath . '/includes/' . $includeFile;
    if (file_exists($includesPath)) {
        return $includesPath;
    }
    
    // Try root of component
    $rootPath = $componentPath . '/' . $includeFile;
    if (file_exists($rootPath)) {
        return $rootPath;
    }
    
    return null;
}

/**
 * Render a placeholder for a missing component
 * @param string $componentName Component name
 * @param string $gridArea CSS grid area name
 * @param array $options Additional options (version, install_url, etc.)
 * @return string HTML for placeholder
 */
function layout_render_component_placeholder($componentName, $gridArea, $options = []) {
    $componentDisplayName = ucfirst(str_replace('_', ' ', $componentName));
    $componentPath = '/admin/components/' . $componentName;
    
    // Get component metadata if available
    $metadata = layout_component_detector_get_metadata($componentName);
    $version = $options['version'] ?? $metadata['version'] ?? null;
    $requiredVersion = $options['required_version'] ?? null;
    $installUrl = $options['install_url'] ?? null;
    
    // Check if component_manager is available for installation
    $componentManagerAvailable = layout_is_component_installed('component_manager');
    $installButton = '';
    
    if ($componentManagerAvailable && $installUrl) {
        $installButton = '<a href="' . htmlspecialchars($installUrl) . '" class="layout-placeholder__install-btn">Install Component</a>';
    } elseif ($componentManagerAvailable) {
        $installButton = '<a href="/admin/components/component_manager/admin/install.php?component=' . urlencode($componentName) . '" class="layout-placeholder__install-btn">Install Component</a>';
    }
    
    $versionInfo = '';
    if ($requiredVersion) {
        $versionInfo = '<br><small>Required version: ' . htmlspecialchars($requiredVersion) . '</small>';
    } elseif ($version) {
        $versionInfo = '<br><small>Latest version: ' . htmlspecialchars($version) . '</small>';
    }
    
    $html = '<div class="layout-placeholder layout-placeholder--' . htmlspecialchars($gridArea) . '" style="grid-area: ' . htmlspecialchars($gridArea) . ';">';
    $html .= '<div class="layout-placeholder__content">';
    $html .= '<p class="layout-placeholder__message">';
    $html .= '<strong>' . htmlspecialchars($componentDisplayName) . ' component not installed.</strong><br>';
    $html .= 'Install at <code>' . htmlspecialchars($componentPath) . '</code>';
    $html .= $versionInfo;
    $html .= '</p>';
    
    if ($metadata['description']) {
        $html .= '<p class="layout-placeholder__description">' . htmlspecialchars($metadata['description']) . '</p>';
    }
    
    if ($installButton) {
        $html .= '<div class="layout-placeholder__actions">' . $installButton . '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Include a component if available, or render placeholder
 * @param string $componentName Component name
 * @param string $includeFile Include file name
 * @param string $gridArea CSS grid area name
 * @return void Outputs component or placeholder
 */
function layout_include_component_or_placeholder($componentName, $includeFile, $gridArea) {
    if (layout_is_component_installed($componentName)) {
        $includePath = layout_get_component_include_path($componentName, $includeFile);
        if ($includePath && file_exists($includePath)) {
            // Include the component
            include $includePath;
            return;
        }
    }
    
    // Component not found, render placeholder
    echo layout_render_component_placeholder($componentName, $gridArea);
}

