<?php
/**
 * Commerce Component - Default Menu Links
 * Creates menu links during installation
 */

/**
 * Create menu links for commerce component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function commerce_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
    // Create section heading
    $sectionTitle = 'Commerce';
    $sectionUrl = '#';
    $sectionIdentifier = 'commerce_section';
    
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
            'url' => $adminUrl . '/components/commerce/admin/index.php',
            'page_identifier' => 'commerce_dashboard',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Products',
            'url' => $adminUrl . '/components/commerce/admin/products/index.php',
            'page_identifier' => 'commerce_products',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Categories',
            'url' => $adminUrl . '/components/commerce/admin/products/categories.php',
            'page_identifier' => 'commerce_categories',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Orders',
            'url' => $adminUrl . '/components/commerce/admin/orders/index.php',
            'page_identifier' => 'commerce_orders',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Bulk Orders',
            'url' => $adminUrl . '/components/commerce/admin/orders/bulk-orders.php',
            'page_identifier' => 'commerce_bulk_orders',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Shipping Zones',
            'url' => $adminUrl . '/components/commerce/admin/shipping/zones.php',
            'page_identifier' => 'commerce_shipping_zones',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Shipping Methods',
            'url' => $adminUrl . '/components/commerce/admin/shipping/methods.php',
            'page_identifier' => 'commerce_shipping_methods',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Carriers',
            'url' => $adminUrl . '/components/commerce/admin/shipping/carriers.php',
            'page_identifier' => 'commerce_carriers',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Inventory',
            'url' => $adminUrl . '/components/commerce/admin/inventory/index.php',
            'page_identifier' => 'commerce_inventory',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Warehouses',
            'url' => $adminUrl . '/components/commerce/admin/inventory/warehouses.php',
            'page_identifier' => 'commerce_warehouses',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Settings',
            'url' => $adminUrl . '/components/commerce/admin/settings.php',
            'page_identifier' => 'commerce_settings',
            'icon' => null,
            'icon_svg_path' => null
        ]
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
function commerce_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = 'commerce_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

