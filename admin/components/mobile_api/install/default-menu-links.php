<?php
/**
 * Mobile API Component - Default Menu Links
 * Creates menu links for mobile_api component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function mobile_api_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
    // Create section heading
    $sectionHeadingId = null;
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, page_identifier, menu_order, is_active, menu_type, is_section_heading) VALUES (?, ?, ?, ?, 1, 'admin', 1)");
    $sectionTitle = 'Mobile API';
    $sectionUrl = '#';
    $sectionIdentifier = 'mobile_api_section';
    $stmt->bind_param("sssi", $sectionTitle, $sectionUrl, $sectionIdentifier, $menuOrder);
    $stmt->execute();
    $sectionHeadingId = $conn->insert_id;
    $createdMenus[] = $sectionHeadingId;
    $menuOrder++;
    
    // Create menu links for each admin page
    $menuLinks = [
        [
            'title' => 'Dashboard',
            'url' => $adminUrl . '/components/mobile_api/admin/index.php',
            'page_identifier' => 'mobile_api_dashboard',
            'icon' => 'dashboard',
            'icon_svg_path' => null
        ],
        [
            'title' => 'App Builder',
            'url' => $adminUrl . '/components/mobile_api/admin/app_builder.php',
            'page_identifier' => 'mobile_api_app_builder',
            'icon' => 'apps',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Endpoints',
            'url' => $adminUrl . '/components/mobile_api/admin/endpoints.php',
            'page_identifier' => 'mobile_api_endpoints',
            'icon' => 'api',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Authentication',
            'url' => $adminUrl . '/components/mobile_api/admin/authentication.php',
            'page_identifier' => 'mobile_api_authentication',
            'icon' => 'lock',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Location Tracking',
            'url' => $adminUrl . '/components/mobile_api/admin/location_tracking.php',
            'page_identifier' => 'mobile_api_location_tracking',
            'icon' => 'location_on',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Collection Addresses',
            'url' => $adminUrl . '/components/mobile_api/admin/collection_addresses.php',
            'page_identifier' => 'mobile_api_collection_addresses',
            'icon' => 'place',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Maps',
            'url' => $adminUrl . '/components/mobile_api/admin/maps.php',
            'page_identifier' => 'mobile_api_maps',
            'icon' => 'map',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Analytics',
            'url' => $adminUrl . '/components/mobile_api/admin/analytics.php',
            'page_identifier' => 'mobile_api_analytics',
            'icon' => 'analytics',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Notifications',
            'url' => $adminUrl . '/components/mobile_api/admin/notifications.php',
            'page_identifier' => 'mobile_api_notifications',
            'icon' => 'notifications',
            'icon_svg_path' => null
        ],
        [
            'title' => 'Settings',
            'url' => $adminUrl . '/components/mobile_api/admin/settings.php',
            'page_identifier' => 'mobile_api_settings',
            'icon' => 'settings',
            'icon_svg_path' => null
        ]
    ];
    
    foreach ($menuLinks as $link) {
        // Check if menu already exists
        $checkStmt = $conn->prepare("SELECT id FROM menu_system_menus WHERE page_identifier = ?");
        $checkStmt->bind_param("s", $link['page_identifier']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            continue; // Skip if already exists
        }
        $checkStmt->close();
        
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
        $menuOrder++;
        $stmt->close();
    }
    
    return ['success' => true, 'menu_ids' => $createdMenus];
}

/**
 * Remove menu links created by mobile_api component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @return array Result with success status
 */
function mobile_api_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with mobile_api
    $pattern = 'mobile_api_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

