<?php
/**
 * Access Component - Default Menu Links
 * Creates menu entries in menu_system_menus table during installation
 * @param mysqli $conn Database connection
 * @param string $componentName Component name (e.g., 'access')
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function access_create_menu_links($conn, $componentName, $adminUrl) {
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
    $sectionTitle = 'Access Management';
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
            'title' => 'Dashboard',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/index.php',
            'page_identifier' => $componentName . '_index',
            'icon' => 'dashboard',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Account Types',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/account-types/index.php',
            'page_identifier' => $componentName . '_account_types',
            'icon' => 'category',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Accounts',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/accounts/index.php',
            'page_identifier' => $componentName . '_accounts',
            'icon' => 'account_circle',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Users',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/users/index.php',
            'page_identifier' => $componentName . '_users',
            'icon' => 'people',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Registrations',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/registrations/index.php',
            'page_identifier' => $componentName . '_registrations',
            'icon' => 'person_add',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Roles',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/roles/index.php',
            'page_identifier' => $componentName . '_roles',
            'icon' => 'admin_panel_settings',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Permissions',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/permissions/index.php',
            'page_identifier' => $componentName . '_permissions',
            'icon' => 'lock',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Messages',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/messaging/index.php',
            'page_identifier' => $componentName . '_messaging',
            'icon' => 'mail',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Chat',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/chat/index.php',
            'page_identifier' => $componentName . '_chat',
            'icon' => 'chat',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Notifications',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/notifications/index.php',
            'page_identifier' => $componentName . '_notifications',
            'icon' => 'notifications',
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
            error_log("Access: Error creating menu link {$link['title']}: " . $e->getMessage());
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
function access_remove_menu_links($conn, $componentName) {
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

