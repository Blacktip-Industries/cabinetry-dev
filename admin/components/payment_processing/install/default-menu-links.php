<?php
/**
 * Payment Processing Component - Default Menu Links
 * Creates menu links during installation
 */

/**
 * Create menu links for payment_processing component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function payment_processing_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100;
    
    // Create section heading
    $sectionTitle = 'Payment Processing';
    $sectionUrl = '#';
    $sectionIdentifier = 'payment_processing_section';
    
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
            'url' => $adminUrl . '/components/payment_processing/admin/index.php',
            'page_identifier' => 'payment_processing_dashboard',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Transactions',
            'url' => $adminUrl . '/components/payment_processing/admin/transactions/index.php',
            'page_identifier' => 'payment_processing_transactions',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Gateways',
            'url' => $adminUrl . '/components/payment_processing/admin/gateways/index.php',
            'page_identifier' => 'payment_processing_gateways',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Webhooks',
            'url' => $adminUrl . '/components/payment_processing/admin/webhooks/index.php',
            'page_identifier' => 'payment_processing_webhooks',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Refunds',
            'url' => $adminUrl . '/components/payment_processing/admin/refunds/index.php',
            'page_identifier' => 'payment_processing_refunds',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Subscriptions',
            'url' => $adminUrl . '/components/payment_processing/admin/subscriptions/index.php',
            'page_identifier' => 'payment_processing_subscriptions',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Reports',
            'url' => $adminUrl . '/components/payment_processing/admin/reports/index.php',
            'page_identifier' => 'payment_processing_reports',
            'icon' => null,
            'icon_svg_path' => null
        ],
        [
            'title' => 'Settings',
            'url' => $adminUrl . '/components/payment_processing/admin/settings.php',
            'page_identifier' => 'payment_processing_settings',
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
function payment_processing_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = 'payment_processing_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}

