<?php
/**
 * Savepoints Component - Default Menu Links
 * Creates menu entries in menu_system_menus table during installation
 * @param mysqli $conn Database connection
 * @param string $componentName Component name (e.g., 'savepoints')
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function savepoints_create_menu_links($conn, $componentName, $adminUrl) {
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
    
    // Create section heading (since we have multiple pages)
    $sectionHeadingId = null;
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, page_identifier, menu_order, is_active, menu_type, is_section_heading) VALUES (?, ?, ?, ?, 1, 'admin', 1)");
    $sectionTitle = 'Savepoints';
    $sectionUrl = '#';
    $sectionIdentifier = $componentName . '_section';
    $stmt->bind_param("sssi", $sectionTitle, $sectionUrl, $sectionIdentifier, $menuOrder);
    $stmt->execute();
    $sectionHeadingId = $conn->insert_id;
    $createdMenus[] = $sectionHeadingId;
    $stmt->close();
    $menuOrder++;
    
    // Menu links for component pages
    $menuLinks = [
        [
            'title' => 'Savepoints',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/index.php',
            'page_identifier' => $componentName . '_index',
            'icon' => 'backup',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Create Savepoint',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/create.php',
            'page_identifier' => $componentName . '_create',
            'icon' => 'add_circle',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Restore',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/restore.php',
            'page_identifier' => $componentName . '_restore',
            'icon' => 'restore',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Restore Test',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/restore-test.php',
            'page_identifier' => $componentName . '_restore_test',
            'icon' => 'science',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Settings',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/settings.php',
            'page_identifier' => $componentName . '_settings',
            'icon' => 'settings',
            'icon_svg_path' => null
        ]
    ];
    
    foreach ($menuLinks as $link) {
        try {
            $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, icon_svg_path, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, 1, 'admin')");
            $icon = $link['icon'] ?? null;
            $iconSvg = $link['icon_svg_path'] ?? null;
            $stmt->bind_param("sssssii", 
                $link['title'],
                $link['url'],
                $icon,
                $iconSvg,
                $link['page_identifier'],
                $sectionHeadingId,
                $menuOrder
            );
            $stmt->execute();
            $createdMenus[] = $conn->insert_id;
            $stmt->close();
            $menuOrder++;
        } catch (mysqli_sql_exception $e) {
            error_log("Savepoints: Error creating menu link {$link['title']}: " . $e->getMessage());
        }
    }
    
    return ['success' => true, 'menu_ids' => $createdMenus];
}

/**
 * Remove menu links created by component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @return array Result with success status
 */
function savepoints_remove_menu_links($conn, $componentName) {
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

