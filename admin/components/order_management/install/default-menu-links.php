<?php
/**
 * Order Management Component - Default Menu Links
 * Creates menu links during installation
 */

/**
 * Create menu links for order_management component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function order_management_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
    // Create section heading
    $sectionTitle = 'Order Management';
    $sectionUrl = '#';
    $sectionIdentifier = 'order_management_section';
    
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, page_identifier, menu_order, is_active, menu_type, is_section_heading) VALUES (?, ?, ?, ?, 1, 'admin', 1)");
    $stmt->bind_param("sssi", $sectionTitle, $sectionUrl, $sectionIdentifier, $menuOrder);
    $stmt->execute();
    $sectionHeadingId = $conn->insert_id;
    $createdMenus[] = $sectionHeadingId;
    $menuOrder++;
    $stmt->close();
    
    // Create menu links
    $menuLinks = [
        [
            'title' => 'Dashboard',
            'url' => $adminUrl . '/components/order_management/admin/index.php',
            'page_identifier' => 'order_management_dashboard',
        ],
        [
            'title' => 'Orders',
            'url' => $adminUrl . '/components/order_management/admin/orders/index.php',
            'page_identifier' => 'order_management_orders',
        ],
        [
            'title' => 'Workflows',
            'url' => $adminUrl . '/components/order_management/admin/workflows/index.php',
            'page_identifier' => 'order_management_workflows',
        ],
        [
            'title' => 'Fulfillment',
            'url' => $adminUrl . '/components/order_management/admin/fulfillment/index.php',
            'page_identifier' => 'order_management_fulfillment',
        ],
        [
            'title' => 'Automation',
            'url' => $adminUrl . '/components/order_management/admin/automation/index.php',
            'page_identifier' => 'order_management_automation',
        ],
        [
            'title' => 'Returns',
            'url' => $adminUrl . '/components/order_management/admin/returns/index.php',
            'page_identifier' => 'order_management_returns',
        ],
        [
            'title' => 'Reports',
            'url' => $adminUrl . '/components/order_management/admin/reports/index.php',
            'page_identifier' => 'order_management_reports',
        ],
        [
            'title' => 'Templates',
            'url' => $adminUrl . '/components/order_management/admin/templates/index.php',
            'page_identifier' => 'order_management_templates',
        ],
        [
            'title' => 'Settings',
            'url' => $adminUrl . '/components/order_management/admin/settings/index.php',
            'page_identifier' => 'order_management_settings',
        ],
    ];
    
    foreach ($menuLinks as $link) {
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
 * Remove menu links created by component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @return array Result with success status
 */
function order_management_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = 'order_management_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

