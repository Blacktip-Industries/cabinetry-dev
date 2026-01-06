<?php
/**
 * Email Marketing Component - Default Menu Links
 * Creates menu links during installation
 */

/**
 * Create menu links for email_marketing component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function email_marketing_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
    // Create section heading
    $sectionTitle = 'Email Marketing';
    $sectionUrl = '#';
    $sectionIdentifier = 'email_marketing_section';
    
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
            'url' => $adminUrl . '/components/email_marketing/admin/dashboard.php',
            'page_identifier' => 'email_marketing_dashboard',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Campaigns',
            'url' => $adminUrl . '/components/email_marketing/admin/campaigns/index.php',
            'page_identifier' => 'email_marketing_campaigns',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Templates',
            'url' => $adminUrl . '/components/email_marketing/admin/templates/index.php',
            'page_identifier' => 'email_marketing_templates',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Leads',
            'url' => $adminUrl . '/components/email_marketing/admin/leads/index.php',
            'page_identifier' => 'email_marketing_leads',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Data Mining',
            'url' => $adminUrl . '/components/email_marketing/admin/data-mining/index.php',
            'page_identifier' => 'email_marketing_data_mining',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Coupons',
            'url' => $adminUrl . '/components/email_marketing/admin/coupons/index.php',
            'page_identifier' => 'email_marketing_coupons',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Loyalty Points',
            'url' => $adminUrl . '/components/email_marketing/admin/loyalty/index.php',
            'page_identifier' => 'email_marketing_loyalty',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Automation',
            'url' => $adminUrl . '/components/email_marketing/admin/automation/index.php',
            'page_identifier' => 'email_marketing_automation',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Queue',
            'url' => $adminUrl . '/components/email_marketing/admin/queue/index.php',
            'page_identifier' => 'email_marketing_queue',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Analytics',
            'url' => $adminUrl . '/components/email_marketing/admin/analytics/index.php',
            'page_identifier' => 'email_marketing_analytics',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Settings',
            'url' => $adminUrl . '/components/email_marketing/admin/settings.php',
            'page_identifier' => 'email_marketing_settings',
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
function email_marketing_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = 'email_marketing_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

