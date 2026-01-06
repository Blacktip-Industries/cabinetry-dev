<?php
/**
 * Product Options Component - Default Menu Links
 * Creates menu links during installation
 */

/**
 * Create menu links for product_options component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function product_options_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
    // Create section heading
    $sectionTitle = 'Product Options';
    $sectionUrl = '#';
    $sectionIdentifier = 'product_options_section';
    
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, page_identifier, menu_order, is_active, menu_type, is_section_heading) VALUES (?, ?, ?, ?, 1, 'admin', 1)");
    $stmt->bind_param("sssi", $sectionTitle, $sectionUrl, $sectionIdentifier, $menuOrder);
    $stmt->execute();
    $sectionHeadingId = $conn->insert_id;
    $createdMenus[] = $sectionHeadingId;
    $menuOrder++;
    
    // Create menu links
    $menuLinks = [
        [
            'title' => 'Options Dashboard',
            'url' => $adminUrl . '/components/product_options/admin/index.php',
            'page_identifier' => 'product_options_dashboard',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Create Option',
            'url' => $adminUrl . '/components/product_options/admin/options/create.php',
            'page_identifier' => 'product_options_create',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Visual Builder',
            'url' => $adminUrl . '/components/product_options/admin/options/builder.php',
            'page_identifier' => 'product_options_builder',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Manage Groups',
            'url' => $adminUrl . '/components/product_options/admin/groups/index.php',
            'page_identifier' => 'product_options_groups',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Manage Queries',
            'url' => $adminUrl . '/components/product_options/admin/queries/index.php',
            'page_identifier' => 'product_options_queries',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Templates',
            'url' => $adminUrl . '/components/product_options/admin/templates/index.php',
            'page_identifier' => 'product_options_templates',
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
    }
    
    return ['success' => true, 'menu_ids' => $createdMenus];
}

/**
 * Remove menu links created by component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @return array Result with success status
 */
function product_options_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = 'product_options_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

