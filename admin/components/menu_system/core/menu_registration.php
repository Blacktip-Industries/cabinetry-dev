<?php
/**
 * Menu System Component - Menu Registration Functions
 * Helper functions for components to register menu links
 */

require_once __DIR__ . '/database.php';

/**
 * Get icon SVG path by icon name
 * @param string $iconName Icon name
 * @param mysqli|null $conn Database connection (optional, will get if not provided)
 * @return string|null SVG path or null
 */
function menu_system_get_icon_svg_path($iconName, $conn = null) {
    if (empty($iconName)) {
        return null;
    }
    
    if ($conn === null) {
        $conn = menu_system_get_db_connection();
    }
    
    if ($conn === null) {
        return null;
    }
    
    $icon = menu_system_get_icon_by_name($iconName);
    return $icon ? $icon['svg_path'] : null;
}

/**
 * Create a single menu link
 * @param mysqli $conn Database connection
 * @param array $data Menu link data
 * @return array Result with success status and menu ID
 */
function menu_system_create_menu_link($conn, $data) {
    // Validate required fields
    if (empty($data['title']) || empty($data['url']) || empty($data['page_identifier'])) {
        return ['success' => false, 'error' => 'Title, URL, and page_identifier are required'];
    }
    
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system_menus table does not exist'];
    }
    
    // Get icon SVG path if icon name provided
    $iconSvgPath = null;
    if (!empty($data['icon'])) {
        $iconSvgPath = menu_system_get_icon_svg_path($data['icon'], $conn);
    }
    
    // Set defaults
    $title = $data['title'];
    $url = $data['url'];
    $icon = $data['icon'] ?? null;
    $pageIdentifier = $data['page_identifier'];
    $parentId = $data['parent_id'] ?? null;
    $sectionHeadingId = $data['section_heading_id'] ?? null;
    $menuOrder = $data['menu_order'] ?? 0;
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    $menuType = $data['menu_type'] ?? 'admin';
    $isSectionHeading = isset($data['is_section_heading']) ? (int)$data['is_section_heading'] : 0;
    $isPinned = isset($data['is_pinned']) ? (int)$data['is_pinned'] : 0;
    
    try {
        $tableName = menu_system_get_table_name('menus');
        
        // Check if menu link already exists (by page_identifier)
        $checkStmt = $conn->prepare("SELECT id FROM {$tableName} WHERE page_identifier = ? AND menu_type = ?");
        $checkStmt->bind_param("ss", $pageIdentifier, $menuType);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $existing = $checkResult->fetch_assoc();
            $checkStmt->close();
            return ['success' => true, 'menu_id' => $existing['id'], 'message' => 'Menu link already exists'];
        }
        $checkStmt->close();
        
        // Insert menu link
        $stmt = $conn->prepare("INSERT INTO {$tableName} (title, url, icon, icon_svg_path, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type, is_section_heading, is_pinned) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param("sssssiiiiiii", $title, $url, $icon, $iconSvgPath, $pageIdentifier, $parentId, $sectionHeadingId, $menuOrder, $isActive, $menuType, $isSectionHeading, $isPinned);
        
        if ($stmt->execute()) {
            $menuId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'menu_id' => $menuId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Menu System: Error creating menu link: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Find all component menu-links.php files
 * @return array Array of file paths with component names
 */
function menu_system_find_component_menu_files() {
    $componentsDir = __DIR__ . '/../../';
    $menuFiles = [];
    
    if (!is_dir($componentsDir)) {
        return $menuFiles;
    }
    
    $components = glob($componentsDir . '*/install/menu-links.php');
    
    foreach ($components as $filePath) {
        // Extract component name from path
        // Path format: .../admin/components/{component_name}/install/menu-links.php
        $pathParts = explode(DIRECTORY_SEPARATOR, $filePath);
        $componentIndex = array_search('components', $pathParts);
        
        if ($componentIndex !== false && isset($pathParts[$componentIndex + 1])) {
            $componentName = $pathParts[$componentIndex + 1];
            
            // Skip menu_system itself
            if ($componentName === 'menu_system') {
                continue;
            }
            
            $menuFiles[] = [
                'component_name' => $componentName,
                'file_path' => $filePath
            ];
        }
    }
    
    return $menuFiles;
}

/**
 * Register menu links for a specific component
 * @param string $componentName Component name
 * @param mysqli $conn Database connection
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and details
 */
function menu_system_register_component_menu_links($componentName, $conn, $adminUrl) {
    $menuFile = __DIR__ . '/../../' . $componentName . '/install/menu-links.php';
    
    if (!file_exists($menuFile)) {
        return ['success' => false, 'error' => 'Menu links file not found: ' . $menuFile];
    }
    
    // Check if component is installed (has config.php)
    $configFile = __DIR__ . '/../../' . $componentName . '/config.php';
    if (!file_exists($configFile)) {
        return ['success' => false, 'error' => 'Component not installed (config.php not found)'];
    }
    
    // Load the menu-links.php file
    require_once $menuFile;
    
    // Call the component's menu link creation function
    $functionName = $componentName . '_create_menu_links';
    
    if (!function_exists($functionName)) {
        return ['success' => false, 'error' => "Function {$functionName} not found in menu-links.php"];
    }
    
    try {
        $result = $functionName($conn, $componentName, $adminUrl);
        return $result;
    } catch (Exception $e) {
        error_log("Menu System: Error registering menu links for {$componentName}: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Process all component menu link files
 * Called during menu_system installation to register links for components installed before menu_system
 * @param mysqli $conn Database connection
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and processed components
 */
function menu_system_process_all_component_menus($conn, $adminUrl) {
    $menuFiles = menu_system_find_component_menu_files();
    $processed = [];
    $errors = [];
    $skipped = [];
    
    foreach ($menuFiles as $menuFile) {
        $componentName = $menuFile['component_name'];
        
        // Check if component is installed
        $configFile = __DIR__ . '/../../' . $componentName . '/config.php';
        if (!file_exists($configFile)) {
            $skipped[] = $componentName . ' (not installed)';
            continue;
        }
        
        // Register menu links for this component
        $result = menu_system_register_component_menu_links($componentName, $conn, $adminUrl);
        
        if ($result['success']) {
            $processed[] = [
                'component' => $componentName,
                'message' => $result['message'] ?? 'Menu links registered successfully',
                'menu_ids' => $result['menu_ids'] ?? []
            ];
        } else {
            $errors[] = [
                'component' => $componentName,
                'error' => $result['error'] ?? 'Unknown error'
            ];
        }
    }
    
    return [
        'success' => empty($errors),
        'processed' => $processed,
        'errors' => $errors,
        'skipped' => $skipped
    ];
}

/**
 * Remove menu links for a component
 * @param string $componentName Component name
 * @param mysqli $conn Database connection
 * @return array Result with success status
 */
function menu_system_remove_component_menu_links($componentName, $conn) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Try to call component's remove function if it exists
    $menuFile = __DIR__ . '/../../' . $componentName . '/install/menu-links.php';
    if (file_exists($menuFile)) {
        require_once $menuFile;
        $functionName = $componentName . '_remove_menu_links';
        
        if (function_exists($functionName)) {
            return $functionName($conn, $componentName);
        }
    }
    
    // Fallback: Remove by page_identifier pattern
    $tableName = menu_system_get_table_name('menus');
    $pattern = $componentName . '_%';
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

