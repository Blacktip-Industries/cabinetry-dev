<?php
/**
 * Formula Builder Component - Default Menu Links
 * Creates menu links during installation
 */

/**
 * Create menu links for formula_builder component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function formula_builder_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
    // Create section heading
    $sectionTitle = 'Formula Builder';
    $sectionUrl = '#';
    $sectionIdentifier = 'formula_builder_section';
    
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, page_identifier, menu_order, is_active, menu_type, is_section_heading) VALUES (?, ?, ?, ?, 1, 'admin', 1)");
    $stmt->bind_param("sssi", $sectionTitle, $sectionUrl, $sectionIdentifier, $menuOrder);
    $stmt->execute();
    $sectionHeadingId = $conn->insert_id;
    $createdMenus[] = $sectionHeadingId;
    $menuOrder++;
    
    // Create menu links
    $menuLinks = [
        [
            'title' => 'Dashboard',
            'url' => $adminUrl . '/components/formula_builder/admin/index.php',
            'page_identifier' => 'formula_builder_dashboard',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Formulas',
            'url' => $adminUrl . '/components/formula_builder/admin/formulas/index.php',
            'page_identifier' => 'formula_builder_formulas',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Create Formula',
            'url' => $adminUrl . '/components/formula_builder/admin/formulas/create.php',
            'page_identifier' => 'formula_builder_create',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Template Library',
            'url' => $adminUrl . '/components/formula_builder/admin/library/index.php',
            'page_identifier' => 'formula_builder_library',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Marketplace',
            'url' => $adminUrl . '/components/formula_builder/admin/library/marketplace.php',
            'page_identifier' => 'formula_builder_marketplace',
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
function formula_builder_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = 'formula_builder_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

