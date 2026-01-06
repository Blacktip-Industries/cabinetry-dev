<?php
/**
 * Layout Component - Menu Links
 * Creates menu entries in menu_system_menus table during installation
 * @param mysqli $conn Database connection
 * @param string $componentName Component name (e.g., 'layout')
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function layout_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100; // Starting order
    
    // Check if menu links already exist
    $checkStmt = $conn->prepare("SELECT id FROM menu_system_menus WHERE page_identifier LIKE ?");
    $pattern = $componentName . '_%';
    $checkStmt->bind_param("s", $pattern);
    $checkStmt->execute();
    $existing = $checkStmt->get_result();
    if ($existing->num_rows > 0) {
        $checkStmt->close();
        return ['success' => true, 'message' => 'Menu links already exist', 'menu_ids' => []];
    }
    $checkStmt->close();
    
    // Create section heading
    $sectionHeadingId = null;
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, page_identifier, menu_order, is_active, menu_type, is_section_heading) VALUES (?, ?, ?, ?, 1, 'admin', 1)");
    $sectionTitle = 'Layout & Design';
    $sectionUrl = '#';
    $sectionIdentifier = $componentName . '_section';
    $stmt->bind_param("sssi", $sectionTitle, $sectionUrl, $sectionIdentifier, $menuOrder);
    $stmt->execute();
    $sectionHeadingId = $conn->insert_id;
    $createdMenus[] = $sectionHeadingId;
    $stmt->close();
    $menuOrder++;
    
    // Element Templates parent menu
    $elementTemplatesParentId = null;
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, page_identifier, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, ?, ?, 1, 'admin')");
    $parentTitle = 'Element Templates';
    $parentUrl = $adminUrl . '/components/' . $componentName . '/admin/element-templates/index.php';
    $parentIcon = 'widgets';
    $parentIdentifier = $componentName . '_element_templates';
    $stmt->bind_param("ssssii", $parentTitle, $parentUrl, $parentIcon, $parentIdentifier, $sectionHeadingId, $menuOrder);
    $stmt->execute();
    $elementTemplatesParentId = $conn->insert_id;
    $createdMenus[] = $elementTemplatesParentId;
    $stmt->close();
    $menuOrder++;
    
    // Element Templates sub-items
    $elementTemplateSubItems = [
        [
            'title' => 'All Templates',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/element-templates/index.php',
            'page_identifier' => $componentName . '_element_templates',
            'icon' => 'list'
        ],
        [
            'title' => 'Create Template',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/element-templates/create.php',
            'page_identifier' => $componentName . '_element_templates_create',
            'icon' => 'add'
        ],
        [
            'title' => 'Upload Image',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/element-templates/upload-image.php',
            'page_identifier' => $componentName . '_element_templates_upload',
            'icon' => 'upload'
        ]
    ];
    
    foreach ($elementTemplateSubItems as $subItem) {
        $iconSvgPath = null;
        if (!empty($subItem['icon'])) {
            $iconResult = $conn->query("SELECT svg_path FROM menu_system_icons WHERE name = '" . $conn->real_escape_string($subItem['icon']) . "' LIMIT 1");
            if ($iconResult && $iconRow = $iconResult->fetch_assoc()) {
                $iconSvgPath = $iconRow['svg_path'];
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, icon_svg_path, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'admin')");
        $icon = $subItem['icon'] ?? null;
        $stmt->bind_param("sssssiii", $subItem['title'], $subItem['url'], $icon, $iconSvgPath, $subItem['page_identifier'], $elementTemplatesParentId, $sectionHeadingId, $menuOrder);
        $stmt->execute();
        $createdMenus[] = $conn->insert_id;
        $stmt->close();
        $menuOrder++;
    }
    
    // Design Systems parent menu
    $designSystemsParentId = null;
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, page_identifier, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, ?, ?, 1, 'admin')");
    $parentTitle = 'Design Systems';
    $parentUrl = $adminUrl . '/components/' . $componentName . '/admin/design-systems/index.php';
    $parentIcon = 'palette';
    $parentIdentifier = $componentName . '_design_systems';
    $stmt->bind_param("ssssii", $parentTitle, $parentUrl, $parentIcon, $parentIdentifier, $sectionHeadingId, $menuOrder);
    $stmt->execute();
    $designSystemsParentId = $conn->insert_id;
    $createdMenus[] = $designSystemsParentId;
    $stmt->close();
    $menuOrder++;
    
    // Design Systems sub-items
    $designSystemSubItems = [
        [
            'title' => 'All Design Systems',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/design-systems/index.php',
            'page_identifier' => $componentName . '_design_systems',
            'icon' => 'list'
        ],
        [
            'title' => 'Create Design System',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/design-systems/create.php',
            'page_identifier' => $componentName . '_design_systems_create',
            'icon' => 'add'
        ]
    ];
    
    foreach ($designSystemSubItems as $subItem) {
        $iconSvgPath = null;
        if (!empty($subItem['icon'])) {
            $iconResult = $conn->query("SELECT svg_path FROM menu_system_icons WHERE name = '" . $conn->real_escape_string($subItem['icon']) . "' LIMIT 1");
            if ($iconResult && $iconRow = $iconResult->fetch_assoc()) {
                $iconSvgPath = $iconRow['svg_path'];
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, icon_svg_path, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'admin')");
        $icon = $subItem['icon'] ?? null;
        $stmt->bind_param("sssssiii", $subItem['title'], $subItem['url'], $icon, $iconSvgPath, $subItem['page_identifier'], $designSystemsParentId, $sectionHeadingId, $menuOrder);
        $stmt->execute();
        $createdMenus[] = $conn->insert_id;
        $stmt->close();
        $menuOrder++;
    }
    
    // Top-level menu items (no parent)
    $topLevelItems = [
        [
            'title' => 'Monitoring Dashboard',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/monitoring/index.php',
            'page_identifier' => $componentName . '_monitoring',
            'icon' => 'dashboard'
        ],
        [
            'title' => 'Export/Import',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/export/export.php',
            'page_identifier' => $componentName . '_export',
            'icon' => 'import_export'
        ],
        [
            'title' => 'Preview',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/preview/preview.php',
            'page_identifier' => $componentName . '_preview',
            'icon' => 'preview'
        ]
    ];
    
    foreach ($topLevelItems as $item) {
        $iconSvgPath = null;
        if (!empty($item['icon'])) {
            $iconResult = $conn->query("SELECT svg_path FROM menu_system_icons WHERE name = '" . $conn->real_escape_string($item['icon']) . "' LIMIT 1");
            if ($iconResult && $iconRow = $iconResult->fetch_assoc()) {
                $iconSvgPath = $iconRow['svg_path'];
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, icon_svg_path, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, 1, 'admin')");
        $icon = $item['icon'] ?? null;
        $stmt->bind_param("sssssii", $item['title'], $item['url'], $icon, $iconSvgPath, $item['page_identifier'], $sectionHeadingId, $menuOrder);
        $stmt->execute();
        $createdMenus[] = $conn->insert_id;
        $stmt->close();
        $menuOrder++;
    }
    
    return ['success' => true, 'menu_ids' => $createdMenus];
}

/**
 * Remove menu links created by component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @return array Result with success status
 */
function layout_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = $componentName . '_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

