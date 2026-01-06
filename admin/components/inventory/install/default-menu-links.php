<?php
/**
 * Inventory Component - Default Menu Links
 * Creates menu links during installation
 */

/**
 * Create menu links for inventory component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function inventory_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
    // Create section heading
    $sectionTitle = 'Inventory';
    $sectionUrl = '#';
    $sectionIdentifier = 'inventory_section';
    
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
            'url' => $adminUrl . '/components/inventory/admin/index.php',
            'page_identifier' => 'inventory_dashboard',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Items',
            'url' => $adminUrl . '/components/inventory/admin/items/index.php',
            'page_identifier' => 'inventory_items',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Locations',
            'url' => $adminUrl . '/components/inventory/admin/locations/index.php',
            'page_identifier' => 'inventory_locations',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Movements',
            'url' => $adminUrl . '/components/inventory/admin/movements/index.php',
            'page_identifier' => 'inventory_movements',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Transfers',
            'url' => $adminUrl . '/components/inventory/admin/transfers/index.php',
            'page_identifier' => 'inventory_transfers',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Adjustments',
            'url' => $adminUrl . '/components/inventory/admin/adjustments/index.php',
            'page_identifier' => 'inventory_adjustments',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Barcodes',
            'url' => $adminUrl . '/components/inventory/admin/barcodes/index.php',
            'page_identifier' => 'inventory_barcodes',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Reports',
            'url' => $adminUrl . '/components/inventory/admin/reports/index.php',
            'page_identifier' => 'inventory_reports',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Alerts',
            'url' => $adminUrl . '/components/inventory/admin/alerts/index.php',
            'page_identifier' => 'inventory_alerts',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Settings',
            'url' => $adminUrl . '/components/inventory/admin/settings/index.php',
            'page_identifier' => 'inventory_settings',
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
 * Remove menu links created by inventory component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @return array Result with success status
 */
function inventory_remove_menu_links($conn, $componentName) {
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

