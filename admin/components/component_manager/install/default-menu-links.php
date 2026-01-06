<?php
/**
 * Component Manager - Default Menu Links
 * Creates menu links in menu_system_menus table during installation
 */

/**
 * Create menu links for Component Manager component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function component_manager_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
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
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, page_identifier, menu_order, is_active, menu_type, is_section_heading) VALUES (?, ?, ?, ?, 1, 'admin', 1)");
    $sectionTitle = 'Component Manager';
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
        ['title' => 'Dashboard', 'url' => $adminUrl . '/components/' . $componentName . '/admin/index.php', 'page_identifier' => $componentName . '_dashboard', 'icon' => 'dashboard'],
        ['title' => 'Component Registry', 'url' => $adminUrl . '/components/' . $componentName . '/admin/registry.php', 'page_identifier' => $componentName . '_registry', 'icon' => 'list'],
        ['title' => 'Updates', 'url' => $adminUrl . '/components/' . $componentName . '/admin/update.php', 'page_identifier' => $componentName . '_update', 'icon' => 'update'],
        ['title' => 'Changelog', 'url' => $adminUrl . '/components/' . $componentName . '/admin/changelog.php', 'page_identifier' => $componentName . '_changelog', 'icon' => 'history'],
        ['title' => 'Rollback', 'url' => $adminUrl . '/components/' . $componentName . '/admin/rollback.php', 'page_identifier' => $componentName . '_rollback', 'icon' => 'undo'],
        ['title' => 'Uninstall', 'url' => $adminUrl . '/components/' . $componentName . '/admin/uninstall.php', 'page_identifier' => $componentName . '_uninstall', 'icon' => 'delete'],
        ['title' => 'Conflicts', 'url' => $adminUrl . '/components/' . $componentName . '/admin/conflicts.php', 'page_identifier' => $componentName . '_conflicts', 'icon' => 'warning'],
        ['title' => 'Usage Analytics', 'url' => $adminUrl . '/components/' . $componentName . '/admin/usage.php', 'page_identifier' => $componentName . '_usage', 'icon' => 'analytics'],
        ['title' => 'Export/Import', 'url' => $adminUrl . '/components/' . $componentName . '/admin/export_import.php', 'page_identifier' => $componentName . '_export_import', 'icon' => 'import_export'],
        ['title' => 'Categories', 'url' => $adminUrl . '/components/' . $componentName . '/admin/categories.php', 'page_identifier' => $componentName . '_categories', 'icon' => 'category'],
        ['title' => 'Dependency Graph', 'url' => $adminUrl . '/components/' . $componentName . '/admin/dependency_graph.php', 'page_identifier' => $componentName . '_dependency_graph', 'icon' => 'account_tree'],
        ['title' => 'Notifications', 'url' => $adminUrl . '/components/' . $componentName . '/admin/notifications.php', 'page_identifier' => $componentName . '_notifications', 'icon' => 'notifications'],
        ['title' => 'Logs', 'url' => $adminUrl . '/components/' . $componentName . '/admin/logs.php', 'page_identifier' => $componentName . '_logs', 'icon' => 'description'],
        ['title' => 'Performance', 'url' => $adminUrl . '/components/' . $componentName . '/admin/performance.php', 'page_identifier' => $componentName . '_performance', 'icon' => 'speed'],
        ['title' => 'Security', 'url' => $adminUrl . '/components/' . $componentName . '/admin/security.php', 'page_identifier' => $componentName . '_security', 'icon' => 'security'],
        ['title' => 'Scheduling', 'url' => $adminUrl . '/components/' . $componentName . '/admin/scheduling.php', 'page_identifier' => $componentName . '_scheduling', 'icon' => 'schedule'],
        ['title' => 'Reports', 'url' => $adminUrl . '/components/' . $componentName . '/admin/reports.php', 'page_identifier' => $componentName . '_reports', 'icon' => 'assessment'],
        ['title' => 'Alerts', 'url' => $adminUrl . '/components/' . $componentName . '/admin/alerts.php', 'page_identifier' => $componentName . '_alerts', 'icon' => 'notifications_active'],
        ['title' => 'API Documentation', 'url' => $adminUrl . '/components/' . $componentName . '/admin/api.php', 'page_identifier' => $componentName . '_api', 'icon' => 'api'],
        ['title' => 'API Keys', 'url' => $adminUrl . '/components/' . $componentName . '/admin/api-keys.php', 'page_identifier' => $componentName . '_api_keys', 'icon' => 'vpn_key'],
        ['title' => 'Webhooks', 'url' => $adminUrl . '/components/' . $componentName . '/admin/webhooks.php', 'page_identifier' => $componentName . '_webhooks', 'icon' => 'webhook'],
        ['title' => 'Documentation', 'url' => $adminUrl . '/components/' . $componentName . '/admin/documentation.php', 'page_identifier' => $componentName . '_documentation', 'icon' => 'menu_book'],
        ['title' => 'Analytics', 'url' => $adminUrl . '/components/' . $componentName . '/admin/analytics.php', 'page_identifier' => $componentName . '_analytics', 'icon' => 'bar_chart'],
        ['title' => 'Compatibility', 'url' => $adminUrl . '/components/' . $componentName . '/admin/compatibility.php', 'page_identifier' => $componentName . '_compatibility', 'icon' => 'check_circle'],
        ['title' => 'Resources', 'url' => $adminUrl . '/components/' . $componentName . '/admin/resources.php', 'page_identifier' => $componentName . '_resources', 'icon' => 'folder'],
        ['title' => 'Settings', 'url' => $adminUrl . '/components/' . $componentName . '/admin/settings.php', 'page_identifier' => $componentName . '_settings', 'icon' => 'settings'],
    ];
    
    foreach ($menuLinks as $link) {
        try {
            $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, icon_svg_path, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, 1, 'admin')");
            $icon = $link['icon'] ?? null;
            $iconSvg = null;
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
            error_log("Component Manager: Error creating menu link {$link['title']}: " . $e->getMessage());
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
function component_manager_remove_menu_links($conn, $componentName) {
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

