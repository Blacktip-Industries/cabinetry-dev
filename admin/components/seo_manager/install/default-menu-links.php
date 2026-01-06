<?php
/**
 * SEO Manager Component - Default Menu Links
 * Creates menu entries in menu_system_menus table during installation
 */

/**
 * Create menu links for SEO Manager component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function seo_manager_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100; // Starting order
    
    // Create section heading
    $sectionTitle = 'SEO Manager';
    $sectionUrl = '#';
    $sectionIdentifier = 'seo_manager_section';
    
    $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, page_identifier, menu_order, is_active, menu_type, is_section_heading) VALUES (?, ?, ?, ?, 1, 'admin', 1)");
    $stmt->bind_param("sssi", $sectionTitle, $sectionUrl, $sectionIdentifier, $menuOrder);
    $stmt->execute();
    $sectionHeadingId = $conn->insert_id;
    $createdMenus[] = $sectionHeadingId;
    $menuOrder++;
    
    // Create menu links for each admin page
    $menuLinks = [
        [
            'title' => 'Dashboard',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/dashboard.php',
            'page_identifier' => 'seo_manager_dashboard',
            'icon' => 'dashboard'
        ],
        [
            'title' => 'Pages',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/pages.php',
            'page_identifier' => 'seo_manager_pages',
            'icon' => 'pages'
        ],
        [
            'title' => 'Keywords',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/keywords.php',
            'page_identifier' => 'seo_manager_keywords',
            'icon' => 'search'
        ],
        [
            'title' => 'Content Optimizer',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/content-optimizer.php',
            'page_identifier' => 'seo_manager_content_optimizer',
            'icon' => 'edit'
        ],
        [
            'title' => 'Sitemap',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/sitemap.php',
            'page_identifier' => 'seo_manager_sitemap',
            'icon' => 'sitemap'
        ],
        [
            'title' => 'Robots.txt',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/robots.php',
            'page_identifier' => 'seo_manager_robots',
            'icon' => 'robot'
        ],
        [
            'title' => 'Schema Markup',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/schema.php',
            'page_identifier' => 'seo_manager_schema',
            'icon' => 'code'
        ],
        [
            'title' => 'Analytics',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/analytics.php',
            'page_identifier' => 'seo_manager_analytics',
            'icon' => 'analytics'
        ],
        [
            'title' => 'Rankings',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/rankings.php',
            'page_identifier' => 'seo_manager_rankings',
            'icon' => 'trending_up'
        ],
        [
            'title' => 'Technical Audits',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/technical-audits.php',
            'page_identifier' => 'seo_manager_technical_audits',
            'icon' => 'bug_report'
        ],
        [
            'title' => 'Backlinks',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/backlinks.php',
            'page_identifier' => 'seo_manager_backlinks',
            'icon' => 'link'
        ],
        [
            'title' => 'Schedules',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/schedules.php',
            'page_identifier' => 'seo_manager_schedules',
            'icon' => 'schedule'
        ],
        [
            'title' => 'AI Configuration',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/ai-config.php',
            'page_identifier' => 'seo_manager_ai_config',
            'icon' => 'settings'
        ],
        [
            'title' => 'Settings',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/settings.php',
            'page_identifier' => 'seo_manager_settings',
            'icon' => 'settings'
        ]
    ];
    
    foreach ($menuLinks as $link) {
        $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, NULL, ?, ?, 1, 'admin')");
        $icon = $link['icon'] ?? null;
        $stmt->bind_param("ssssii", 
            $link['title'],
            $link['url'],
            $icon,
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
 * Remove menu links created by SEO Manager component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @return array Result with success status
 */
function seo_manager_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = 'seo_manager_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

