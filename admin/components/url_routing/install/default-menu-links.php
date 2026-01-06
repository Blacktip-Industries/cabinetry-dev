<?php
/**
 * URL Routing Component - Default Menu Links
 * Creates menu links in menu_system_menus table during installation
 */

/**
 * Create menu links for URL Routing component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function url_routing_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
    // Single page component - no section heading needed
    $menuLinks = [
        [
            'title' => 'URL Routing',
            'url' => $adminUrl . '/components/url_routing/admin/routes.php',
            'page_identifier' => 'url_routing_routes',
            'icon' => null,
            'icon_svg_path' => null
        ],
    ];
    
    foreach ($menuLinks as $link) {
        $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, icon_svg_path, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, 1, 'admin')");
        $icon = $link['icon'] ?? null;
        $iconSvg = $link['icon_svg_path'] ?? null;
        $stmt->bind_param("sssssi", 
            $link['title'],
            $link['url'],
            $icon,
            $iconSvg,
            $link['page_identifier'],
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
 * Remove menu links created by URL Routing component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @return array Result with success status
 */
function url_routing_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = 'url_routing_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

